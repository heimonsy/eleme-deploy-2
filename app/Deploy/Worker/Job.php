<?php
namespace Deploy\Worker;

use Eloquent;
use Deploy\Interfaces\OutputInterface;
use Deploy\Traits\OutputTrait;

class Job extends Eloquent implements OutputInterface, WorkableInterface
{
    use OutputTrait;

    const TYPE_USER = 'user';
    const TYPE_SYSTEM = 'system';

    protected $table = 'jobs';

    protected $guarded = array('id');

    public function isSuccess()
    {
        return $this->status == WorkableInterface::STATUS_SUCCESS;
    }

    public function getOutputIdentify()
    {
        return  'JOB:' . $this->id;
    }

    public function getWorkIdentify()
    {
        return 'Job-' . $this->id;
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

    public function toArray()
    {
        return array_merge(parent::toArray(), array('output' => $this->getOutput()));
    }

    public function delete()
    {
        $this->clear();
        parent::delete();
    }
}
