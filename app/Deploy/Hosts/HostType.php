<?php
namespace Deploy\Hosts;

use Eloquent;

class HostType extends Eloquent
{
    const DEPLOY_TYPE_APP = 'APP';
    const DEPLOY_TYPE_STATIC = 'STAIC';

    protected $table = 'host_types';

    protected $guarded = array('id');

    public function catalog()
    {
        return $this->belongsTo('Deploy\Hosts\HostTypeCatalog', 'catalog_id', 'id');
    }

    public function hosts()
    {
        return $this->hasMany('Deploy\Hosts\Host', 'host_type_id', 'id');
    }

    public function site()
    {
        return $this->belongsTo('Deploy\Site\Site', 'site_id', 'id');
    }

    public function toArray()
    {
        return array_merge(parent::toArray(), array('access_protected' => $this->catalog->accessAction()));
    }
}
