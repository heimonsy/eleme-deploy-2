<?php
namespace Deploy\Site;

use Eloquent;

class Deploy extends Eloquent
{
    const TYPE_DEPLOY = 'deploy';
    const TYPE_PR_DEPLOY = 'pr_deploy';

    const STATUS_WAITING = 'Waiting';
    const STATUS_DEPLOYING = 'Deploying';
    const STATUS_ERROR = 'Error';
    const STATUS_SUCCESS = 'Success';

    protected $table = 'deploys';

    protected $guarded = array('id');

}
