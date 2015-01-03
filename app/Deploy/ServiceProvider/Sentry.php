<?php
namespace Deploy\ServiceProvider;

use Illuminate\Support\ServiceProvider;
use Deploy\Sentry\Login;

class Sentry extends ServiceProvider
{
    public function register()
    {
        $this->app->bindShared('sentry', function ($app) {
            return new \Deploy\Sentry\Sentry(
                new Login()
            );
        });
    }
}
