<?php
namespace Deploy\Hosts;

use Eloquent;

class HostTypeCatalog extends Eloquent
{
    protected $table = 'host_type_catalogs';

    protected $guarded = array('id');

    public $timestamps = false;
}
