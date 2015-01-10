<?php
namespace Deploy\Site;

use Eloquent;
use Deploy\Sentry\AccessProtectedInterface;
use Deploy\Sentry\AccessProtectedTrait;
use Deploy\Sentry\ManageProtectedInterface;
use Deploy\Sentry\ManageProtectedTrait;
use Deploy\Traits\AllObjectsTrait;

class Site extends Eloquent implements AccessProtectedInterface, ManageProtectedInterface
{
    use AllObjectsTrait;
    use AccessProtectedTrait;
    use ManageProtectedTrait;

    protected $table = 'sites';

    protected $guarded = array('id');

    public function identify()
    {
        return 'site_' . $this->id;
    }

    public function manageDescription()
    {
        return '管理项目' . $this->name;
    }

    public function accessDescription()
    {
        return '发布项目' . $this->name;
    }
}
