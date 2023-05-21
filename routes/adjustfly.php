<?php

use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Adjustment Routes
|--------------------------------------------------------------------------
|
*/

Route::group([
    'namespace' => 'Patrixsmart\Adjustfly\Http\Controllers',
    'middleware' => 'web'
], function () {
    Route::apiResource('adjustments', 'AdjustmentController')->only(['index', 'show']);
});
