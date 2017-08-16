<?php

namespace UncLibrary\SierraApi;

use Illuminate\Support\ServiceProvider;

class SierraApiProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $config = __DIR__.'/config.php';

        $this->publishes([$config => config_path('sierra_api.php')]);

        $this->mergeConfigFrom($config, 'sierra_api');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('sierra', function ($app) {
            return new SierraApi(
                $app['config']['sierra_api.key'],
                $app['config']['sierra_api.secret'],
                $app['config']['sierra_api.host'],
                $app['config']['sierra_api.path'],
                $app['session.store']);
        });
    }
}
