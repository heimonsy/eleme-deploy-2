<?php

use Deploy\Account\Role;
use Deploy\Sentry\Permission;
use Deploy\Site\Site;
use Deploy\Site\Deploy;
use Deploy\Site\DeployConfig;
use Deploy\Hosts\HostTypeCatalog;
use Deploy\Account\User;
use Deploy\Facade\Worker;
use Deploy\Worker\Job;
use Deploy\Worker\DeployScript;
use Deploy\Site\PullRequestBuild;
use Deploy\Hosts\HostType;
use Deploy\Hosts\Host;
use Deploy\Worker\DeployHost;
use Deploy\Worker\Jobs\DeployCommit;


class ApiController extends Controller
{
    public function indexRolePermission(Role $role)
    {
        $permissions = $role->permissions()->lists('name');
        $addIsControlled = function (&$list) use ($permissions) {
            foreach ($list as $key => $value) {
                if (in_array($list[$key]['action'], $permissions)) {
                    $list[$key]['is_controlled'] = 1;
                } else {
                    $list[$key]['is_controlled'] = 0;
                }
            }
        };

        $siteAccess = Site::accessActionList();
        $siteManage = Site::manageActionList();
        $hostTypeCatalogAccess =  HostTypeCatalog::accessActionList();

        $addIsControlled($siteAccess);
        $addIsControlled($siteManage);
        $addIsControlled($hostTypeCatalogAccess);

        return Response::json(array(
            'code' => 0,
            'data' => array(
                'name' => $role->name,
                'id' => $role->id,
                'permissions' => array(
                    array(
                        'description' => '站点管理权限',
                        'list' => $siteManage,
                    ),
                    array(
                        'description' => '站点发布权限',
                        'list' => $siteAccess,
                    ),
                    array(
                        'description' => '环境发布权限',
                        'list' => $hostTypeCatalogAccess
                    )
                )
            )
        ));
    }

    public function storeRolePermission(Role $role)
    {
        $list = Input::get('permissions');
        if (empty($list)) {
            $list = array();
        }
        DB::transaction(function () use($list, $role) {
            $role->permissions()->delete();
            $permissions = [];
            foreach ($list as $value) {
                $permissions[] = $role->permissions()->create(array('name' => $value));
            }
            if (count($permissions) > 0) {
                $role->permissions()->saveMany($permissions);
            }
        });

        return Response::json(array('code' => 0, 'msg' => '权限修改成功'));
    }

    public function storeUserRole(User $user)
    {
        $validator = Validator::make(
            Input::only('role_id'),
            array('role_id' => 'required|numeric|exists:roles,id|unique:role_user,role_id,null,id,user_id,' . $user->id),
            array(
                'required' => '角色 id 不能为空',
                'numeric' => '角色 id 必须为数字',
                'exists' => '角色不存在',
                'unique' => '用户已经拥有该角色',
            )
        );

        if ($validator->fails()) {
            return Response::json(array(
                'code' => 1,
                'msg' => $validator->messages()->first(),
            ));
        }

        $user->roles()->attach(Input::only('role_id'));

        return Response::json(array('code' => 0, 'msg' => '添加成功'));
    }

    public function destroyUserRole(User $user, Role $role)
    {
        $user->roles()->detach($role->id);

        return Response::json(array('code' => 0, 'msg' => '删除成功'));
    }

    public function showSiteConfig(Site $site)
    {
        return Response::json(array(
            'code' => 0,
            'data' => $site
        ));
    }

    public function updateSiteConfig(Site $site)
    {
        Input::merge(array_map('trim', Input::all()));
        $site->fill(Input::only('static_dir', 'rsync_exclude_file', 'default_branch', 'build_command', 'test_command',
                                'hipchat_room', 'hipchat_token', 'pull_key', 'pull_key_passphrase', 'github_token'));
        $site->save();

        $pull_key = Input::get('pull_key');
        if ($pull_key != '******') {
            $user = Sentry::loginUser();
            $job = Worker::createJob(
                'Deploy\Worker\Jobs\StoreKey',
                "操作：Store Keys &nbsp; " . "项目：{$site->name} &nbsp;" . "操作者：{$user->name}({$user->login}) &nbsp;",
                array('site_id' => $site->id)
            );
            Worker::push($job);
        }


        return Response::json(array(
            'code' => 0,
            'msg' => '保存成功',
        ));
    }

