<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Patrixsmart\Adjustfly\Events\AdjustmentRecorded;
use Patrixsmart\Adjustfly\Models\Adjustment;

/**
 * @mixin Model
 */
trait HasAdjustments
{
    /**
     * Whether adjustments are currently being recorded for this model class.
     */
    protected static bool $recordingAdjustments = true;

    /**
     * Hook the configured model events so adjustments are recorded for free.
     */
    public static function bootHasAdjustments(): void
    {
        foreach (['created', 'updating', 'deleted', 'restored'] as $event) {
            static::registerModelEvent($event, static function (self $model) use ($event): void {
                if (! config('adjustfly.record_automatically', true)) {
                    return;
                }

                if (! in_array($event, (array) config('adjustfly.events', ['updating']), true)) {
                    return;
                }

                $model->recordAdjustment($event === 'updating' ? 'updated' : $event);
            });
        }
    }

    /**
     * Run a callback without recording adjustments for this model class.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function withoutAdjustments(callable $callback): mixed
    {
        $previous = static::$recordingAdjustments;

        static::$recordingAdjustments = false;

        try {
            return $callback();
        } finally {
            static::$recordingAdjustments = $previous;
        }
    }

    /**
     * All adjustments recorded against this model, newest first.
     *
     * Several adjustments can share a created_at timestamp, so the primary key
     * breaks the tie — otherwise "newest" is whatever order the driver returns.
     *
     * @return MorphMany<Adjustment, $this>
     */
    public function adjustments(): MorphMany
    {
        $relation = $this->morphMany($this->adjustmentModel(), 'adjustable');

        return $relation
            ->orderByDesc($relation->getRelated()->getQualifiedCreatedAtColumn())
            ->orderByDesc($relation->getRelated()->getQualifiedKeyName());
    }

    /**
     * The most recent adjustment recorded against this model.
     *
     * @return MorphOne<Adjustment, $this>
     */
    public function latestAdjustment(): MorphOne
    {
        return $this->morphOne($this->adjustmentModel(), 'adjustable')->latestOfMany();
    }

    /**
     * Persist the model's pending changes as an adjustment.
     *
     * Returns null when there is nothing worth recording, so callers can
     * safely do `$model->recordAdjustment()?->id`.
     */
    public function recordAdjustment(string $event = 'updated'): ?Adjustment
    {
        if (! static::$recordingAdjustments) {
            return null;
        }

        $properties = $this->adjustedProperties($event);

        if ($properties === null) {
            return null;
        }

        /** @var Adjustment $adjustment */
        $adjustment = $this->adjustments()->create($properties);

        event(new AdjustmentRecorded($adjustment, $this));

        return $adjustment;
    }

    /**
     * Build the before/after payload for an adjustment.
     *
     * Values are read from getOriginal()/getDirty() rather than re-querying
     * the database, so this is safe (and free of extra queries) inside the
     * "updating" event.
     *
     * @return array<string, mixed>|null
     */
    public function adjustedProperties(string $event = 'updated'): ?array
    {
        $dirty = array_keys($this->getDirty());

        [$before, $after] = match ($event) {
            'created', 'restored' => [
                [],
                $this->adjustmentCurrentValues(array_keys($this->getAttributes())),
            ],
            'deleted', 'forceDeleted' => [
                $this->adjustmentOriginalValues(array_keys($this->getRawOriginal())),
                [],
            ],
            default => [
                $this->adjustmentOriginalValues($dirty),
                $this->adjustmentCurrentValues($dirty),
            ],
        };

        $before = $this->filterAdjustedAttributes($before);
        $after = $this->filterAdjustedAttributes($after);

        if ($before === [] && $after === []) {
            return null;
        }

        return array_merge([
            'event' => $event,
            'before' => $before,
            'after' => $after,
            $this->adjustmentUserForeignKey() => $this->adjustmentUserId(),
        ], $this->adjustmentRequestContext());
    }

    /**
     * Current values for the given attributes, with casts applied.
     *
     * getDirty() and getAttributes() hand back raw storage values, so an
     * array-cast column would be recorded as a JSON *string* while the
     * matching getOriginal() value came back as an array. Reading through
     * getAttribute() keeps both sides of the payload in the same shape.
     *
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    protected function adjustmentCurrentValues(array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->getAttribute($key);
        }

        return $values;
    }

    /**
     * Original values for the given attributes, with casts applied.
     *
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    protected function adjustmentOriginalValues(array $keys): array
    {
        return array_intersect_key($this->getOriginal(), array_flip($keys));
    }

    /**
     * Remove attributes that must never be recorded.
     *
     * Override $adjustmentExcluded to add to the global exclusion list, or
     * $adjustmentOnly to restrict recording to a whitelist.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function filterAdjustedAttributes(array $attributes): array
    {
        $only = property_exists($this, 'adjustmentOnly') ? (array) $this->adjustmentOnly : [];

        if ($only !== []) {
            $attributes = array_intersect_key($attributes, array_flip($only));
        }

        $excluded = array_merge(
            (array) config('adjustfly.excluded_attributes', []),
            property_exists($this, 'adjustmentExcluded') ? (array) $this->adjustmentExcluded : []
        );

        return array_diff_key($attributes, array_flip($excluded));
    }

    /**
     * @return array<string, string|null>
     */
    protected function adjustmentRequestContext(): array
    {
        if (! config('adjustfly.capture_request_context', true) || ! app()->bound('request')) {
            return [];
        }

        $request = request();

        return [
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1024) ?: null,
        ];
    }

    protected function adjustmentUserId(): int|string|null
    {
        if (! app()->bound('auth')) {
            return null;
        }

        return app('auth')->guard(config('adjustfly.user.guard'))->id();
    }

    protected function adjustmentUserForeignKey(): string
    {
        return config('adjustfly.user.foreign_key', 'user_id');
    }

    /**
     * @return class-string<Adjustment>
     */
    protected function adjustmentModel(): string
    {
        return config('adjustfly.model', Adjustment::class);
    }

    /**
     * @return LengthAwarePaginator<int, Adjustment>
     */
    public function paginatedAdjustments(?int $perPage = null): LengthAwarePaginator
    {
        return $this->adjustments()->paginate($perPage ?? $this->adjustmentsPerPage());
    }

    /**
     * @return Paginator<int, Adjustment>
     */
    public function simplePaginatedAdjustments(?int $perPage = null): Paginator
    {
        return $this->adjustments()->simplePaginate($perPage ?? $this->adjustmentsPerPage());
    }

    protected function adjustmentsPerPage(): int
    {
        return (int) config('adjustfly.routes.per_page', 20);
    }
}
