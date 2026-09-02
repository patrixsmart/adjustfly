<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Tests;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

class MigrationsNotAutoLoadedTest extends TestCase
{
    /**
     * Deliberately does not load the package migrations. If the service
     * provider registered them itself, `php artisan migrate` would still create
     * the table and this test would fail.
     */
    protected function defineDatabaseMigrations(): void
    {
        // no-op
    }

    #[Test]
    public function the_package_does_not_register_its_own_migrations(): void
    {
        $this->assertFalse(
            Schema::hasTable('adjustments'),
            'The adjustments table was created without being published, so the package is auto-loading its migrations.'
        );
    }
}
