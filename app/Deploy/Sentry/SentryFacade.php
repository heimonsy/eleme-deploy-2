<?php
namespace Deploy\Sentry;

use Illuminate\Support\Facades\Facade;

class SentryFacade extends Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'sentry';
    }
}
