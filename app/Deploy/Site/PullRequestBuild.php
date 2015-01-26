<?php
namespace Deploy\Site;

use Eloquent;

class PullRequestBuild extends Eloquent
{
    const PR_STATUS_OPEN = 'open';
    const PR_STATUS_CLOSED = 'closed';

    const STATUS_WAITING = 'Waiting';
    const STATUS_DOING = 'Doing';
    const STATUS_ERROR = 'Error';
    const STATUS_SUCCESS = 'Success';
    const STATUS_FATAL = 'Fatal';
    const STATUS_ABORT = 'Abort';

    protected $table = 'pull_request_builds';

    protected $guarded = array('id');

    public function site()
    {
        return $this->belongsTo('Deploy\Site\Site', 'site_id', 'id');
    }

    public function job()
    {
        return $this->belongsTo('Deploy\Worker\Job', 'job_id', 'id');
    }

    public function scopeOf($query, Site $site)
    {
        return $query->where('site_id', '=', $site->id);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', '=', self::PR_STATUS_OPEN);
    }

    public function scopeSuccess($query)
    {
        return $query->where('build_status', '=', self::STATUS_SUCCESS)->where('test_status', '=', self::STATUS_SUCCESS);
    }

    public function setStatus($status)
    {
        $this->status = $status;
        $this->save();
        return $this;
    }

    public function setCommandStatus($build, $test)
    {
        if ($build !== null) {
            $this->build_status = $build;
        }
        if ($test !== null) {
            $this->test_status = $test;
        }
        $this->save();
        return $this;
    }
}
