<?php

namespace Scriptotek\PrimoSearch\Laravel;

use Scriptotek\PrimoSearch\Primo;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/primo.php' => config_path('primo.php'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/primo.php',
            'primo'
        );

        $this->app->singleton(Primo::class, function ($app) {
            return new Primo($app['config']->get('primo'));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [Primo::class];
    }
}
