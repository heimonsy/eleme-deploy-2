<?php
namespace Deploy\Sentry;

use Illuminate\Support\ServiceProvider;

class SentryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bindShared('sentry', function ($app) {
            return new Sentry(
                new Login()
            );
        });
    }
}
