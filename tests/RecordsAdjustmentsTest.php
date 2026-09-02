<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Tests;

use Illuminate\Support\Facades\Event;
use Patrixsmart\Adjustfly\Events\AdjustmentRecorded;
use Patrixsmart\Adjustfly\Models\Adjustment;
use Patrixsmart\Adjustfly\Tests\Fixtures\Post;
use PHPUnit\Framework\Attributes\Test;

class RecordsAdjustmentsTest extends TestCase
{
    #[Test]
    public function it_records_an_adjustment_when_a_model_is_updated(): void
    {
        $post = Post::create(['title' => 'Original', 'body' => 'Body']);

        $post->update(['title' => 'Updated']);

        $this->assertCount(1, $post->adjustments);

        $adjustment = $post->adjustments()->first();

        $this->assertSame('updated', $adjustment->event);
        $this->assertSame(['title' => 'Original'], $adjustment->before);
        $this->assertSame(['title' => 'Updated'], $adjustment->after);
    }

    #[Test]
    public function before_and_after_are_cast_to_arrays(): void
    {
        $post = Post::create(['title' => 'Original']);
        $post->update(['title' => 'Updated']);

        $this->assertIsArray($post->adjustments()->first()->before);
    }

    #[Test]
    public function it_does_not_record_when_nothing_meaningful_changed(): void
    {
        $post = Post::create(['title' => 'Original']);

        $post->update(['title' => 'Original']);
        $post->touch();

        $this->assertCount(0, $post->adjustments()->get());
    }

    #[Test]
    public function it_excludes_globally_and_locally_excluded_attributes(): void
    {
        $post = Post::create(['title' => 'Original', 'secret' => 'a']);

        $post->update(['title' => 'Updated', 'secret' => 'b']);

        $adjustment = $post->adjustments()->first();

        $this->assertArrayNotHasKey('secret', $adjustment->after);
        $this->assertArrayNotHasKey('updated_at', $adjustment->after);
        $this->assertSame(['title'], $adjustment->changedAttributes());
    }

    #[Test]
    public function it_can_suppress_recording(): void
    {
        $post = Post::create(['title' => 'Original']);

        Post::withoutAdjustments(function () use ($post): void {
            $post->update(['title' => 'Updated']);
        });

        $this->assertCount(0, $post->adjustments()->get());

        $post->update(['title' => 'Recorded again']);

        $this->assertCount(1, $post->adjustments()->get());
    }

    #[Test]
    public function it_fires_an_event_when_an_adjustment_is_recorded(): void
    {
        Event::fake([AdjustmentRecorded::class]);

        $post = Post::create(['title' => 'Original']);
        $post->update(['title' => 'Updated']);

        Event::assertDispatched(AdjustmentRecorded::class);
    }

    #[Test]
    public function it_does_not_run_an_extra_query_to_build_the_payload(): void
    {
        $post = Post::create(['title' => 'Original']);
        $post->title = 'Updated';

        $properties = $post->adjustedProperties();

        $this->assertSame(['title' => 'Original'], $properties['before']);
        $this->assertSame(['title' => 'Updated'], $properties['after']);
    }

    #[Test]
    public function it_scopes_adjustments_to_an_adjustable(): void
    {
        $post = Post::create(['title' => 'Original']);
        $post->update(['title' => 'Updated']);

        Post::create(['title' => 'Other'])->update(['title' => 'Other updated']);

        $this->assertCount(1, Adjustment::query()->forAdjustable($post)->get());
        $this->assertCount(2, Adjustment::query()->forType($post->getMorphClass())->get());
    }
}
