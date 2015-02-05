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
use Deploy\Worker\WorkableInterface;

class DeployCommit extends Task
{
    private $site;
    private $deploy;
    private $hosts;

    private $LOG_PREFIX;
    private $COMMIT;
    private $MAX_DEPLOYS;

    public function fire($worker)
    {
        $this->MAX_DEPLOYS = 3;
        $this->site = Site::with('deploy_config')->findOrFail($this->message['site_id']);
        $config = DeployConfig::firstOrCreate(array('site_id' => $this->site->id));
        $this->deploy = Deploy::findOrFail($this->message['deploy_id']);

        $this->LOG_PREFIX = "[Site {$this->site->name}] [Deploy Commit {$this->deploy->commit}],";

        try {
            $this->COMMIT = $this->deploy->commit;

            $APP_SCRIPT = DeployScript::complie($config->app_script, DeployScript::varList($this->site, $config));
            $STATIC_SCRIPT = DeployScript::complie($config->static_script, DeployScript::varList($this->site, $config));

            $statics = DeployHost::where('deploy_id', $this->deploy->id)->static()->get();
            $apps = DeployHost::where('deploy_id', $this->deploy->id)->app()->get();
            $this->hosts = array(
                'static' => $this->arrayToQueue($statics),
                'app' => $this->arrayToQueue($apps),
            );

            $worker->log("{$this->LOG_PREFIX} Start");

            Log::info("$this->LOG_PREFIX Start Deploy Statics");
            $this->deploy->setStatus(Deploy::STATUS_DEPLOYING);
            /*****************************************
             *
             *  执行静态文件同步
             *
             *****************************************/
            //执行同步前本地命令
            $this->processCommands($STATIC_SCRIPT['before']['handle']);
            $this->deployPlan($this->hosts['static']);
            //执行同步后本地命令
            $this->processCommands($STATIC_SCRIPT['after']['handle']);


            Log::info("$this->LOG_PREFIX Start Deploy APPs");
            /*****************************************
             *
             *  执行APP文件同步
             *
             *****************************************/
            //执行同步前本地命令
            $this->processCommands($APP_SCRIPT['before']['handle']);
            $this->deployPlan($this->hosts['app']);
            //执行同步后本地命令
            $this->processCommands($APP_SCRIPT['after']['handle']);

            Log::info("$this->LOG_PREFIX Start Watch DeployToHost");
            // 监控任务执行情况
            $this->watchDeployToHost($this->deploy->id);

            $this->deploy->setStatus(Deploy::STATUS_SUCCESS);
            $worker->deleteJob();

            Log::info("$this->LOG_PREFIX Success");

            $this->sendNotify('Success');

        } catch (Exception $e) {
            $this->deploy->setStatus(Deploy::STATUS_ERROR);
            Log::info($e);

            $worker->deleteJob(WorkableInterface::STATUS_ERROR);
            Log::info("$this->LOG_PREFIX Error");
            $this->sendNotify('Error');
        }
    }

    public function deployPlan($hosts)
    {
        $redis = app('redis')->connection();

        $lock = null;
        try {
            while (!$hosts->isEmpty()) {
                $host = $hosts->shift();
                $type = $host->host_type_id;
                Log::info("$this->LOG_PREFIX shift $host->host_name");

                $total = DeployHost::deploy($this->deploy->id)->deployType($host->type)->type($type)->count();
                $count = DeployHost::deploy($this->deploy->id)->deployType($host->type)->type($type)->deploying()->count();
                Log::info("$this->LOG_PREFIX ({$host->type}, $count, $total)");

                if ($count < $this->MAX_DEPLOYS && $count < ceil($total / 2)) {
                    $lock = new Lock($redis, JobLock::deployHostLock($this->site->id, $host->host_ip), array('timeout' => 30000, 'blocking' => false));
                    if ($lock->acquire()) {
                        //释放掉，因为在DeployToHost里面也会锁住
                        $lock->release(); $lock = null;

                        $task = Worker::createTask('Deploy\Worker\Tasks\DeployToHost', "发布{$this->COMMIT}到主机:{$host->name}", array(
                            'site_id' => $this->site->id,
                            'deploy_id' => $this->deploy->id,
                            'deploy_host_id' => $host->id,
                        ), $this->job->id);
                        Worker::pushTask($task);
                        $host->task_id = $task->id; $host->setStatus(DeployHost::STATUS_DEPLOYING);
                        $this->job->commandLine("Start Deploy To {$host->host_name}({$host->host_ip})");
                        Log::info("$this->LOG_PREFIX Start Deploy To {$host->host_name}");
                    } else {
                        Log::info("$this->LOG_PREFIX LOCK push back $host->host_name");
                        $hosts->push($host);
                        $lock = null;
                    }
                } else {
                    Log::info("$this->LOG_PREFIX FULL push back $host->host_name");
                    $hosts->push($host);
                }
                sleep(1);
            };
        } catch (Exception $e) {
            if ($lock !== null)  {
                $lock->release();
            }
            throw $e;
        }
    }

    public function watchDeployToHost($deployId)
    {
        $start = time();
        while (true) {
            $deploy = Deploy::find($deployId);
            if ($deploy->total_hosts <= $deploy->success_hosts + $deploy->error_hosts) {
                break;
            }

            if (time() - $start > 600) {
                $hosts = DeployHost::of($deploy)->deploying()->get();
                if (count($hosts) == 0) {
                    throw new Exception("Unknow Error");
                }
                $hostsStr = '';
                foreach ($hosts as $host) {
                    $hostsStr .= "{$host->host_name}({$host->host_ip}) ";
                }
                throw new Exception("Deploy To {$hostsStr} Error");
            }

            sleep(5);
        }
    }

    public function arrayToQueue($hosts)
    {
        $hostQueue = new SplQueue();
        foreach ($hosts as $host) {
            $hostQueue->push($host);
        }
        return $hostQueue;
    }

    public function sendNotify($status)
    {
        try {
            $task = Worker::createTask('Deploy\Worker\Tasks\DeployNotify', "发送notify", array(
                'site_id' => $this->site->id,
                'deploy_id' => $this->deploy->id,
                'job_id' => $this->job->id,
                'status' => $status,
            ), $this->job->id);
            Worker::pushTask($task);

            Log::info("{$this->LOG_PREFIX} Push Notify Success");
        } catch (Exception $e) {
            Log::info($e);
            Log::info("{$this->LOG_PREFIX} Push Notify Faild");
        }
    }
}

