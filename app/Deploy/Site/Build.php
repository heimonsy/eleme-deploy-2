<?php
namespace Deploy\Site;

use Eloquent;

class Build extends Eloquent
{
    const STATUS_WAITING = 'Waiting';
    const STATUS_BUILDING = 'Building';
    const STATUS_SUCCESS = 'Success';
    const STATUS_ERROR = 'Error';

    protected $status_infos = array(
        self::STATUS_BUILDING => '正在 Build',
        self::STATUS_WAITING => '正在等待',
        self::STATUS_ERROR => 'Build 出错',
        self::STATUS_SUCCESS => 'Build 成功',
    );

    protected $table = 'builds';

    protected $guarded = array('id');

    public function job()
    {
        return $this->belongsTo('Deploy\Worker\Job', 'job_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('Deploy\Account\User', 'user_id', 'id');
    }

    public function site()
    {
        return $this->belongsTo('Deploy\Site\Site', 'site_id', 'id');
    }

    public function scopeOf($query, Site $site)
    {
        return $query->where('site_id', '=', $site->id);
    }

    public function setStatus($status, $info = null)
    {
        if ($info == null) {
            $info = $this->status_infos[$status];
        }
        $this->status = $status;
        $this->status_info = $info;
        $this->save();

        return $this;
    }
}
