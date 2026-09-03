<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use Patrixsmart\Adjustfly\AdjustflyServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [AdjustflyServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $schema = $this->app['db']->connection()->getSchemaBuilder();

        $schema->create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('secret')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        $schema->create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('module');
            $table->string('key');
            $table->string('value')->nullable();
            $table->timestamps();
        });

        $schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Tester');
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });
    }
}
