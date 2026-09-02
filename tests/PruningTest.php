<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Tests;

use Patrixsmart\Adjustfly\Models\Adjustment;
use PHPUnit\Framework\Attributes\Test;

class PruningTest extends TestCase
{
    #[Test]
    public function it_prunes_adjustments_older_than_the_retention_window(): void
    {
        config(['adjustfly.prune_after_days' => 30]);

        Adjustment::factory()->create(['created_at' => now()->subDays(90)]);
        Adjustment::factory()->create(['created_at' => now()->subDays(5)]);

        $this->artisan('model:prune', ['--model' => [Adjustment::class]])->assertSuccessful();

        $this->assertSame(1, Adjustment::query()->withTrashed()->count());
    }

    #[Test]
    public function it_prunes_nothing_when_retention_is_disabled(): void
    {
        config(['adjustfly.prune_after_days' => null]);

        Adjustment::factory()->create(['created_at' => now()->subYears(5)]);

        $this->artisan('model:prune', ['--model' => [Adjustment::class]])->assertSuccessful();

        $this->assertSame(1, Adjustment::query()->withTrashed()->count());
    }
}
