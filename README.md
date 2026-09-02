# Adjustfly

Record Laravel model adjustments — a lightweight, polymorphic audit trail for your Eloquent models.

Supports **Laravel 12 and 13**. Laravel 12 needs PHP 8.2+; Laravel 13 needs PHP 8.3+.

## Installation

```sh
composer require patrixsmart/adjustfly
```

Publish the config file and run the migration:

```sh
php artisan vendor:publish --tag="adjustfly-config"
php artisan migrate
```

If you need to customise the schema, publish the migration too:

```sh
php artisan vendor:publish --tag="adjustfly-migrations"
```

> **Publish the config *before* migrating.** The migration reads `morph_key_type`
> and `user.key_type` to pick the right column types for UUID/ULID models.

## Usage

### Track a model

Add the `HasAdjustments` trait. That is all — adjustments are recorded
automatically on every update.

```php
use Illuminate\Database\Eloquent\Model;
use Patrixsmart\Adjustfly\Traits\HasAdjustments;

class Student extends Model
{
    use HasAdjustments;
}
```

```php
$student->update(['name' => 'Ada Lovelace']);

$student->adjustments()->first();
// event:  "updated"
// before: ['name' => 'Alan Turing']
// after:  ['name' => 'Ada Lovelace']
// user_id, ip_address, user_agent, created_at
```

Nothing is written when an update changes no tracked attribute, so `touch()`
and no-op saves will not fill your table with empty rows.

Both sides of the payload store **cast** values, so an `array` column stays an
array and a `datetime` column serialises as ISO-8601 — `before` and `after` are
always directly comparable.

### Choose which events are recorded

```php
// config/adjustfly.php
'events' => ['created', 'updating', 'deleted', 'restored'],
```

Prefer to record by hand? Set `record_automatically => false` and call:

```php
$adjustment = $student->recordAdjustment();   // returns null when nothing changed
```

### Control which attributes are recorded

Globally, in `config/adjustfly.php`:

```php
'excluded_attributes' => ['password', 'remember_token', 'updated_at', 'created_at', 'deleted_at'],
```

Per model — add to the exclusion list, or restrict to a whitelist:

```php
class Student extends Model
{
    use HasAdjustments;

    protected array $adjustmentExcluded = ['internal_notes'];

    // ...or record nothing but these:
    protected array $adjustmentOnly = ['name', 'email', 'class_id'];
}
```

### Suppress recording temporarily

Useful in seeders, imports and data backfills:

```php
Student::withoutAdjustments(function () {
    Student::query()->update(['term' => '2026/1']);
});
```

### Attribute the change to a user

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Patrixsmart\Adjustfly\Traits\OwnedAdjustments;

class User extends Authenticatable
{
    use OwnedAdjustments;
}
```

```php
$user->ownedAdjustments;                 // everything this user changed
$user->paginatedOwnedAdjustments(25);
```

The acting user is resolved from the guard in `adjustfly.user.guard`
(the default guard when null), and is simply `null` for console and queue work.

### Querying

```php
use Patrixsmart\Adjustfly\Models\Adjustment;

$student->adjustments;                       // newest first
$student->latestAdjustment;                  // single most recent
$student->paginatedAdjustments(25);
$student->simplePaginatedAdjustments(25);

Adjustment::query()->forAdjustable($student)->get();
Adjustment::query()->forType(Student::class)->get();
Adjustment::query()->forUser($user)->get();

$adjustment->changedAttributes();            // ['name', 'email']
$adjustment->before;                         // cast to array
$adjustment->user;                           // the acting user
$adjustment->adjustable;                     // the adjusted model
```

### Reacting to adjustments

```php
use Patrixsmart\Adjustfly\Events\AdjustmentRecorded;

Event::listen(function (AdjustmentRecorded $event) {
    // $event->adjustment, $event->adjustable
});
```

### Pruning

Adjustments grow quickly. `prune_after_days` (default 365) plugs into
Laravel's scheduler:

```php
// bootstrap/app.php
->withSchedule(fn ($schedule) => $schedule->command('model:prune')->daily())
```

Set it to `null` to keep adjustments forever.

## HTTP routes

The package ships read-only `index`/`show` endpoints, **disabled by default**.
Adjustment rows contain the before/after state of your models, so exposing them
is an explicit, deliberate choice.

To enable them:

**1. Write a policy and register it:**

```php
use Patrixsmart\Adjustfly\Models\Adjustment;

class AdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Adjustment $adjustment): bool
    {
        return $user->isAdmin();
    }
}

// AppServiceProvider::boot()
Gate::policy(Adjustment::class, AdjustmentPolicy::class);
```

**2. Turn the routes on:**

```php
// config/adjustfly.php
'routes' => [
    'enabled' => true,
    'prefix' => 'api',
    'middleware' => ['api', 'auth:sanctum'],
],
```

Without a policy Laravel denies by default, so the endpoints return `403` rather
than leaking data.

```
GET /api/adjustments?adjustable_type=...&adjustable_id=5&event=updated&per_page=25
GET /api/adjustments/{adjustment}
```

## Configuration reference

| Key | Default | Purpose |
| --- | --- | --- |
| `model` | `Adjustment::class` | Swap in your own subclass |
| `table` | `adjustments` | Table name |
| `morph_key_type` | `id` | `id`, `uuid` or `ulid` — for tracked models |
| `record_automatically` | `true` | Hook model events automatically |
| `events` | `['updating']` | Events that produce an adjustment |
| `excluded_attributes` | passwords, tokens, timestamps | Never recorded |
| `capture_request_context` | `true` | Store IP address and user agent |
| `user.model` / `user.guard` | auth defaults | Who is credited |
| `user.foreign_key` / `user.key_type` | `user_id` / `id` | Column name and type |
| `routes.enabled` | `false` | Expose the HTTP endpoints |
| `prune_after_days` | `365` | Retention for `model:prune` |

## Upgrading from 1.x

2.0 is a breaking release.

- **`before`/`after` are now `json` columns cast to arrays.** They were
  hand-encoded `text` before. Existing rows still decode correctly, but if you
  read them with `json_decode()` in your app, drop that call.
- **New columns:** `event`, `ip_address`, `user_agent`, plus indexes on the
  morph and user columns. Re-publish and re-run the migration, or write a small
  migration of your own to add them.
- **Routes are disabled by default** and now require authorization. Previously
  they were registered on the `web` middleware with no auth at all — re-enable
  them deliberately, behind a policy.
- **Recording is automatic** unless you set `record_automatically => false`. If
  you already call `recordAdjustment()` from an observer, either remove your
  call or disable automatic recording, or you will record twice.
- **`adjustedProperties()` no longer calls `fresh()`.** It reads
  `getOriginal()`/`getDirty()`, so it costs no extra query and is correct inside
  the `updating` event. If you called it from an `updated` observer, move that
  to `updating`.
- `user_id` changed from `string` to a type matching your user key.
- The empty `AdjustmentSeeder` was removed.

## Testing

```sh
composer install
composer test
```

## License

MIT.
