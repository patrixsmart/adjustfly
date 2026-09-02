<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly;

use Illuminate\Support\ServiceProvider;
use Patrixsmart\Adjustfly\Console\PruneAdjustmentsCommand;

class AdjustflyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/adjustfly.php', 'adjustfly');
    }

    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerCommands();

        if (config('adjustfly.routes.enabled', false)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/adjustfly.php');
        }
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([PruneAdjustmentsCommand::class]);
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

        // Migrations are published rather than loaded from the package, so the
        // schema lives in the application's own repository: it is visible in
        // code review, editable before it is ever run, and never altered by a
        // routine `composer update` followed by `php artisan migrate`.
        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'adjustfly-migrations');
    }
}
