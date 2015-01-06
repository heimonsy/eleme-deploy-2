<?php
namespace Deploy\Worker;

use Config;
use Exception;

class JobQueue
{
    private $queueKey;
    private $reservedKey;
    private $reservedTimeout;
    private $delayKey;
    private $queueName;
    private $redis;

    public function __construct($queue)
    {
        if (empty($queue)) {
            throw new Exception('queue name can\'t be empty');
        }

        $this->queueName = $queue;
        $this->queueKey = $queue . ':QUEUE';
        $this->reservedKey = $queue . ':RESERVED';
        $this->delayKey = $queue . ':DELAY';
        $this->reservedTimeout  = Config::get('worker.timeout.reserved') ?: 300;
        $this->redis = app('redis')->connection();
    }

    public function queueName()
    {
        return $this->queueName;
    }

    public function pop()
    {
        $this->migrateAllDelayJobs();
        //$this->migrateAllExpiredJobs();

        $jobId = $this->redis->rpop($this->queueKey);
        if ($jobId !== null) {
            $time = time() + $this->reservedTimeout;
            $this->redis->zadd($this->reservedKey, $time, $jobId);
        }

        return $jobId;
    }

    public function push($jobId)
    {
        return $this->redis->lpush($this->queueKey, $jobId);
    }

    public function delay($jobId, $time = 30)
    {
        $time = $time + time();

        return $this->redis->zadd($this->delayKey, $time, $jobId);
    }

    public function removeJobFromReserved($jobId)
    {
        return $this->redis->zrem($this->reservedKey, $jobId);
    }

    public function migrateAllExpiredJobs()
    {
        $options = ['cas' => true, 'watch' => $this->reservedKey, 'retry' => 5];
        $this->redis->transaction($options, function ($transaction) {
            $time = time();
            $this->migrateAndRepushJobs($transaction, $this->reservedKey, $this->queueKey, $time);
        });
    }

    public function migrateAllDelayJobs()
    {
        $options = ['cas' => true, 'watch' => $this->delayKey, 'retry' => 5];
        $this->redis->transaction($options, function ($transaction) {
            $time = time();
            $this->migrateAndRepushJobs($transaction, $this->delayKey, $this->queueKey, $time);
        });
    }

    public function migrateAndRepushJobs($transaction, $from, $to, $time)
    {
        $transaction->multi();
        $list = $this->redis->zrangebyscore($from, '-inf', $time);
        if (!empty($list)) {
            foreach ($list as $jobId) {
                $this->redis->lpush($to, $jobId);
            }
            $this->redis->zremrangebyscore($from, '-inf', $time);
        }
    }
}
