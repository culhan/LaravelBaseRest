<?php

namespace KhanCode\LaravelBaseRest;

use Illuminate\Support\ServiceProvider;

class LaravelBaseRestServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'khancode');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'khancode');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravelbaserest.php', 'laravelbaserest');

        // Register the service the package provides.
        $this->app->singleton('laravelbaserest', function ($app) {
            return new LaravelBaseRest;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['laravelbaserest'];
    }
    
    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/laravelbaserest.php' => config_path('laravelbaserest.php'),
        ], 'laravelbaserest.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/khancode'),
        ], 'laravelbaserest.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/khancode'),
        ], 'laravelbaserest.views');*/

        // Publishing the translation files.
        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/khancode'),
        ], 'laravelbaserest.views');

        // Registering package commands.
        // $this->commands([]);
    }
}
