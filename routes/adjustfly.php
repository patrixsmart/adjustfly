<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Patrixsmart\Adjustfly\Http\Controllers\AdjustmentController;

/*
|--------------------------------------------------------------------------
| Adjustment Routes
|--------------------------------------------------------------------------
|
| Disabled by default. Enable them in config/adjustfly.php once you have an
| AdjustmentPolicy in place — adjustments contain the before/after state of
| your models and must never be publicly readable.
|
*/

Route::prefix((string) config('adjustfly.routes.prefix', 'api'))
    ->middleware((array) config('adjustfly.routes.middleware', ['api', 'auth']))
    ->name((string) config('adjustfly.routes.name', 'adjustfly.'))
    ->group(function (): void {
        Route::get('adjustments', [AdjustmentController::class, 'index'])
            ->name('adjustments.index');

        Route::get('adjustments/{adjustment}', [AdjustmentController::class, 'show'])
            ->name('adjustments.show');
    });
