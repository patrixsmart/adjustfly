# Changelog

## 2.1.0

> **This release contains breaking changes.** They would normally warrant a major
> version; they ship here because 2.0.0 was only just published. Read the two
> items under *Changed* before upgrading.

### Changed
- **BREAKING: the traits moved from `Patrixsmart\Adjustfly\Traits` to
  `Patrixsmart\Adjustfly\Concerns`.** Update the `use` statements in every model
  that tracks adjustments — the old namespace no longer exists and will fatal.

  ```diff
  - use Patrixsmart\Adjustfly\Traits\HasAdjustments;
  + use Patrixsmart\Adjustfly\Concerns\HasAdjustments;
  ```

- **BREAKING: the migration is no longer loaded from the package.** It must be
  published with `vendor:publish --tag="adjustfly-migrations"` before
  `php artisan migrate`. Existing installs already have the table and are
  unaffected; a fresh install that skips the publish step will silently end up
  with no table. The schema now lives in the application's own repository, where
  it is reviewable, editable before it is first run, and never altered by a
  `composer update` followed by `migrate`.
- Removed the `morph_key_type` and `user.key_type` config keys. They were read
  only by the package migration; set the column types in the published file
  instead. Leaving them in a published config is harmless — nothing reads them.

### Added
- `adjustfly:prune` command (`--days`, `--chunk`, `--pretend`). Laravel's own
  `model:prune` only auto-discovers models under `app/Models`, so it never
  reaches a model that lives in a package and would prune nothing;
  `adjustfly:prune` names the configured model and delegates to it.
- A foreign key on the user column, with `nullOnDelete()` so deleting a user
  keeps the audit row and nulls the actor.
- `LICENSE` file. The package declared MIT in `composer.json` and the README but
  shipped no licence text.
- `.gitattributes`, so tests, CI config and `phpunit.xml` are excluded from the
  distributed package.

### Fixed
- The migration failed on MySQL and PostgreSQL whenever `user.foreign_key` was
  customised: the column honoured the config but the index hardcoded `user_id`.
  Both now derive from one variable. SQLite hid this, because it silently
  rewrites an unresolved double-quoted identifier into a string literal and
  indexes that constant instead.
- The foreign key hardcoded the `users` table; it now resolves from the
  configured user model, so an app whose actor lives in `admins` or `staff`
  works.
- The migration's UUID/ULID comment pointed at `$table->uuid()`, which cannot
  chain `constrained()`. It now points at `foreignUuid()`/`foreignUlid()`.
- CI installed `laravel/framework` as a dev dependency — `--dev` applied to every
  package in the `composer require` call, moving the framework out of `require`
  and testing a manifest that no longer declared the real runtime dependency.

## 2.0.0

### Security
- Routes are **disabled by default** and gated behind `viewAny`/`view` authorization.
  Previously `/adjustments` was registered on the `web` middleware with no
  authentication, publicly exposing the before/after state of every tracked model.
- `password`, `remember_token` and timestamps are excluded from recorded payloads
  by default, configurable globally and per model.

### Added
- Automatic recording via model events (`created`, `updating`, `deleted`, `restored`).
- `AdjustmentRecorded` event.
- `Model::withoutAdjustments()` to suppress recording for a block.
- `$adjustmentExcluded` / `$adjustmentOnly` per-model attribute control.
- `event`, `ip_address` and `user_agent` columns.
- `forAdjustable()`, `forType()`, `forUser()` query scopes and `changedAttributes()`.
- `latestAdjustment` and `user()` relations.
- UUID/ULID support for both tracked models and the user key.
- Retention via `MassPrunable` and `php artisan model:prune`.
- `AdjustmentResource` for consistent JSON output, with filtering and validated pagination.
- Configurable table, model, guard, foreign key, route prefix/middleware/name.
- `adjustfly-migrations` publish tag.
- Test suite on Orchestra Testbench; CI across Laravel 12/13.

### Fixed
- `adjustedProperties()` no longer calls `fresh()` — removes a query per update and
  fixes incorrect values when recording outside the `updating` event.
- Controller no longer extends `App\Http\Controllers\Controller`, which is absent
  from the Laravel 11+ skeleton.
- Routes use class references instead of the removed string/`namespace` style.
- Migration converted to an anonymous class; added missing indexes.
- No adjustment is written when nothing meaningful changed.
- `before` and `after` both store **cast** values. Previously the two sides were
  asymmetric: an `array`-cast column was recorded as an array on the `before`
  side but as a raw JSON string on the `after` side, and `datetime` columns
  mixed ISO-8601 with raw SQL strings.
- `adjustments()` and `ownedAdjustments()` break `created_at` ties on the
  primary key. Adjustments written in the same second previously came back in
  whatever order the driver chose, which also made `latestAdjustment` unreliable.

### Changed
- **Dropped support for Laravel 8, 9, 10 and 11.** Requires Laravel 12 or 13
  (PHP 8.2+ on Laravel 12, PHP 8.3+ on Laravel 13).
- `before`/`after` are `json` columns cast to arrays.
- Removed the empty `AdjustmentSeeder`.

## 1.x
- Initial releases.
