<?php
namespace Deploy\Hosts;

use Eloquent;
use Deploy\Sentry\AccessProtectedInterface;
use Deploy\Sentry\AccessProtectedTrait;
use Deploy\Traits\AllObjectsTrait;

class HostTypeCatalog extends Eloquent implements AccessProtectedInterface
{
    use AllObjectsTrait;
    use AccessProtectedTrait;

    protected $table = 'host_type_catalogs';

    protected $guarded = array('id');

    public $timestamps = false;

    public function identify()
    {
        return 'host_type_catalog_' . $this->id;
    }

    public function accessDescription()
    {
        return '发布到环境: ' . $this->name;
    }

    public function toArray()
    {
        return array_merge(parent::toArray(), array('access_protected' => $this->accessAction()));
    }
}
