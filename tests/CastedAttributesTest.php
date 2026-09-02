<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Tests;

use Patrixsmart\Adjustfly\Tests\Fixtures\Post;
use PHPUnit\Framework\Attributes\Test;

class CastedAttributesTest extends TestCase
{
    #[Test]
    public function cast_attributes_are_recorded_consistently_on_both_sides(): void
    {
        $post = Post::create(['title' => 'T', 'meta' => ['a' => 1]]);

        $post->update(['meta' => ['a' => 2]]);

        $adjustment = $post->adjustments()->first();

        $this->assertSame(['a' => 1], $adjustment->before['meta']);
        $this->assertSame(['a' => 2], $adjustment->after['meta']);
    }

    #[Test]
    public function date_casts_are_recorded_consistently_on_both_sides(): void
    {
        $post = Post::create(['title' => 'T', 'published_at' => '2026-01-01 10:00:00']);

        $post->update(['published_at' => '2026-02-02 11:00:00']);

        $adjustment = $post->adjustments()->first();

        // Both sides must serialise through the datetime cast, not one cast and
        // one raw string, so the two values are directly comparable.
        $this->assertMatchesRegularExpression('/^2026-01-01T10:00:00/', $adjustment->before['published_at']);
        $this->assertMatchesRegularExpression('/^2026-02-02T11:00:00/', $adjustment->after['published_at']);
    }
}
