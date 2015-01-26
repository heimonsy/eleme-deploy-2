<?php
namespace Deploy\Worker\Jobs;

use Deploy\Worker\Task;
use Exception;
use Log;
use Config;
use File;
use Deploy\Worker\Job;
use Deploy\Account\Repo;
use Deploy\Locks\JobLock;
use Eleme\Rlock\Lock;
use Deploy\Site\Site;
use Deploy\Site\Commit;
use Deploy\Site\Build;
use Deploy\Exception\BaseException;
use Deploy\Site\DeployConfig;

class StoreKey extends Task
{
    public function fire($worker)
    {
        $site = Site::with('deploy_config')->findOrFail($this->message['site_id']);
        $config = DeployConfig::firstOrCreate(array('site_id' => $site->id));
        $redis = app('redis')->connection();

        $LOG_PREFIX = "[Site {$site->name}] [Store Key],";
        $lock = new Lock($redis, JobLock::storeKey($site->id), array('timeout' => 60000, 'blocking' => false));
        if (!$lock->acquire()) {
            $worker->log("{$LOG_PREFIX} Store Key Job Locked, Releasing ");
            $worker->releaseJob(30);

            return;
        }

        try {
            $worker->log("$LOG_PREFIX Start");
            $BASE_PATH = getenv('DEPLOY_BASE_PATH');
            $SITE_KEYS_DIR = $BASE_PATH . "/{$site->id}/keys";
            if (!File::exists($SITE_KEYS_DIR)) {
                $this->process('mkdir -p ' . $SITE_KEYS_DIR);
            }
            $PULL_KEY_FILE = "{$SITE_KEYS_DIR}/{$site->id}.pull";
            $DEPLOY_KEY_FILE = "{$SITE_KEYS_DIR}/{$site->id}.deploy";
            $pull_key = $site->realPullKey();
            file_put_contents($PULL_KEY_FILE, $pull_key);
            chmod($PULL_KEY_FILE, 0600);
            $deploy_key = $config->realDeployKey();
            file_put_contents($DEPLOY_KEY_FILE, $deploy_key);
            chmod($DEPLOY_KEY_FILE, 0600);

            $worker->log("$LOG_PREFIX Success");
            $worker->deleteJob();
            $lock->release();

        } catch(Exception $e) {
            $worker->log("$LOG_PREFIX Error: " . $e->getMessage());
            $worker->deleteJob(Job::STATUS_ERROR);
            $lock->release();

            throw $e;
        }
    }
}
