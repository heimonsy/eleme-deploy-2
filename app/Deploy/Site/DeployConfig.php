<?php
namespace Deploy\Site;

use Eloquent;

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
            $this->attributes['deploy_key_passphrase'] = $value;
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
            $this->attributes['deploy_key'] = $value;
        }
    }

    public function getDeployKeyAttribute($value)
    {
        if (!empty($value)) {
            $value = '******';
        }

        return $value;
    }

}

