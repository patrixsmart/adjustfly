<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Adjustment Model & Table
    |--------------------------------------------------------------------------
    |
    | The Eloquent model used to persist adjustments, and the table it writes
    | to. Swap the model for your own subclass if you need extra behaviour.
    |
    | The migration is published, so if you change the table name here, rename
    | it in your published migration too — nothing keeps the two in sync.
    |
    */

    'model' => Patrixsmart\Adjustfly\Models\Adjustment::class,

    'table' => 'adjustments',

    /*
    |--------------------------------------------------------------------------
    | Automatic Recording
    |--------------------------------------------------------------------------
    |
    | When enabled, every model using the HasAdjustments trait records an
    | adjustment automatically on the listed events. Set to false to record
    | manually via $model->recordAdjustment().
    |
    | Supported events: "created", "updating", "deleted", "restored".
    |
    */

    'record_automatically' => true,

    'events' => ['updating'],

    /*
    |--------------------------------------------------------------------------
    | Attributes
    |--------------------------------------------------------------------------
    |
    | Attributes excluded from every recorded adjustment. Models may add to
    | this list with a $adjustmentExcluded property, or restrict recording to
    | a whitelist with $adjustmentOnly.
    |
    */

    'excluded_attributes' => [
        'password',
        'remember_token',
        'updated_at',
        'created_at',
        'deleted_at',
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Context
    |--------------------------------------------------------------------------
    |
    | Capture the IP address and user agent responsible for each adjustment.
    | Disable if you would rather not store this for privacy reasons.
    |
    */

    'capture_request_context' => true,

    /*
    |--------------------------------------------------------------------------
    | User
    |--------------------------------------------------------------------------
    |
    | The authenticatable model credited with an adjustment, the guard used to
    | resolve it, and the foreign key column on the adjustments table. If you
    | change the foreign key, rename the column in your published migration to
    | match.
    |
    */

    'user' => [
        'model' => null, // Defaults to config('auth.providers.users.model')
        'guard' => null, // Defaults to the application's default guard
        'foreign_key' => 'user_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Adjustments contain the before/after state of your models, so the routes
    | are DISABLED by default and gated behind the "viewAny"/"view" abilities
    | on the Adjustment model when enabled. Register an AdjustmentPolicy (or a
    | Gate) before turning them on.
    |
    */

    'routes' => [
        'enabled' => false,
        'prefix' => 'api',
        'name' => 'adjustfly.',
        'middleware' => ['api', 'auth'],
        'per_page' => 20,
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pruning
    |--------------------------------------------------------------------------
    |
    | Number of days adjustments are retained by `php artisan model:prune`.
    | Set to null to retain adjustments forever.
    |
    */

    'prune_after_days' => 365,

];
