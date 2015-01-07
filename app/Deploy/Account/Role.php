<?php
namespace Deploy\Account;

use Eloquent;

class Role extends Eloquent
{
    const TYPE_SYSTEM = 'system';
    const TYPE_USER = 'user';

    protected $table = 'roles';

    protected $guarded = array('id');

}
