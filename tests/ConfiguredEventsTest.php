<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Tests;

use Patrixsmart\Adjustfly\Tests\Fixtures\Post;
use PHPUnit\Framework\Attributes\Test;

class ConfiguredEventsTest extends TestCase
{
    #[Test]
    public function creation_is_not_recorded_by_default(): void
    {
        $post = Post::create(['title' => 'Original']);

        $this->assertCount(0, $post->adjustments()->get());
    }

    #[Test]
    public function creation_is_recorded_when_the_event_is_enabled(): void
    {
        config(['adjustfly.events' => ['created', 'updating', 'deleted']]);

        $post = Post::create(['title' => 'Original']);

        $adjustment = $post->adjustments()->first();

        $this->assertSame('created', $adjustment->event);
        $this->assertSame([], $adjustment->before);
        $this->assertSame('Original', $adjustment->after['title']);
        $this->assertArrayNotHasKey('secret', $adjustment->after);
    }

    #[Test]
    public function deletion_is_recorded_when_the_event_is_enabled(): void
    {
        config(['adjustfly.events' => ['deleted']]);

        $post = Post::create(['title' => 'Original']);
        $post->delete();

        $adjustment = $post->adjustments()->first();

        $this->assertSame('deleted', $adjustment->event);
        $this->assertSame('Original', $adjustment->before['title']);
        $this->assertSame([], $adjustment->after);
    }

    #[Test]
    public function nothing_is_recorded_when_automatic_recording_is_off(): void
    {
        config(['adjustfly.record_automatically' => false]);

        $post = Post::create(['title' => 'Original']);
        $post->update(['title' => 'Updated']);

        $this->assertCount(0, $post->adjustments()->get());

        // ...but the manual API still works.
        $post->title = 'Manual';
        $this->assertNotNull($post->recordAdjustment());
    }
}
