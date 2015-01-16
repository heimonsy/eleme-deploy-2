<?php
namespace Deploy\Site;

use Eloquent;

class Commit extends Eloquent
{
    protected $table = 'commits';

    protected $guarded = array('id');

    public static function isCommit($commit)
    {
        return preg_match('/^[0-9a-f]{40}$/', $commit) === 1;
    }
}
