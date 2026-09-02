# Changelog

## 2.0.0 (unreleased)

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
