<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Tests;

use Illuminate\Support\Facades\Schema;
use Patrixsmart\Adjustfly\Tests\Fixtures\Post;
use PHPUnit\Framework\Attributes\Test;

/**
 * Runs the real package migration with a renamed user column, so the column,
 * the foreign key and the index all have to agree.
 */
class CustomForeignKeyTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('adjustfly.user.foreign_key', 'actor_id');
    }

    #[Test]
    public function the_migration_builds_the_renamed_column(): void
    {
        $this->assertTrue(Schema::hasColumn('adjustments', 'actor_id'));
        $this->assertFalse(Schema::hasColumn('adjustments', 'user_id'));
    }

    #[Test]
    public function the_index_follows_the_renamed_column(): void
    {
        // An index naming a column that does not exist is a hard error on MySQL
        // and PostgreSQL, but SQLite silently rewrites the unresolved
        // double-quoted identifier into a string literal and indexes that
        // constant instead. Such an index reports no columns, so assert on the
        // indexed columns rather than trusting the migration to have thrown.
        $indexed = array_merge(
            ...array_map(
                static fn (array $index): array => $index['columns'],
                Schema::getIndexes('adjustments')
            )
        );

        $this->assertContains('actor_id', $indexed);
        $this->assertNotContains('user_id', $indexed);
    }

    #[Test]
    public function the_configured_foreign_key_is_used_when_recording(): void
    {
        $post = Post::create(['title' => 'Original']);
        $post->update(['title' => 'Updated']);

        $adjustment = $post->adjustments()->first();

        $this->assertArrayHasKey('actor_id', $adjustment->getAttributes());
        $this->assertArrayNotHasKey('user_id', $adjustment->getAttributes());
    }
}
