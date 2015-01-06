<?php
namespace Deploy\Account;

use Eloquent;

class Team extends Eloquent
{
    protected $table = 'teams';

    protected $guarded = array();

    public function users()
    {
        return $this->belongsToMany('Deploy\Account\User', 'team_user', 'team_id', 'user_id');
    }

    public function repos()
    {
        return $this->belongsToMany('Deploy\Account\Repo', 'team_repo', 'team_id', 'repo_id');
    }
}
