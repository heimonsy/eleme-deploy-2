<?php

use Deploy\Account\Role;
use Deploy\Sentry\Permission;
use Deploy\Site\Site;
use Deploy\Site\DeployConfig;
use Deploy\Hosts\HostTypeCatalog;
use Deploy\Account\User;
use Deploy\Facade\Worker;


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
        $site->fill(Input::only('static_dir', 'rsync_exclude_file', 'default_branch', 'build_command', 'test_command',
                                'hipchat_room', 'hipchat_token', 'pull_key', 'pull_key_passphrase'));
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
            $deploy_config->save();
        }

        return Response::json(array(
            'code' => 0,
            'data' => $deploy_config,
        ));
    }

    public function updateDeployConfig(Site $site)
    {
        $deploy_config = $site->deploy_config()->first();
        $deploy_config->fill(Input::only('remote_user', 'remote_owner', 'remote_app_dir', 'remote_static_dir', 
            'app_script', 'static_script', 'deploy_key', 'deploy_key_passphrase'));

        $deploy_config->save();

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
}
