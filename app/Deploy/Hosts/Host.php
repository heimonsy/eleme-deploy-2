<?php
namespace Deploy\Hosts;

use Eloquent;

class Host extends Eloquent
{
    const TYPE_APP = 'APP';
    const TYPE_STATIC = 'STATIC';

    protected $table = 'hosts';

    protected $guarded = array('id');

    public function host_type()
    {
        return $this->belongsTo('Deploy\Hosts\HostType', 'host_type_id', 'id');
    }

    public function host_type_catalog()
    {
        return $this->host_type()->first()->catalog();
    }

    public function site()
    {
        return $this->belongsTo('Deploy\Site\Site', 'site_id', 'id');
    }

    public function scopeApp($query)
    {
        return $query->where('type', self::TYPE_APP);
    }

    public function scopeStatic($query)
    {
        return $query->where('type', self::TYPE_STATIC);
    }

    public function isApp()
    {
        return self::TYPE_APP === $this->type;
    }

    public function isStatic()
    {
        return self::TYPE_STATIC === $this->type;
    }
}
