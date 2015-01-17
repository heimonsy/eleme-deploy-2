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

class BuildRepo extends Task
{
    public function fire($worker)
    {
        $site = Site::with('deploy_config')->findOrFail($this->message['site_id']);
        $build = Build::findOrFail($this->message['build_id']);

        $LOG_PREFIX = "[Site {$site->name}] [Build {$build->id}],";
        $redis = app('redis')->connection();

        $lock = new Lock($redis, JobLock::buildRepo($site->id), array('timeout' => 600000, 'blocking' => false));
        if (!$lock->acquire()) {
            $worker->log("{$LOG_PREFIX} Build Repo Job locked, Releasing ");
            $worker->releaseJob(30);

            return;
        }

        $process = 0;
        try {
            $config = $site->deploy_config;
            if ($config === null) {
                throw new BaseException('deploy config 未配置');
            }

            $BASE_PATH = getenv('DEPLOY_BASE_PATH');
            if ($BASE_PATH === false) {
                throw new BaseException('env 没有配置base path');
            }
            $build->setStatus(Build::STATUS_BUILDING);
            $worker->log("{$LOG_PREFIX} Build Repo Job Start");

            $REPO_GIT = $site->repo_git;
            $CHECKOUT = $build->checkout;

            $SITE_DIR = $BASE_PATH . '/' . $site->id;
            $PULL_KEY_FILE = $SITE_DIR . '/keys/' . $site->id . '.pull';
            $PULL_KEY_PASSPHRASE = $site->pull_key_passphrase;

            $BRANCH_DIR = $SITE_DIR . '/branchs/default';
            $COMMIT_DIR = $SITE_DIR . '/commits';
            $CHECKOUT_PATH = $COMMIT_DIR . '/TMP_' . $CHECKOUT;
            if (!File::exists($BRANCH_DIR)) {
                $this->process('mkdir -p ' . $BRANCH_DIR);
                $this->process('mkdir -p ' . $COMMIT_DIR);
                $process = 1;
                $this->gitProcess("git clone {$REPO_GIT} $BRANCH_DIR", $BRANCH_DIR, $PULL_KEY_FILE, $PULL_KEY_PASSPHRASE);
            }
            $process = 2;

            $this->gitProcess("git pull origin ", $BRANCH_DIR, $PULL_KEY_FILE, $PULL_KEY_PASSPHRASE);

            $this->process("cp -rf '{$BRANCH_DIR}' '{$CHECKOUT_PATH}' ");
            $process = 3;

            $this->process("git checkout {$CHECKOUT} ", $CHECKOUT_PATH);
            $show = $this->process("git show {$CHECKOUT} | grep -E 'commit (.+)' | cut -c8- ", $CHECKOUT_PATH);
            $COMMIT = trim($show->getOutput());
            if (!Commit::isCommit($COMMIT)) {
                throw new BaseException("{$LOG_PREFIX} [Checkout {$CHECKOUT}] 获取commit出错");
            }
            $COMMIT_PATH = $COMMIT_DIR . '/' . $COMMIT;

            $needBuild = true;
            if (File::exists($COMMIT_PATH)) {
                $this->process("rm -rf $CHECKOUT_PATH");
                $needBuild = false;
            } else {
                $this->process("mv -f $CHECKOUT_PATH $COMMIT_PATH");
            }
            $process = 4;

            if ($needBuild) {
                if (!empty($site->build_command)) {
                    $this->process($site->build_command, $COMMIT_PATH);
                }
                if (!empty($site->test_command)) {
                    $this->process($site->test_command, $COMMIT_PATH);
                }
            }
            $process = 5;

            $commit = Commit::firstOrCreate(array('site_id' => $site->id, 'commit' => $COMMIT, 'checkout' => $CHECKOUT));
            $build->commit = $COMMIT;
            $build->setStatus(Build::STATUS_SUCCESS);
            $worker->log("{$LOG_PREFIX} Build Success");
            $status = Job::STATUS_SUCCESS;

            $lock->release();
        } catch (Exception $e) {
            $lock->release();
            $build->setStatus(Build::STATUS_ERROR);
            $worker->log("{$LOG_PREFIX} Build Error");
            $worker->log($e);
            switch ($process) {
            case 1:
                $this->process("rm -rf {$BRANCH_DIR}", null, false);
                break;
            case 3:
                $this->process("rm -rf {$CHECKOUT_PATH}", null, false);
                break;
            case 4:
                $this->process("rm -rf {$COMMIT_PATH}", null, false);
                break;
            }
            $status = Job::STATUS_ERROR;
        }
        $worker->log("{$LOG_PREFIX} Build Finish");
        $worker->deleteJob($status);
    }
}
