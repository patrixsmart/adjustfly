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
    */

    'model' => Patrixsmart\Adjustfly\Models\Adjustment::class,

    'table' => 'adjustments',

    /*
    |--------------------------------------------------------------------------
    | Morph Key Type
    |--------------------------------------------------------------------------
    |
    | The key type used by the models you are tracking. Supported values are
    | "id" (auto-incrementing), "uuid" and "ulid". This is only read by the
    | package migration, so change it before running `php artisan migrate`.
    |
    */

    'morph_key_type' => 'id',

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
    | resolve it, the foreign key column, and that column's type ("id",
    | "uuid", "ulid" or "string").
    |
    */

    'user' => [
        'model' => null, // Defaults to config('auth.providers.users.model')
        'guard' => null, // Defaults to the application's default guard
        'foreign_key' => 'user_id',
        'key_type' => 'id',
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
