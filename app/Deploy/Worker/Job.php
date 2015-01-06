<?php
namespace Deploy\Worker;

use Eloquent;
use Deploy\Interfaces\OutputInterface;
use Deploy\Traits\OutputTrait;

class Job extends Eloquent implements OutputInterface
{
    use OutputTrait;

    const TYPE_USER = 'user';
    const TYPE_SYSTEM = 'system';

    const STATUS_WAITING  = 'Waiting';
    const STATUS_DOING  = 'Doing';
    const STATUS_ERROR  = 'Error';
    const STATUS_SUCCESS  = 'Success';

    protected $table = 'jobs';

    protected $guarded = array('id');

    public function getId()
    {
        return $this->id;
    }

    public function getMessageAttribute($message)
    {
        return json_decode($message, true);
    }

    public function setMessageAttribute(array $message)
    {
        $this->attributes['message'] = json_encode($message);
    }
}
