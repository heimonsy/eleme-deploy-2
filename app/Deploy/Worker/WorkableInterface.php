<?php
namespace Deploy\Worker;

interface WorkableInterface
{
    const STATUS_CREATED = 'Created';
    const STATUS_WAITING  = 'Waiting';
    const STATUS_DOING  = 'Doing';
    const STATUS_ERROR  = 'Error';
    const STATUS_SUCCESS  = 'Success';
    const STATUS_KILLED  = 'Killed';

    public function getWorkClass();
    public function getWorkIdentify();
    public function setStatus($status);
}
