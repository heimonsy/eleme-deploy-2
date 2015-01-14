<?php
namespace Deploy\Hosts;

use Eloquent;

class Host extends Eloquent
{
    protected $table = 'hosts';

    protected $guarded = array('id');

    public function host_type()
    {
        return $this->belongsTo('Deploy\Hosts\HostType', 'host_type_id', 'id');
    }

    public function site()
    {
        return $this->belongsTo('Deploy\Site\Site', 'site_id', 'id');
    }
}
