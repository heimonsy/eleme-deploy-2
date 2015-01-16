<?php
namespace Deploy\Facade;

use Deploy\Worker\Supervisor;
use Deploy\Exception\ClassNotFoundException;
use Deploy\Worker\JobQueue;
use Deploy\Worker\Job;
use Illuminate\Support\Facades\Facade;
use Config;
use Exception;

class Worker extends Facade
{

    public static function getListenPid()
    {
        return app('redis')->connection()->get(Supervisor::PID_KEY);
    }

    public static function status()
    {
        $redis = app('redis')->connection();
        $lastTime = $redis->get(Supervisor::PING_KEY);
        $pid = $redis->get(Supervisor::PID_KEY);
    }

    public static function createJob($class, $description, array $message = array(), $type = Job::TYPE_USER)
    {
        if (!class_exists($class)) {
            throw new ClassNotFoundException('内部错误', "task class {$class} not found");
        }
        $job = new Job;
        $job->class = $class;
        $job->message = $message;
        $job->description = $description;
        $job->type = $type;
        $job->status = Job::STATUS_CREATED;
        $job->save();

        return $job;
    }

    public static function push(Job $job, $queueName = 'main')
    {
        $job->status = Job::STATUS_WAITING;
        $job->save();
        $queue = new JobQueue(Config::get('worker.queues.' . $queueName));
        $queue = $queue->push($job->id);
    }
}
