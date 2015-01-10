<?php
namespace Deploy\Sentry;

trait PassportTrait
{
    public function permissions()
    {
        return $this->morphMany('Deploy\Sentry\Permission', 'passable');
    }
}
