<?php
namespace Deploy\Sentry;

use Eloquent;

class Permission extends Eloquent
{
    protected $table = 'permissions';
    protected $guarded = array('id');

    public $timestamps = false;

    public function passable()
    {
         return $this->morphTo();
    }
}
