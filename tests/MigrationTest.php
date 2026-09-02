<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Tests;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

class MigrationTest extends TestCase
{
    #[Test]
    public function the_published_migration_creates_the_expected_schema(): void
    {
        $this->assertTrue(Schema::hasColumns('adjustments', [
            'id',
            'adjustable_type',
            'adjustable_id',
            'user_id',
            'event',
            'before',
            'after',
            'ip_address',
            'user_agent',
            'created_at',
            'updated_at',
            'deleted_at',
        ]));
    }
}
