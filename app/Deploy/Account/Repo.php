<?php
namespace Deploy\Account;

use Eloquent;

class Repo extends Eloquent
{
    protected $table = 'repos';

    protected $guarded = array();

    public function teams()
    {
        return $this->belongsToMany('Deploy\Account\Team', 'team_repo', 'repo_id', 'team_id');
    }
}
