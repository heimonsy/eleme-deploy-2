<?php
namespace Deploy\Site;

use Eloquent;

class Build extends Eloquent
{
    const STATUS_WAITING = 'Waiting';
    const STATUS_BUILDING = 'Buiding';
    const STATUS_SUCCESS = 'Success';
    const STATUS_ERROR = 'Error';

    protected $table = 'builds';

    protected $guarded = array('id');

    public function commit()
    {
        return $this->belongsTo('Deplooy\Site\Commit');
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
}
