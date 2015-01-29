<?php

namespace Deploy\Worker\Tasks;

use Deploy\Worker\Task;
use Deploy\Site\Deploy;
use Deploy\Hosts\Host;
use SSHProcess\RsyncProcess;
use Deploy\Worker\DeployHost;
use Deploy\Site\Site;
use Deploy\Site\DeployConfig;
use Exception;
use Log;
use Deploy\Worker\DeployScript;
use Eleme\Rlock\Lock;
use Deploy\Locks\JobLock;

class DeployToHost extends Task
{
    public function fire($worker)
    {
        $site = Site::with('deploy_config')->findOrFail($this->message['site_id']);
        $config = DeployConfig::firstOrCreate(array('site_id' => $site->id));
        $deploy = Deploy::findOrFail($this->message['deploy_id']);
        $host = DeployHost::findOrFail($this->message['deploy_host_id']);

        $worker->registFatalErrorCallback(function () use ($host, $deploy) {
            $host->setStatus(DeployHost::STATUS_ERROR);
            $deploy->increaseError();
        });

        $LOG_PREFIX = "[Site {$site->name}] [Deploy To Host {$host->host_name}({$host->host_ip})],";


        $lock = null;
        try {
            $BASE_PATH = getenv('DEPLOY_BASE_PATH');
            if (empty($BASE_PATH)) {
                throw new BaseException('env 没有配置base path');
            }

            $COMMIT = $deploy->commit;
            $SITE_DIR = $BASE_PATH . '/' . $site->id;

            if ($deploy->type == Deploy::TYPE_DEPLOY) {
                $LOCAL_DIR = $SITE_DIR . '/commits/' . $COMMIT . '/';
            } else {
                $LOCAL_DIR = $SITE_DIR . '/pull_request/commits/' . $COMMIT . '/';
            }
            $RSYNC_EXCLUDE = $LOCAL_DIR. '/' . $site->rsync_exclude_file;

            $REMOTE_USER = $config->remote_user;
            $REMOTE_OWNER = $config->remote_owner;


            $DEPLOY_KEY_FILE = empty($config->realDeployKey()) ? null : "{$SITE_DIR}/keys/{$site->id}.deploy";
            $DEPLOY_KEY_PASSPHRASE = $config->realDeployKeyPassphrase();


            $HOST_NAME = $host->host_name;
            $HOST_PORT = $host->host_port;
            $HOST_IP = $host->host_ip;

            if ($host->type == Host::TYPE_APP) {
                $COMMAND_SCRIPT = DeployScript::complie($config->app_script, DeployScript::varList($site, $config));
                $REMOTE_DIR = $config->remote_app_dir;
                $IS_KEEP_FILES = RsyncProcess::FORCE_DELETE;
                $RSYNC_DIR  = $LOCAL_DIR;
            } else {
                $COMMAND_SCRIPT = DeployScript::complie($config->static_script, DeployScript::varList($site, $config));
                $REMOTE_DIR = $config->remote_static_dir;
                $IS_KEEP_FILES = RsyncProcess::KEEP_FILES;
                $RSYNC_DIR  = $LOCAL_DIR . '/' . $site->static_dir . '/';
            }

            $REMOTE_DIR = $this->clearDirString($REMOTE_DIR);

            $redis = app('redis')->connection();
            $lock = new Lock($redis, JobLock::deployHostLock($site->id, $host->host_ip), array('timeout' => 600000, 'blocking' => true));
            $lock->acquire();
            Log::info("$LOG_PREFIX Start");

            //执行同步前每次都执行的本地命令
            $this->processCommands($COMMAND_SCRIPT['before']['local']);
            //执行同步前每次都执行的远端命令
            $this->processCommands($COMMAND_SCRIPT['before']['remote'], $HOST_NAME, $HOST_IP, $REMOTE_USER, $DEPLOY_KEY_FILE, $DEPLOY_KEY_PASSPHRASE, $HOST_PORT);

            $this->sshProcess($HOST_NAME, $HOST_IP, $REMOTE_USER, "sudo mkdir -p {$REMOTE_DIR}", $DEPLOY_KEY_FILE, $DEPLOY_KEY_PASSPHRASE, null, $HOST_PORT);
            $this->sshProcess($HOST_NAME, $HOST_IP, $REMOTE_USER, "sudo chown {$REMOTE_USER} -R {$REMOTE_DIR}", $DEPLOY_KEY_FILE, $DEPLOY_KEY_PASSPHRASE, null, $HOST_PORT);
            $this->rsyncProcess($HOST_NAME, $HOST_IP, $REMOTE_USER, $RSYNC_EXCLUDE, $RSYNC_DIR, $REMOTE_DIR, $IS_KEEP_FILES, $DEPLOY_KEY_FILE, $DEPLOY_KEY_PASSPHRASE, null, $HOST_PORT);
            $this->sshProcess($HOST_NAME, $HOST_IP, $REMOTE_USER, "sudo chown {$REMOTE_OWNER} -R {$REMOTE_DIR}", $DEPLOY_KEY_FILE, $DEPLOY_KEY_PASSPHRASE, null, $HOST_PORT);

            //执行同步后每次都执行的本地命令
            $this->processCommands($COMMAND_SCRIPT['after']['local']);
            //执行同步后每次都执行的远端命令
            $this->processCommands($COMMAND_SCRIPT['after']['remote'], $HOST_NAME, $HOST_IP, $REMOTE_USER, $DEPLOY_KEY_FILE, $DEPLOY_KEY_PASSPHRASE, $HOST_PORT);
            $lock->release();
            $lock = null;

            Log::info("$LOG_PREFIX Success");

            $host->setStatus(DeployHost::STATUS_FINISH);
            $worker->deleteJob();

            $deploy->increaseSuccess();

        } catch (Exception $e) {
            $this->job->parentJob()->errorLine("{$host->host_name}({$host->host_ip}) error: " . $e->getMessage());
            $this->job->errorLine($e->getMessage());
            $host->setStatus(DeployHost::STATUS_ERROR);
            $deploy->increaseError();

            if ($lock !== null) {
                $lock->release();
            }
            Log::error($e);
            Log::info("$LOG_PREFIX Error");

            $worker->deleteJob(Workerable::STATUS_ERROR);
        }
    }

    public function clearDirString($str)
    {
        $dirArray = explode('/', $str);
        $realPath = '';
        foreach ($dirArray as $dir) {
            if (!empty($dir)) {
                $realPath .= '/' . $dir;
            }
        }
        $danger_path = array('', '/', '/root', '/boot', '/etc', '/dev'. '/lib');
        if (in_array($realPath, $danger_path)) {
            throw new Exception('Remote Dir (Or Static Dir)  Is Danger Path : ' . $str);
        }
        return $realPath .  '/';
    }
}
