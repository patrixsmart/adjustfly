<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly;

use Illuminate\Support\ServiceProvider;

class AdjustflyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/adjustfly.php', 'adjustfly');
    }

    public function boot(): void
    {
        $this->registerPublishing();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (config('adjustfly.routes.enabled', false)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/adjustfly.php');
        }
    }

    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/adjustfly.php' => config_path('adjustfly.php'),
        ], 'adjustfly-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'adjustfly-migrations');
    }
}
