<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Tests;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;

class RoutesTest extends TestCase
{
    #[Test]
    public function adjustment_routes_are_not_registered_by_default(): void
    {
        $this->assertNull(Route::getRoutes()->getByName('adjustfly.adjustments.index'));

        $this->get('/api/adjustments')->assertNotFound();
    }
}
