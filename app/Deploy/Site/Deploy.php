<?php
namespace Deploy\Site;

use Eloquent;

class Deploy extends Eloquent
{
    const TYPE_DEPLOY = 'deploy';
    const TYPE_PR_DEPLOY = 'prdeploy';

    const STATUS_WAITING = 'Waiting';
    const STATUS_DEPLOYING = 'Deploying';
    const STATUS_ERROR = 'Error';
    const STATUS_SUCCESS = 'Success';
    const STATUS_KILL = 'Kill';

    const KIND_HOST_TYPE = 'type';
    const KIND_TYPE = 'type';

    protected $table = 'deploys';

    protected $guarded = array('id');

    public function setStatus($status)
    {
        $this->status = $status;
        $this->save();
    }

    public function isSuccess()
    {
        return $this->status == self::STATUS_SUCCESS;
    }

    public function increaseSuccess()
    {
        self::increment('success_hosts');
    }

    public function increaseError()
    {
        self::increment('error_hosts');
    }

    public function user()
    {
        return $this->belongsTo('Deploy\Account\User', 'user_id', 'id');
    }

    public function prevDeploy()
    {
        return self::where(array(
            'status' => self::STATUS_SUCCESS,
            'deploy_kind' => $this->deploy_kind,
            'deploy_to' => $this->deploy_to,
        ))->where('commit', '<>', $this->commit)->first();
    }
}
