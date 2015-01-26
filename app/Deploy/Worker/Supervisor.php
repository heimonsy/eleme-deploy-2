<?php
namespace Deploy\Worker;

use Log;
use Exception;
use Symfony\Component\Process\Process;

class Supervisor
{
    const STATUS_LISTENING = 'LISTENING';
    const STATUS_NOT_LISTENING = 'NOT LISTENING';
    const STATUS_NO_RESPONSE = 'NO RESPOONSE';

    const PING_KEY = 'DEPLOY:K:JOB:LISTENING:PING';
    const PID_KEY = 'DEPLOY:K:JOB:LISTENING:PID';

    protected $OFF_DUTY = false;

    protected $queue;
    protected $taskQueue;
    protected $redis;
    protected $pid;

    public function __construct(JobQueue $queue, JobQueue $taskQueue)
    {
        $this->queue = $queue;
        $this->taskQueue = $taskQueue;
        $this->redis = app('redis')->connection();
    }

    public function listen($pid)
    {
        $this->pid = $pid;
        $this->ping();
        $this->setPid($pid);

        Log::info("LISTEN [ {$this->pid} ] : listen start, pid [{$pid}]");
        try {
            while (true) {
                if ($this->OFF_DUTY) {
                    $this->waitExit();
                    break;
                }
                $this->ping();

                $this->recvAndExecute();
                $this->recvTaskAndExecute();

                pcntl_signal_dispatch();
                sleep(3);
            }
        } catch (Exception $e) {
            Log::error($e);
        }

        $this->delPid();
        Log::info("LISTEN [ {$this->pid} ] : listen exit, pid [{$pid}]");

    }

    protected function waitExit()
    {
        // todo
    }

    protected function recvAndExecute()
    {
        try {
            $jobId = $this->queue->pop();
            if ($jobId !== null) {
                Log::info("LISTEN [ {$this->pid} ] : Job Recv [{$jobId}];");
                $p = new Process("php artisan worker:job job {$this->queue->queueName()} {$jobId}", base_path());
                $p->start();
                Log::info("LISTEN [ {$this->pid} ] : Job Started [{$jobId}] : {$p->getCommandLine()};");
            }
            // todo push process to list

        } catch (Exception $e) {
            Log::error($e);
        }
    }

    protected function recvTaskAndExecute()
    {
        try {
            $sampleTaskId = $this->taskQueue->pop();
            if ($sampleTaskId !== null) {
                Log::info("LISTEN: Sample Task Recv [{$sampleTaskId}];");
                $p = new Process("php artisan worker:job sampletask {$this->taskQueue->queueName()} {$sampleTaskId}", base_path());
                $p->start();
                Log::info("LISTEN: Sample Task Started [{$sampleTaskId}] : {$p->getCommandLine()};");
            }
            // todo push process to list

        } catch (Exception $e) {
            Log::error($e);
        }
    }

    public function offDuty()
    {
        $this->OFF_DUTY = true;
    }

    public function ping()
    {
        return $this->redis->setex(self::PING_KEY, 30, time());
    }

    public function setPid($pid)
    {
        return $this->redis->set(self::PID_KEY, $pid);
    }

    public function delPid()
    {
        return $this->redis->del(self::PID_KEY);
    }
}
