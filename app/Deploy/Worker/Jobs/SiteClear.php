<?php
namespace Deploy\Worker\Jobs;

use Deploy\Worker\Task;
use Deploy\Worker\DeployScript;
use Deploy\Site\Site;
use Deploy\Site\Deploy;
use Deploy\Hosts\HostType;
use Deploy\Hosts\Host;
use Exception;
use Log;
use SplQueue;
use Deploy\Locks\JobLock;
use Deploy\Facade\Worker;
use Deploy\Worker\DeployHost;
use Deploy\Site\DeployConfig;
use Eleme\Rlock\Lock;
use Deploy\Site\Commit;
use Deploy\Worker\Job;
use Deploy\Site\PullRequestBuild;
use Deploy\Worker\SampleTask;
use Deploy\Site\Build;

class SiteClear extends Task
{
    public function fire($worker)
    {
        $LOG_PREFIX = '[CLEAR LOG]';
        $sites = Site::all();

        try {
            Log::info("=======================================");
            Log::info("=======================================");
            $BASE_PATH = getenv('DEPLOY_BASE_PATH');
            if (empty($BASE_PATH)) {
                throw new BaseException('env 没有配置base path');
            }

            foreach ($sites as $site) {
                Log::info("$LOG_PREFIX Start Clear Site {$site->name}");

                $SITE_DIR = $BASE_PATH . '/' . $site->id;
                $BRANCH_DIR = $SITE_DIR . '/branchs/default';
                $COMMIT_DIR = $SITE_DIR . '/commits';
                $PR_COMMIT_DIR = $SITE_DIR . '/pull_request/commits';

                $builds = $site->builds()->orderBy('id', 'desc')->skip(30)->take(1000)->get();
                $totalBuilds = count($builds);
                Log::info("$LOG_PREFIX Total Builds: $totalBuilds");
                if ($totalBuilds > 0) {
                    Log::info("$LOG_PREFIX Delete Builds, Total: $totalBuilds");
                    foreach ($builds as $build) {
                        $commit = Commit::where(array('checkout' => $build->checkout, 'commit' => $build->commit))->first();
                        if ($commit != null && !empty($commit->commit)) {
                            Log::info("$LOG_PREFIX Delete Commit: $commit->commit");
                            $COMMIT_PATH = $COMMIT_DIR . '/' . $commit->commit;
                            $process = $this->process('rm -rf ' . $COMMIT_PATH, null, false);
                            if (!$process->isSuccessful()) {
                                Log::info($LOG_PREFIX . ' Delete Commit ' . $build->commit . ' Error: ' . $process->getErrorOutput());
                            }
                            $commit->delete();
                        }

                        $job = Job::find($build->job_id);
                        if ($job != null) {
                            $job->delete();
                        }

                        $build->delete();
                    }
                }

                $prBuilds = PullRequestBuild::of($site)->closed()->orderBy('id', 'desc')->skip(30)->take(1000)->get();
                $totalPrBuilds = count($prBuilds);
                Log::info("$LOG_PREFIX Total Pr Builds: $totalPrBuilds");
                if ($totalPrBuilds > 0) {
                    Log::info("$LOG_PREFIX Delete PR Builds, Total: $totalPrBuilds");
                    foreach ($prBuilds as $build) {
                        if (!empty($build->commit)) {
                            Log::info("$LOG_PREFIX Delete Commit: $build->commit");
                            $COMMIT_PATH = $PR_COMMIT_DIR . '/' . $build->commit;
                            $process = $this->process('rm -rf ' . $COMMIT_PATH, null, false);
                            if (!$process->isSuccessful()) {
                                Log::info($LOG_PREFIX . ' Delete Pr Commit ' . $build->commit . ' Error: ' . $process->getErrorOutput());
                            }
                        }

                        $job = Job::find($build->job_id);
                        if ($job != null) {
                            $job->delete();
                        }

                        $build->delete();
                    }
                }

                $deploys = Deploy::where(array('site_id' => $site->id, 'type' => Deploy::TYPE_DEPLOY))->orderBy('id', 'desc')->skip(40)->take(1000)->get();
                Log::info("$LOG_PREFIX Total Deploys: " . count($deploys));
                $this->clearDeploys($deploys);
                $deploys = Deploy::where(array('site_id' => $site->id, 'type' => Deploy::TYPE_PR_DEPLOY))->orderBy('id', 'desc')->skip(40)->take(1000)->get();
                Log::info("$LOG_PREFIX Total Pr Deploys: " . count($deploys));
                $this->clearDeploys($deploys);
            }

            Log::info("$LOG_PREFIX Start Clear Remain Jobs");
            $jobIds = array_merge(
                Build::lists('job_id'),
                PullRequestBuild::lists('job_id'),
                Deploy::lists('job_id')
            );
            $jobs = Job::whereNotIn('id', $jobIds)->get();
            foreach ($jobs as $job) {
                $tasks = SampleTask::where('job_id', $job->id)->get();
                foreach ($tasks as $task) {
                    $task->delete();
                }

                $job->delete();
            }

            Log::info("$LOG_PREFIX Success");

            $worker->deleteJob();
        } catch (Exception $e) {
            Log::error($e);
            $worker->deleteJob(WorkableInterface::STATUS_ERROR);
        }

        Log::info("=======================================");
        Log::info("=======================================");
    }

    private function clearDeploys($deploys)
    {
        foreach ($deploys as $deploy) {
            DeployHost::where('deploy_id', $deploy->id)->delete();

            $job = Job::find($deploy->job_id);
            if ($job != null) {
                $tasks = SampleTask::where('job_id', $job->id)->get();
                foreach ($tasks as $task) {
                    $task->delete();
                }

                $job->delete();
            }

            $deploy->delete();
        }
    }
}
