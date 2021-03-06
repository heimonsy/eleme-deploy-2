<?php
namespace Deploy\Site;

use Crypt;
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

    public function builds()
    {
        return $this->hasMany('Deploy\Site\Build', 'site_id', 'id');
    }

    public function host_types()
    {
        return $this->hasMany('Deploy\Hosts\HostType', 'site_id', 'id');
    }

    public function commits()
    {
        return $this->hasMany('Deploy\Site\Commit', 'site_id', 'id');
    }

    public function deploy_config()
    {
        return $this->hasOne('Deploy\Site\DeployConfig', 'site_id', 'id');
    }

    public function setPullKeyPassphraseAttribute($value)
    {
        if ($value != '******') {
            $this->attributes['pull_key_passphrase'] = $value === '' ? '' : Crypt::encrypt($value);
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
            $this->attributes['pull_key'] = $value === '' ? '' : Crypt::encrypt($value);
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

    public function setGithubTokenAttribute($value)
    {
        if ($value != '******') {
            $this->attributes['github_token'] = $value;
        }
    }

    public function getGithubTokenAttribute($value)
    {
        if (!empty($value)) {
            $value = '******';
        }
        return $value;
    }

    public function realPullKey()
    {
        $value = $this->attributes['pull_key'];
        return $value == '' ? '' : Crypt::decrypt($value);
    }

    public function realPullKeyPassphrase()
    {
        $value = $this->attributes['pull_key_passphrase'];
        return $value == '' ? '' : Crypt::decrypt($value);
    }

    public function realHipchatToken()
    {
        $value = $this->attributes['hipchat_token'];
        return $value;
    }

    public function realGithubToken()
    {
        return $this->attributes['github_token'];
    }

    public function watchers()
    {
        return $this->belongsToMany('Deploy\Account\User', 'watchs', 'site_id', 'user_id');
    }

    public function repoName()
    {
        if (preg_match('/github\.com:(.+?)\.git$/i', $this->repo_git, $matchs)) {
            return $matchs[1];
        }
        return null;
    }
}
