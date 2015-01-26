<?php
namespace Deploy\Worker;

use Eloquent;
use Deploy\Interfaces\OutputInterface;
use Deploy\Traits\OutputTrait;
use Deploy\Worker\Job;

class SampleTask extends Eloquent implements OutputInterface, WorkableInterface
{
    use OutputTrait;

    protected $table = 'sample_tasks';

    public function getOutputIdentify()
    {
        return 'SAMPLE:TASK:' . $this->id;
    }

    public function getWorkIdentify()
    {
        return 'SampleTask-' . $this->id;
    }

    public function getWorkClass()
    {
        return $this->class;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        $this->save();
    }

    public function getMessageAttribute($message)
    {
        return json_decode($message, true);
    }

    public function setMessageAttribute(array $message)
    {
        $this->attributes['message'] = json_encode($message);
    }

    public function delete()
    {
        $this->clear();
        parent::delete();
    }

    public function parentJob()
    {
        static $job = null;

        if ($job === null) {
            $job = Job::find($this->job_id);
            if ($job === null) {
                throw new \Exception('Can\'t find parent job!');
            }
        }
        return $job;
    }
}
