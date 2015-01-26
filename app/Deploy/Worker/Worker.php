<?php
namespace Deploy\Worker;

use Log;
use Exception;
use ErrorException;

class Worker
{
    const KEY_PREFIX = 'DEPLOY:K:WORKER:PID:';

    protected $reportKey;

    protected $job;
    protected $queue;
    protected $pid;
    protected $redis;

    protected $killCallback = array();
    protected $fatalErrorCallback = array();

    public function __construct(WorkableInterface $job, JobQueue $queue, $pid)
    {
        $this->job = $job;
        $this->queue = $queue;
        $this->pid = $pid;
        $this->redis = app('redis')->connection();
        $this->reportKey = self::getReportKey($this->job->getWorkIdentify());
    }

    public static function getReportKey($identify)
    {
        return self::KEY_PREFIX . $identify;
    }

    public static function kill(WorkableInterface $job)
    {
        $reportKey = self::getReportKey($this->job->getWorkIdentify());
        $redis = app('redis')->connection();
        $pid = $redis->get($reportKey);
        if ($pid !== null) {
        }
        return false;
    }

    public function report()
    {
        $this->redis->setex($this->reportKey, 60 * 60, $this->pid);
    }

    public function dispatch()
    {
        pcntl_signal_dispatch();
    }

    public function offDutty()
    {
        $this->redis->del($this->reportKey);
    }

    public function run()
    {
        $this->report();
        $this->registerKillCallback(function () {
            Log::info("WORKER [ {$this->pid} ] : job killed [ {$this->job->getWorkIdentify()} ] {$this->job->getWorkClass()};");
            $this->job->setStatus(WorkableInterface::STATUS_KILLED);
            $this->offDutty();
        });

        Log::info("WORKER [ {$this->pid} ] : job start [ {$this->job->getWorkIdentify()} ] {$this->job->getWorkClass()};");
        $this->job->setStatus(WorkableInterface::STATUS_DOING);

        try {
            $reflection = new \ReflectionClass($this->job->getWorkClass());
            $instance = $reflection->newInstance($this->job);
            $instance->fire($this);

        } catch (Exception $e) {
            Log::error($e);
            $this->deleteJob(WorkableInterface::STATUS_ERROR);
        } catch (ErrorException $e) {
            Log::error($e);
            $this->deleteJob(WorkableInterface::STATUS_ERROR);
        }

        Log::info("WORKER [ {$this->pid} ] : job finish [ {$this->job->getWorkIdentify()} ] {$this->job->getWorkClass()};");
        $this->offDutty();
    }

    public function fireFatalErrorCallback($exception)
    {
        Log::error($exception);
        $this->deleteJob(WorkableInterface::STATUS_ERROR);
        Log::info("WORKER [ {$this->pid} ] : job finish [ {$this->job->getWorkIdentify()} ] {$this->job->getWorkClass()};");
        $this->offDutty();

        foreach ($this->fatalErrorCallback as $callback) {
            $callback();
        }
    }

    public function registFatalErrorCallback($callback)
    {
        array_push($this->fatalErrorCallback, $callback);
    }

    public function deleteJob($status = WorkableInterface::STATUS_SUCCESS)
    {
        $this->queue->removeJobFromReserved($this->job->id);

        $this->job->setStatus($status);
    }

    public function releaseJob($time = 30)
    {
        $this->queue->delay($this->job->id, $time);
        $this->queue->removeJobFromReserved($this->job->id);
        $this->log("Job {$this->job->getWorkIdentify()} Release");

        $this->job->setStatus(WorkableInterface::STATUS_WAITING);
    }

    public function log($info)
    {
        Log::info("[WORKER {$this->pid}] [Job {$this->job->getWorkIdentify()}]: $info ; ");
    }

    public function fireKillCallback()
    {
        foreach ($this->killCallbackas as $callback) {
            $callback();
        }
    }

    public function registerKillCallback($callback)
    {
        array_push($this->killCallback, $callback);
    }
}