    public function showDeployConfig(Site $site)
    {
        $deploy_config = $site->deploy_config()->first();
        if ($deploy_config == null) {
            $deploy_config = new DeployConfig;
            $deploy_config->site()->associate($site);
            $deploy_config->deploy_key = '';
            $deploy_config->save();
        }

        return Response::json(array(
            'code' => 0,
            'data' => $deploy_config,
        ));
    }

    public function updateDeployConfig(Site $site)
    {
        Input::merge(array_map('trim', Input::all()));
        $deploy_config = $site->deploy_config()->first();
        try {
            $APP_SCRIPT = DeployScript::complie(Input::get('app_script'), DeployScript::varList($site, $deploy_config));
            $STATIC_SCRIPT = DeployScript::complie(Input::get('static_script'), DeployScript::varList($site, $deploy_config));
        } catch (Exception $e) {
            Log::info($e);
            return array('code' => 1, 'msg' => '脚本解析出错, ' . $e->getMessage());
        }

        $deploy_config->fill(Input::only('remote_user', 'remote_owner', 'remote_app_dir', 'remote_static_dir',
            'app_script', 'static_script', 'deploy_key', 'deploy_key_passphrase'));

        $deploy_config->save();

        $user = Sentry::loginUser();
        $deploy_key = Input::get('deploy_key');
        if ($deploy_key != '******') {
            $user = Sentry::loginUser();
            $job = Worker::createJob(
                'Deploy\Worker\Jobs\StoreKey',
                "操作：Store Keys &nbsp; " . "项目：{$site->name} &nbsp;" . "操作者：{$user->name}({$user->login}) &nbsp;",
                array('site_id' => $site->id)
            );
            Worker::push($job);
        }

        return Response::json(array(
            'code' => 0,
            'msg' => '保存成功'
        ));
    }

    public function showSystemConfig()
    {
        $config = SystemConfig::firstOrNew(array('name' => 'system'));
        return Response::json(array(
            'code' => 0,
            'data' => $config
        ));
    }

    public function prRebuild(Site $site)
    {
        $pr = PullRequestBuild::findOrFail(Input::get('pr_id'));
        $pr->setCommandStatus(PullRequestBuild::STATUS_WAITING, PullRequestBuild::STATUS_WAITING);
        $job = Job::findOrFail($pr->job_id);
        $job->status = Job::STATUS_WAITING;
        $job->clear();
        $job->save();
        Worker::push($job);

        return Response::json(array(
            'code' => 0,
            'data' => array(
                'jobId' => $job->id
            )
        ));
    }

    public function siteTypeAndEnv(Site $site)
    {
        $type = Input::get('type');

        $hosts = DB::select("select id, name, type, ip from hosts where site_id = ? group by name, type, ip order by name,ip,type", array($site->id));

        $catalogs = HostTypeCatalog::all();
        $hostTypes = HostType::where('site_id', $site->id)->with('catalog')->orderBy('catalog_id')->get();
        $commits = array();
        if ($type == 'deploy') {
            $commits = $site->commits()->orderBy('id', 'desc')->limit(30)->get();
        } else {
            $commits = PullRequestBuild::of($site)->open()->success()->orderBy('id', 'desc')->limit(30)->get();
        }

        return Response::json(array(
            'code' => 0,
            'data' => array(
                'envs' => $catalogs,
                'types' => $hostTypes,
                'commits' => $commits,
                'hosts' => $hosts
            )
        ));
    }

