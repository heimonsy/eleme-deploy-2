<?php
namespace Deploy\Site;

use Log;
use Crypt;
use Eloquent;
use Deploy\Facade\Worker;
use Deploy\Worker\Job;

class DeployConfig extends Eloquent
{
    protected $table = 'deploy_configs';

    protected $guarded = array('id');

    public function site()
    {
        return $this->belongsTo('Deploy\Site\Site', 'site_id', 'id');
    }

    public function setDeployKeyPassphraseAttribute($value)
    {
        if ($value != '******') {
            $this->attributes['deploy_key_passphrase'] = $value === '' ? '' : Crypt::encrypt($value);
        }
    }

    public function getDeployKeyPassphraseAttribute($value)
    {
        if (!empty($value)) {
            $value = '******';
        }

        return $value;
    }

    public function setDeployKeyAttribute($value)
    {
        if ($value != '******') {
            $this->attributes['deploy_key'] = $value === '' ? '' : Crypt::encrypt($value);
        }
    }

    public function getDeployKeyAttribute($value)
    {
        if (!empty($value)) {
            $value = '******';
        }

        return $value;
    }

    public function realDeployKey()
    {

        $value = $this->attributes['deploy_key'];
        return $value == '' ? '' : Crypt::decrypt($value);
    }

    public function realDeployKeyPassphrase()
    {
        $value = $this->attributes['deploy_key_passphrase'];
        return $value == '' ? '' : Crypt::decrypt($value);
    }
}

