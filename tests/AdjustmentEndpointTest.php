<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Tests;

use Illuminate\Support\Facades\Gate;
use Patrixsmart\Adjustfly\Models\Adjustment;
use Patrixsmart\Adjustfly\Tests\Fixtures\AdjustmentPolicy;
use Patrixsmart\Adjustfly\Tests\Fixtures\Post;
use Patrixsmart\Adjustfly\Tests\Fixtures\User;
use PHPUnit\Framework\Attributes\Test;

class AdjustmentEndpointTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('adjustfly.routes.enabled', true);
        $app['config']->set('adjustfly.routes.middleware', ['api']);
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Gate::policy(Adjustment::class, AdjustmentPolicy::class);
    }

    #[Test]
    public function an_admin_can_list_adjustments(): void
    {
        $post = Post::create(['title' => 'Original']);
        $post->update(['title' => 'Updated']);

        $admin = User::create(['is_admin' => true]);

        $this->actingAs($admin)
            ->getJson('/api/adjustments')
            ->assertOk()
            ->assertJsonPath('data.0.event', 'updated')
            ->assertJsonPath('data.0.after.title', 'Updated')
            ->assertJsonPath('data.0.changed.0', 'title');
    }

    #[Test]
    public function a_non_admin_is_forbidden(): void
    {
        $user = User::create(['is_admin' => false]);

        $this->actingAs($user)->getJson('/api/adjustments')->assertForbidden();
    }

    #[Test]
    public function an_admin_can_view_a_single_adjustment(): void
    {
        $post = Post::create(['title' => 'Original']);
        $post->update(['title' => 'Updated']);

        $adjustment = $post->adjustments()->first();
        $admin = User::create(['is_admin' => true]);

        $this->actingAs($admin)
            ->getJson("/api/adjustments/{$adjustment->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $adjustment->id)
            ->assertJsonPath('data.before.title', 'Original');
    }

    #[Test]
    public function a_missing_adjustment_returns_404(): void
    {
        $admin = User::create(['is_admin' => true]);

        $this->actingAs($admin)->getJson('/api/adjustments/999999')->assertNotFound();
    }

    #[Test]
    public function per_page_is_capped(): void
    {
        $admin = User::create(['is_admin' => true]);

        $this->actingAs($admin)
            ->getJson('/api/adjustments?per_page=99999')
            ->assertStatus(422);
    }

    #[Test]
    public function the_acting_user_is_credited_and_linked_back(): void
    {
        $admin = User::create(['is_admin' => true]);
        $this->actingAs($admin);

        $post = Post::create(['title' => 'Original']);
        $post->update(['title' => 'Updated']);

        $adjustment = $post->adjustments()->first();

        $this->assertSame($admin->id, $adjustment->user_id);
        $this->assertTrue($adjustment->user->is($admin));
        $this->assertCount(1, $admin->ownedAdjustments);
    }

    #[Test]
    public function latest_adjustment_returns_the_most_recent_one(): void
    {
        $post = Post::create(['title' => 'One']);
        $post->update(['title' => 'Two']);
        $post->update(['title' => 'Three']);

        $this->assertSame('Three', $post->latestAdjustment->after['title']);
        $this->assertSame('Three', Post::with('latestAdjustment')->find($post->id)->latestAdjustment->after['title']);
    }
}
