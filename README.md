# Adjustfly

Record Laravel model adjustments — a lightweight, polymorphic audit trail for your Eloquent models.

Supports **Laravel 12 and 13**. Laravel 12 needs PHP 8.2+; Laravel 13 needs PHP 8.3+.

## Installation

```sh
composer require patrixsmart/adjustfly
```

Publish the config and the migration, then migrate:

```sh
php artisan vendor:publish --tag="adjustfly-config"
php artisan vendor:publish --tag="adjustfly-migrations"
php artisan migrate
```

The migration is **not** run from the package — publishing is required. The
schema then lives in your own repository, where it shows up in code review, can
be edited before it is ever run, and is never changed underneath you by a
`composer update`.

Open the published migration before migrating if your models use UUID or ULID
keys; it ships with `morphs()` and an integer `user_id`, and carries comments
showing the alternatives.

## Usage

### Track a model

Add the `HasAdjustments` trait. That is all — adjustments are recorded
automatically on every update.

```php
use Illuminate\Database\Eloquent\Model;
use Patrixsmart\Adjustfly\Concerns\HasAdjustments;

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

### Name the record

Every adjustment stores `adjustable_label` - a human name for the record it
belongs to, captured when the row is written.

```php
$student->update(['class_id' => 4]);

$student->adjustments()->first()->adjustable_label;   // "Ada Lovelace"
```

It is stored rather than resolved on read for two reasons: a name that only
exists on the record cannot be searched for, and a hard-deleted record cannot
be loaded back at all - so without this the trail forgets what it was about.

```php
Adjustment::query()->where('adjustable_label', 'like', "%Lovelace%")->get();
```

The first of `adjustfly.label_attributes` that holds a value wins:

```php
// config/adjustfly.php
'label_attributes' => ['name', 'title', 'label', 'reference', 'email'],
```

When a model's identity is not in a single attribute, it can say what it is
called itself:

```php
class Setting extends Model
{
    use HasAdjustments;

    public function adjustmentLabel(): ?string
    {
        return "{$this->module} - {$this->key}";
    }
}
```

Returning `null` is fine - not every model has a name worth recording. Labels
are truncated to 255 characters, so an unexpectedly long value can never fail
the insert and lose the adjustment with it.

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
use Patrixsmart\Adjustfly\Concerns\OwnedAdjustments;

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

Adjustments grow quickly. `adjustfly:prune` deletes anything older than
`prune_after_days` (default 365):

```sh
php artisan adjustfly:prune

php artisan adjustfly:prune --pretend      # report, delete nothing
php artisan adjustfly:prune --days=90      # override the configured window
php artisan adjustfly:prune --chunk=500    # smaller batches on big tables
```

Schedule it daily:

```php
// bootstrap/app.php
->withSchedule(fn ($schedule) => $schedule->command('adjustfly:prune')->daily())
```

Set `prune_after_days` to `null` to keep adjustments forever — the command then
reports that pruning is disabled and exits cleanly, so it is safe to leave
scheduled.

> Use `adjustfly:prune` rather than Laravel's `model:prune`. The latter only
> auto-discovers models in your own `app/Models` directory, so it will run
> successfully and prune no adjustments at all. `adjustfly:prune` names the
> configured model explicitly and then delegates to `model:prune`, so chunking,
> the `ModelsPruned` event and soft-delete handling are unchanged. If you prefer
> calling it directly:
> `php artisan model:prune --model="Patrixsmart\Adjustfly\Models\Adjustment"`

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
| `record_automatically` | `true` | Hook model events automatically |
| `events` | `['updating']` | Events that produce an adjustment |
| `excluded_attributes` | passwords, tokens, timestamps | Never recorded |
| `label_attributes` | `name`, `title`, `label`, … | Attributes tried when naming a record |
| `capture_request_context` | `true` | Store IP address and user agent |
| `user.model` / `user.guard` | auth defaults | Who is credited |
| `user.foreign_key` | `user_id` | User column on the adjustments table |
| `routes.enabled` | `false` | Expose the HTTP endpoints |
| `prune_after_days` | `365` | Retention for `adjustfly:prune` |

## Upgrading from 2.x

3.0 adds the `adjustable_label` column, and the package writes to it on every
adjustment - so the column has to exist before you upgrade.

Since 2.1 the migration lives in your application rather than the package,
which means it will not change on its own. Add the column to your published
migration and re-run it, or write a small migration of your own:

```php
Schema::table('adjustments', function (Blueprint $table) {
    $table->string('adjustable_label')->nullable()->after('adjustable_id');
});
```

Existing rows keep a null label; nothing else changes. If you would rather not
store labels at all, set `label_attributes` to `[]` and override
`adjustmentLabel()` to return null - the column stays empty but must still be
present.

## Upgrading from 2.0

2.1 carries two breaking changes despite the minor version bump — 2.0.0 had only
just been published when they landed.

- **The traits moved from `Patrixsmart\Adjustfly\Traits` to
  `Patrixsmart\Adjustfly\Concerns`.** The old namespace is gone, so every model
  that tracks adjustments needs its import updated:

  ```diff
  - use Patrixsmart\Adjustfly\Traits\HasAdjustments;
  - use Patrixsmart\Adjustfly\Traits\OwnedAdjustments;
  + use Patrixsmart\Adjustfly\Concerns\HasAdjustments;
  + use Patrixsmart\Adjustfly\Concerns\OwnedAdjustments;
  ```

- **The migration is no longer loaded from the package.** If your table already
  exists you are unaffected. On a fresh install you must now run
  `php artisan vendor:publish --tag="adjustfly-migrations"` before `migrate`, or
  you will end up with no table and no error.
- The `morph_key_type` and `user.key_type` config keys were removed. Leaving them
  in a published config is harmless; set column types in the published migration.

## Upgrading from 1.x

2.x is a breaking release. Everything under *Upgrading from 2.0* applies too.

- **`before`/`after` are now `json` columns cast to arrays.** They were
  hand-encoded `text` before. Existing rows still decode correctly, but if you
  read them with `json_decode()` in your app, drop that call.
- **New columns:** `event`, `ip_address`, `user_agent`, plus indexes on the
  morph and user columns. Your 1.x table predates them, so adapt the published
  migration into an `ALTER`, or drop and recreate the table.
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

MIT. See [LICENSE](LICENSE).
