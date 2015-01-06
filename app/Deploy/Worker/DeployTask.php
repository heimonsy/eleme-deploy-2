<?php
namespace Deploy\Worker;

use Eloquent;

class DeployTask extends Eloquent
{
     const STATUS_WAITING = 'waiting';
     const STATUS_DEPLOYING = 'doing';
     const STATUS_SUCCESS = 'success';
     const STATUS_ERROR = 'error';

    protected $table = 'deploy_tasks';

    protected $guarded = array('id');

}