    public function siteDeploy(Site $site)
    {
        $user = Sentry::loginUser();
        $deploy_kind = Input::get('deploy_kind');
        $deploy_to = Input::get('deploy_to');
        $hosts = array();

        if ($deploy_kind == 'host') {
            $host = Host::find($deploy_to);
            if ($host == null) {
                return Response::json(array('code' => 1, 'msg' => '机器名不存在'));
            }

            $hostType = $host->host_type()->first();
            $catalog = $hostType->catalog()->first();

            if (!$user->control($catalog->accessAction())) {
                return Response::json(array('code' => 1, 'msg' => '你没有发布到这台主机的权限'));
            }
            $hosts = array($host);
            $toName = "$host->name($host->ip)";

        } elseif ($deploy_kind == 'type') {
            $hostType = HostType::findorFail($deploy_to);
            if ($hostType == null) {
                return Response::json(array('code' => 1, 'msg' => '分组不存在'));
            }
            $catalog = $hostType->catalog()->first();
            $hosts = $hostType->hosts()->get();

            $toName = $hostType->name;
        } else {
            $catalog = HostTypeCatalog::find($deploy_to);
            if ($catalog == null) {
                return Response::json(array('code' => 1, 'msg' => '环境不存在'));
            }
            $types = HostType::where('catalog_id', $deploy_to)->where('site_id', $site->id)->with('hosts')->get();

            foreach ($types as $hostType) {
                $tHosts = $hostType->hosts;

                foreach ($tHosts as $host) {
                    $hosts[] = $host;
                }
            }
            $toName = $catalog->name;
        }
        if (count($hosts) == 0) {
            return Response::json(array(
                'code' => 1,
                'msg' => '所选的发布环境没有配置主机'
            ));
        }
        $realHosts = array(
            'APP' => array(),
            'STATIC' => array()
        );
        foreach ($hosts as $host) {
            $realHosts[$host->type][$host->ip] = $host;
        }
        $hosts = array_merge(array_values($realHosts['APP']), array_values($realHosts['STATIC']));
        if (count($hosts) == 0) {
            return Response::json(array(
                'code' => 1,
                'msg' => '2: 所选的发布环境没有配置主机'
            ));
        }

        $deployType = Input::get('type');
        $commit= substr(Input::get('commit'), 0, 7);

        $job = Worker::createJob(
            'Deploy\Worker\Jobs\DeployCommit',
            '操作：' . ($deployType == 'prdeploy' ? 'PR ' : '') .  "Deploy {$commit} To {$toName}; " . "项目：{$site->name} &nbsp;" . "操作者：{$user->name}({$user->login}) &nbsp;"
        );

        $deploy = new Deploy;
        $deploy->fill(Input::only('deploy_kind', 'deploy_to', 'commit'));
        $deploy->user_id = $user->id;
        $deploy->job_id = $job->id;
        $deploy->site_id = $site->id;
        $deploy->total_hosts = count($hosts);
        $deploy->description = $toName;
        $deploy->type = Input::get('type');
        $deploy->status = Deploy::STATUS_WAITING;
        $deploy->save();

        $deployHosts = array();
        $datetime = date('Y:m:d H:i:s');
        foreach ($hosts as $host) {
            $deployHosts[] = array(
                'job_id' => $job->id,
                'site_id' => $site->id,
                'host_type_id' => $host->host_type_id,
                'deploy_id' => $deploy->id,
                'type' => $host->type,
                'host_ip' => $host->ip,
                'host_name' => $host->name,
                'host_port' => $host->port,
                'created_at' => $datetime,
                'updated_at' => $datetime,
                'status' => DeployHost::STATUS_WAITING
            );
        }
        DeployHost::insert($deployHosts);

        $job->message = array(
            'site_id' => $site->id,
            'deploy_id' => $deploy->id
        );
        Worker::push($job);

        return Response::json(array(
            'code' => 0,
            'data' => array(
                'jobId' => $job->id
            ),
            'msg' => '发布任务创建成功'
        ));
    }

    public function indexDeploy(Site $site)
    {
        return Response::json(array(
            'code' => 0,
            'data' => Deploy::where(
                array(
                    'site_id' => $site->id,
                    'type' => Input::get('type'),
                )
            )->with(array('user' => function ($query) {
                $query->select('name', 'login', 'id');
            }))->orderBy('id', 'desc')->limit(30)->get(),
        ));
    }

