<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Tests;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;

class EnabledRoutesTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('adjustfly.routes.enabled', true);
        $app['config']->set('adjustfly.routes.middleware', ['api']);
    }

    #[Test]
    public function routes_are_registered_when_enabled(): void
    {
        $this->assertNotNull(Route::getRoutes()->getByName('adjustfly.adjustments.index'));
    }

    #[Test]
    public function an_unauthenticated_visitor_cannot_read_adjustments(): void
    {
        $this->getJson('/api/adjustments')->assertForbidden();
    }
}
