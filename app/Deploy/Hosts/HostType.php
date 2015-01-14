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

    public function site()
    {
        return $this->belongsTo('Deploy\Site\Site', 'site_id', 'id');
    }
}
