<?php

namespace Patrixsmart\Adjustfly;

use Illuminate\Support\ServiceProvider;

class AdjustflyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/adjustfly.php', 'adjustfly'
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/adjustfly.php' => config_path('adjustfly.php')
        ],'adjustfly-config');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadRoutesFrom(__DIR__.'/../routes/adjustfly.php');

    }
}
