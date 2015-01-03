<?php
namespace Deploy\Facade;

use Illuminate\Support\Facades\Facade;

class Sentry extends Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'sentry';
    }
}
