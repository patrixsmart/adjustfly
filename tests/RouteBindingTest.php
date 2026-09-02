<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Tests;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;

class RouteBindingTest extends TestCase
{
    #[Test]
    public function it_does_not_hijack_an_adjustment_route_parameter_in_the_host_app(): void
    {
        Route::middleware('api')->get('/my-adjustments/{adjustment}', fn (string $adjustment) => ['got' => $adjustment]);

        $this->getJson('/my-adjustments/hello')
            ->assertOk()
            ->assertJson(['got' => 'hello']);
    }
}
