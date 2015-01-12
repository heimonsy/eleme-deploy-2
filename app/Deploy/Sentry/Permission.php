<?php
namespace Deploy\Sentry;

use Eloquent;
use Deploy\Site\Site;
use Deploy\Hosts\HostTypeCatalog;

class Permission extends Eloquent
{
    protected $table = 'permissions';
    protected $guarded = array('id');

    public $timestamps = false;

    public function passable()
    {
         return $this->morphTo();
    }

    public static function permissionList()
    {
        $list = Site::accessActionList();
        $list = array_merge($list, Site::manageActionList());
        $list = array_merge($list, HostTypeCatalog::accessActionList());

        return $list;
    }
}
