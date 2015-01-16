<?php
namespace Deploy\Worker;

use Log;
use Exception;
use ErrorException;
use Deploy\Worker\Job;
use Symfony\Component\Debug\Exception\FatalErrorException;

class Worker
{
    const KEY_PREFIX = 'DEPLOY:K:WORKER:PID:';

    protected $reportKey;

    protected $job;
    protected $queue;
    protected $pid;
    protected $redis;

    public function __construct($job, JobQueue $queue, $pid)
    {
        $this->job = $job;
        $this->queue = $queue;
        $this->pid = $pid;
        $this->redis = app('redis')->connection();
        $this->reportKey = self::KEY_PREFIX . $this->job->id;
    }

    public function report()
    {
        $this->redis->setex($this->reportKey, 60 * 60, $this->pid);
    }

    public function offDutty()
    {
        $this->redis->del($this->reportKey);
    }

    public function run()
    {
        $this->report();
        Log::info("WORKER [ {$this->pid} ] : job start [ {$this->job->id} ] {$this->job->class};");
        $this->job->status = Job::STATUS_DOING;
        $this->job->save();

        try {
            $reflection = new \ReflectionClass($this->job->class);
            $instance = $reflection->newInstance($this->job);
            $instance->fire($this);

        } catch (Exception $e) {
            Log::error($e);
            $this->deleteJob(Job::STATUS_ERROR);
        } catch (FatalErrorException $e) {
            Log::error($e);
            $this->deleteJob(Job::STATUS_ERROR);
        }

        Log::info("WORKER [ {$this->pid} ] : job finish [ {$this->job->id} ] {$this->job->class};");
        $this->offDutty();
    }

    public function deleteJob($status = Job::STATUS_SUCCESS)
    {
        $this->job->status = $status;
        $this->job->save();

        $this->queue->removeJobFromReserved($this->job->id);
    }

    public function releaseJob($time = 30)
    {
        $this->queue->delay($this->job->id, $time);
        $this->queue->removeJobFromReserved($this->job->id);
        $this->log("Job {$this->job->id} Release");

        $this->job->status = Job::STATUS_WAITING;
        $this->job->save();
    }

    public function log($info)
    {
        Log::info("[WORKER {$this->pid}] [Job {$this->job->id}]: $info ; ");
    }
}