    public function siteMultiHost(Site $site)
    {
        $hostTypes = HostType::where('site_id', $site->id)->lists('id', 'name');
        $date = date('Y-m-d H:i:s');

        $hostList = preg_replace('/ +/m', ' ', Input::get('host_list'));
        $hostList = explode("\n", $hostList);

        $errors = array();
        $input_hosts = array();
        $input_names = array();
        $input_ips = array(
            'APP' => array(),
            'STATIC' => array(),
        );
        $input_host_types = array();
        $f_error_to_msg = function ($errors) {
            $str = '';
            foreach ($errors as $key => $value) {
                $str .= "{$value}：{$key}<br>";
            }
            return $str;
        };

        $f_is_ip = function ($ip) {
            return
                preg_match('/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/', $ip)
                > 0;
        };
        $f_is_port = function ($port) {
            return $port > 0 && $port < 65536;
        };
        $f_have_duplicate = function ($arr) {
            $new = array_unique($arr);
            return count($new) !== count($arr);
        };

        foreach ($hostList as $line) {
            $line = trim($line);
            if ($line == '') continue;

            $host = explode(' ', $line);
            if (count($host) != 5) {
                $errors[$line] = '该行字段数不正确';
                continue;
            }
            $hosts[] = array(
                'name' => $host[2],
                'host_type_id' => isset($hostTypes[$host[0]]) ? $hostTypes[$host[0]] : 0,
                'ip' => $host[3],
                'port' => $host[4],
                'site_id' => $site->id,
                'type' => $host[1],
                'created_at' => $date,
                'updated_at' => $date,
            );

            if (!isset($hostTypes[$host[0]])) {
                $errors[$host[0]] = '分组不存在';
            }

            $host[1] = strtoupper($host[1]);
            if ($host[1] !== 'APP' && $host[1] !== 'STATIC') {
                $errors[$host[1]] = '发布类型不存在';
            } elseif ($f_is_ip($host[3])) {
                $input_ips[$host[1]][] = $host[3];
            } else {
                $errors[$host[3]] = 'IP格式错误';
            }

            if (!$f_is_port($host[4])) {
                $errors[$host[4]] = '端口格式错误';
            }

            $input_names[] = $host[2];
        }
        //$have_ips = array();
        //if (count($input_ips['APP']) > 0) {
            //$have_ips = array_merge($have_ips, Host::where(array('site_id' => $site->id, 'type' => 'APP'))->whereIn('ip', $input_ips['APP'])->lists('ip'));
            //if ($f_have_duplicate($input_ips['APP'])) {
                //$errors['IP-ERROR'] = '输入的IP有重复';
            //}
        //}
        //if (count($input_ips['STATIC']) > 0) {
            //$have_ips = array_merge($have_ips, Host::where(array('site_id' => $site->id, 'type' => 'STATIC'))->whereIn('ip', $input_ips['STATIC'])->lists('ip'));
            //if ($f_have_duplicate($input_ips['STATIC'])) {
                //$errors['IP-ERROR'] = '输入的IP有重复';
            //}
        //}
        //foreach ($have_ips as $ip) {
            //$errors[$ip] = 'IP已存在';
        //}

        //$have_names = array();
        //if (count($input_names) > 0) {
            //$have_names = Host::where(array('site_id' => $site->id))->whereIn('name', $input_names)->lists('name');
            //if ($f_have_duplicate($input_names)) {
                //$errors['NAME-ERRORS'] = '输入主机名有重复';
            //}
        //}
        //foreach ($have_names as $name) {
            //$errors[$name] = '主机名已存在';
        //}

        if (count($errors) > 0) {
            return Response::json(array(
                'code' => 1,
                'msg' => $f_error_to_msg($errors)
            ));
        }

        Host::insert($hosts);

        return Response::json(array(
            'code' => 0,
            'msg' => '添加成功'
        ));
    }

    public function killDeploy(Site $site)
    {
        $deploy = Deploy::find(Input::get('deploy_id'));
        if (!$deploy) {
            return Response::json(array(
                'code' => 1,
                'msg' => 'deploy 不存在'
            ));
        }

        DeployCommit::sendKillMessage($deploy);

        return Response::json(array(
            'code' => 0,
            'msg' => 'ok'
        ));
    }
}

