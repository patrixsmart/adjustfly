<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Tests;

use Patrixsmart\Adjustfly\Models\Adjustment;
use Patrixsmart\Adjustfly\Tests\Fixtures\Post;
use Patrixsmart\Adjustfly\Tests\Fixtures\Setting;
use PHPUnit\Framework\Attributes\Test;

class RecordLabelTest extends TestCase
{
    #[Test]
    public function it_stores_a_label_from_the_first_matching_attribute(): void
    {
        $post = Post::create(['title' => 'Original']);

        $post->update(['body' => 'Something else']);

        $this->assertSame('Original', $post->adjustments()->first()->adjustable_label);
    }

    #[Test]
    public function the_label_describes_the_record_as_the_change_left_it(): void
    {
        $post = Post::create(['title' => 'Original']);

        $post->update(['title' => 'Renamed']);

        // Recorded during "updating", when the new values are already on the
        // model - the label belongs to the state the change produced.
        $this->assertSame('Renamed', $post->adjustments()->first()->adjustable_label);
    }

    #[Test]
    public function a_model_may_name_itself(): void
    {
        $setting = Setting::create(['module' => 'billing', 'key' => 'currency', 'value' => 'NGN']);

        $setting->update(['value' => 'USD']);

        $this->assertSame('billing - currency', $setting->adjustments()->first()->adjustable_label);
    }

    #[Test]
    public function the_label_outlives_the_record(): void
    {
        config()->set('adjustfly.events', ['created', 'updating', 'deleted']);

        $setting = Setting::create(['module' => 'billing', 'key' => 'currency', 'value' => 'NGN']);
        $id = $setting->id;

        $setting->delete();

        // The point of storing it: nothing is left to resolve a name from.
        $this->assertNull(Setting::find($id));
        $this->assertSame(
            'billing - currency',
            Adjustment::query()->where('adjustable_id', $id)->latest('id')->first()->adjustable_label,
        );
    }

    #[Test]
    public function it_records_no_label_when_the_model_has_nothing_to_offer(): void
    {
        config()->set('adjustfly.label_attributes', ['nonexistent_attribute']);

        $post = Post::create(['title' => 'Original']);
        $post->update(['title' => 'Renamed']);

        $this->assertNull($post->adjustments()->first()->adjustable_label);
    }

    #[Test]
    public function the_attributes_it_looks_at_are_configurable(): void
    {
        config()->set('adjustfly.label_attributes', ['body', 'title']);

        $post = Post::create(['title' => 'Original', 'body' => 'The body wins']);
        $post->update(['title' => 'Renamed']);

        $this->assertSame('The body wins', $post->adjustments()->first()->adjustable_label);
    }

    #[Test]
    public function a_blank_attribute_is_skipped_rather_than_stored(): void
    {
        config()->set('adjustfly.label_attributes', ['body', 'title']);

        $post = Post::create(['title' => 'Original', 'body' => '   ']);
        $post->update(['title' => 'Renamed']);

        $this->assertSame('Renamed', $post->adjustments()->first()->adjustable_label);
    }

    #[Test]
    public function an_over_long_label_is_truncated_rather_than_failing_the_insert(): void
    {
        $post = Post::create(['title' => str_repeat('a', 400)]);

        $post->update(['body' => 'Anything']);

        $this->assertSame(255, mb_strlen((string) $post->adjustments()->first()->adjustable_label));
    }

    #[Test]
    public function the_label_is_recorded_for_every_event(): void
    {
        config()->set('adjustfly.events', ['created', 'updating', 'deleted']);

        $post = Post::create(['title' => 'Original']);
        $post->update(['title' => 'Renamed']);
        $post->delete();

        $labels = Adjustment::query()
            ->where('adjustable_type', Post::class)
            ->orderBy('id')
            ->pluck('adjustable_label')
            ->all();

        $this->assertSame(['Original', 'Renamed', 'Renamed'], $labels);
    }

    #[Test]
    public function the_label_is_exposed_by_the_resource(): void
    {
        $post = Post::create(['title' => 'Original']);
        $post->update(['title' => 'Renamed']);

        $resource = (new \Patrixsmart\Adjustfly\Http\Resources\AdjustmentResource(
            $post->adjustments()->first()
        ))->toArray(request());

        $this->assertSame('Renamed', $resource['adjustable_label']);
    }
}
