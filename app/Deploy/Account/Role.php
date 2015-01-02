<?php
namespace Deploy\Account;

use Eloquent;

class Role extends Eloquent
{
    protected $table = 'roles';

    protected $guarded = array('id');

    public $timestamps = false;
}
