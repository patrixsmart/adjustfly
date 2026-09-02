<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Tests;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

class SchemaTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('adjustfly.morph_key_type', 'uuid');
        $app['config']->set('adjustfly.user.key_type', 'ulid');
        $app['config']->set('adjustfly.user.foreign_key', 'actor_id');
    }

    #[Test]
    public function the_migration_honours_the_configured_key_types(): void
    {
        $this->assertTrue(Schema::hasColumn('adjustments', 'adjustable_id'));
        $this->assertTrue(Schema::hasColumn('adjustments', 'actor_id'));
        $this->assertFalse(Schema::hasColumn('adjustments', 'user_id'));

        $this->assertTrue(Schema::hasColumns('adjustments', [
            'event', 'before', 'after', 'ip_address', 'user_agent', 'deleted_at',
        ]));
    }

    #[Test]
    public function the_configured_foreign_key_is_used_when_recording(): void
    {
        $post = Fixtures\Post::create(['title' => 'Original']);
        $post->update(['title' => 'Updated']);

        $adjustment = $post->adjustments()->first();

        $this->assertArrayHasKey('actor_id', $adjustment->getAttributes());
        $this->assertArrayNotHasKey('user_id', $adjustment->getAttributes());
    }
}
