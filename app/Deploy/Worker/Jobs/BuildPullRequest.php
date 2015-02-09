<?php
namespace Deploy\Worker\Jobs;

use Deploy\Worker\Task;
use Exception;
use Log;
use File;
use Deploy\Worker\Job;
use Deploy\Account\Repo;
use Deploy\Locks\JobLock;
use Eleme\Rlock\Lock;
use Deploy\Site\Site;
use Deploy\Site\Commit;
use Deploy\Site\Build;
use Deploy\Exception\BaseException;
use Deploy\Site\PullRequestBuild;
use Deploy\Facade\Worker;

class BuildPullRequest extends Task
{
    protected $site;
    protected $pr;

    protected $LOG_PREFIX;

    public function fire($worker)
    {
        $site = Site::with('deploy_config')->findOrFail($this->message['site_id']);
        $pr = PullRequestBuild::findOrFail($this->message['pr_id']);

        $LOG_PREFIX = "[Site {$site->name}] [PR Build #{$pr->title}],";
        $redis = app('redis')->connection();

        $this->site = $site;
        $this->pr = $pr;
        $this->LOG_PREFIX = $LOG_PREFIX;

        $lock = null;
        $process = 0;
        try {
            $BASE_PATH = getenv('DEPLOY_BASE_PATH');
            if (empty($BASE_PATH)) {
                throw new BaseException('env 没有配置base path');
            }

            $SITE_DIR = $BASE_PATH . '/' . $site->id;
            $BRANCH_DIR = $SITE_DIR . '/branchs/default';
            $PR_BRANCH_DIR = $SITE_DIR . '/branchs/pull_request_default';
            $PR_COMMIT_DIR = $SITE_DIR . '/pull_request/commits';
            $COMMIT_PATH = $PR_COMMIT_DIR . '/' . $pr->commit;

            $PULL_KEY_FILE = empty($site->realPullKey()) ? null : "{$SITE_DIR}/keys/{$site->id}.pull";
            $PULL_KEY_PASSPHRASE = $site->realPullKeyPassphrase();

            if (!File::exists($PR_BRANCH_DIR)) {
                $lock = new Lock($redis, JobLock::buildRepo($site->id), array('timeout' => 60000, 'blocking' => false));
                if (!$lock->acquire()) {
                    $worker->log("{$LOG_PREFIX} Build Repo Job locked, Releasing ");
                    $worker->releaseJob(30);

                    return;
                }
                $process = 1;
                //$this->process("mkdir -p {$PR_BRANCH_DIR}");
                $this->process("mkdir -p {$PR_COMMIT_DIR}");
                $this->process("cp -rf {$BRANCH_DIR} {$PR_BRANCH_DIR}");
                $process = 2;
                $lock->release();
                $lock = null;
            }

            $lock = new Lock($redis, JobLock::buildPullRequest($site->id), array('timeout' => 60000, 'blocking' => false));
            if (!$lock->acquire()) {
                $worker->log("{$LOG_PREFIX} Build Pull Request Job locked, Releasing ");
                $worker->releaseJob(30);

                return;
            }

            $worker->log("{$LOG_PREFIX} Build Pull Request Start");

            if (!File::exists($COMMIT_PATH)) {
                $this->gitProcess("git fetch -f origin +refs/pull/{$pr->number}/head", $PR_BRANCH_DIR, $PULL_KEY_FILE, $PULL_KEY_PASSPHRASE);

                $show = $this->process("git show FETCH_HEAD | grep -E 'commit (.+)' | cut -c8- ", $PR_BRANCH_DIR);
                $REAL_COMMIT = trim($show->getOutput());

                if ($REAL_COMMIT != $pr->commit) {
                    $pr->setCommandStatus(PullRequestBuild::STATUS_FATAL, PullRequestBuild::STATUS_FATAL);
                    $this->job->errorLine("pr commit 和 获取到的真实 commit 不匹配， 当前commit 可能已经被覆盖");
                    $worker->deleteJob(Job::STATUS_ERROR);

                    return ;
                }
                $process = 3;
                $this->process("cp -rf {$PR_BRANCH_DIR} {$COMMIT_PATH}");
                $process = 4;
            }

            $lock->release();
            $lock = null;
            $this->process("git checkout -qf {$pr->commit}", $COMMIT_PATH);

            try {
                $pr->setCommandStatus(PullRequestBuild::STATUS_DOING, PullRequestBuild::STATUS_WAITING);
                if (!empty($site->build_command)) {
                    $this->process($site->build_command, $COMMIT_PATH);
                }
            } catch (Exception $e) {
                $pr->setCommandStatus(null, PullRequestBuild::STATUS_ABORT);
                $this->sendNotify('failure', 'Build Failure');
                throw new Exception("Build Failure: " . $e->getMessage(), 1379);
            }

            try {
                $pr->setCommandStatus(PullRequestBuild::STATUS_SUCCESS, PullRequestBuild::STATUS_DOING);
                if (!empty($site->test_command)) {
                    $this->process($site->test_command, $COMMIT_PATH);
                }
            } catch (Exception $e) {
                $pr->setCommandStatus(null, PullRequestBuild::STATUS_ERROR);
                $this->sendNotify('failure', 'Test Failure');
                throw new Exception("Test Failure: " . $e->getMessage(), 1379);
            }
            $pr->setCommandStatus(PullRequestBuild::STATUS_SUCCESS, PullRequestBuild::STATUS_SUCCESS);
            $worker->deleteJob();
            $worker->log("{$LOG_PREFIX} Build Pull Request Success");

            $this->sendNotify('success', 'Build & Test Success');

        } catch (Exception $e) {
            if ($lock != null) {
                $lock->release();
            }
            if ($e->getCode() != 1379) {
                $this->sendNotify('error', 'Job Error');
            } else {
                $pr->setCommandStatus(PullRequestBuild::STATUS_ERROR, null);
            }

            $worker->log("{$LOG_PREFIX} Build Pull Request Error [{$e->getLine()}: {$e->getMessage()}]");

            switch ($process) {
            case 1:
                $this->process("mkdir -p {$PR_COMMIT_DIR}", null, false);
                $this->process("rm -rf {$PR_BRANCH_DIR}", null, false);
                break;
            case 3:
                $this->process("rm -rf {$COMMIT_PATH}", null, false);
                break;
            }
            $worker->deleteJob(Job::STATUS_ERROR);
            //$this->job->errorLine($e);

            throw $e;
        }
    }

    public function sendNotify($status, $description)
    {
        try {
            $task = Worker::createTask('Deploy\Worker\Tasks\PRStatusNotify', "发送notify", array(
                'site_id' => $this->site->id,
                'pr_id' => $this->pr->id,
                'job_id' => $this->job->id,
                'description' => $description,
                'status' => $status
            ), $this->job->id);

            Worker::pushTask($task);
        } catch (Exception $e) {
            Log::error($e);
            Log::error("PR Deploy Notify Error");
        }
    }
}
