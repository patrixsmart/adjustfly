<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Tests;

use Patrixsmart\Adjustfly\Models\Adjustment;
use PHPUnit\Framework\Attributes\Test;

class PruneCommandTest extends TestCase
{
    private function seedAgedAdjustments(): void
    {
        Adjustment::factory()->create(['created_at' => now()->subDays(90)]);
        Adjustment::factory()->create(['created_at' => now()->subDays(45)]);
        Adjustment::factory()->create(['created_at' => now()->subDays(5)]);
    }

    #[Test]
    public function it_prunes_using_the_configured_retention_window(): void
    {
        config(['adjustfly.prune_after_days' => 30]);
        $this->seedAgedAdjustments();

        $this->artisan('adjustfly:prune')->assertSuccessful();

        $this->assertSame(1, Adjustment::query()->withTrashed()->count());
    }

    #[Test]
    public function the_days_option_overrides_the_configured_window(): void
    {
        config(['adjustfly.prune_after_days' => 365]);
        $this->seedAgedAdjustments();

        $this->artisan('adjustfly:prune', ['--days' => 60])->assertSuccessful();

        $this->assertSame(2, Adjustment::query()->withTrashed()->count());
    }

    #[Test]
    public function pretend_reports_without_deleting(): void
    {
        config(['adjustfly.prune_after_days' => 30]);
        $this->seedAgedAdjustments();

        $this->artisan('adjustfly:prune', ['--pretend' => true])->assertSuccessful();

        $this->assertSame(3, Adjustment::query()->withTrashed()->count());
    }

    #[Test]
    public function it_does_nothing_when_retention_is_disabled(): void
    {
        config(['adjustfly.prune_after_days' => null]);
        $this->seedAgedAdjustments();

        $this->artisan('adjustfly:prune')
            ->expectsOutputToContain('Pruning is disabled')
            ->assertSuccessful();

        $this->assertSame(3, Adjustment::query()->withTrashed()->count());
    }

    #[Test]
    public function it_rejects_an_invalid_days_option(): void
    {
        config(['adjustfly.prune_after_days' => 30]);
        $this->seedAgedAdjustments();

        $this->artisan('adjustfly:prune', ['--days' => 'soon'])->assertFailed();

        $this->assertSame(3, Adjustment::query()->withTrashed()->count());
    }

    #[Test]
    public function it_prunes_a_custom_configured_model(): void
    {
        config([
            'adjustfly.prune_after_days' => 30,
            'adjustfly.model' => CustomAdjustment::class,
        ]);
        $this->seedAgedAdjustments();

        $this->artisan('adjustfly:prune')->assertSuccessful();

        $this->assertSame(1, Adjustment::query()->withTrashed()->count());
    }
}

class CustomAdjustment extends Adjustment {}
