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
        return '管理项目: ' . $this->name;
    }

    public function accessDescription()
    {
        return '发布项目: ' . $this->name;
    }

    public function toArray()
    {
        return array_merge(parent::toArray(), array(
            'access_protected' => $this->accessAction(),
            'manage_protected' => $this->manageAction()
        ));
    }

    public function host_types()
    {
        return $this->hasMany('Deploy\Hosts\HostType', 'site_id', 'id');
    }

    public function deploy_config()
    {
        return $this->hasOne('Deploy\Site\DeployConfig', 'site_id', 'id');
    }

    public function setPullKeyPassphraseAttribute($value)
    {
        if ($value != '******') {
            $this->attributes['pull_key_passphrase'] = $value;
        }
    }

    public function getPullKeyPassphraseAttribute($value)
    {
        if (!empty($value)) {
            $value = '******';
        }

        return $value;
    }

    public function setPullKeyAttribute($value)
    {
        if ($value != '******') {
            $this->attributes['pull_key'] = $value;
        }
    }

    public function getPullKeyAttribute($value)
    {
        if (!empty($value)) {
            $value = '******';
        }

        return $value;
    }

    public function setHipchatTokenAttribute($value)
    {
        if ($value != '******') {
            $this->attributes['hipchat_token'] = $value;
        }
    }

    public function getHipchatTokenAttribute($value)
    {
        if (!empty($value)) {
            $value = '******';
        }
        return $value;
    }
}
