<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Patrixsmart\Adjustfly\Http\Resources\AdjustmentResource;
use Patrixsmart\Adjustfly\Models\Adjustment;

/**
 * Read-only access to recorded adjustments.
 *
 * Every action is gated: register an AdjustmentPolicy (or Gates named
 * "viewAny"/"view" for the Adjustment model) before enabling the routes,
 * otherwise Laravel denies access by default.
 */
class AdjustmentController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var class-string<Adjustment> $model */
        $model = config('adjustfly.model', Adjustment::class);

        abort_unless($request->user()?->can('viewAny', $model), 403);

        $validated = $request->validate([
            'adjustable_type' => ['sometimes', 'string'],
            'adjustable_id' => ['sometimes', 'string'],
            'user_id' => ['sometimes', 'string'],
            'event' => ['sometimes', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.$this->maxPerPage()],
        ]);

        $instance = new $model;

        $adjustments = $model::query()
            ->when($validated['adjustable_type'] ?? null, fn ($q, $type) => $q->where('adjustable_type', $type))
            ->when($validated['adjustable_id'] ?? null, fn ($q, $id) => $q->where('adjustable_id', $id))
            ->when($validated['event'] ?? null, fn ($q, $event) => $q->where('event', $event))
            ->when(
                $validated['user_id'] ?? null,
                fn ($q, $id) => $q->where(config('adjustfly.user.foreign_key', 'user_id'), $id)
            )
            ->orderByDesc($instance->getCreatedAtColumn())
            ->orderByDesc($instance->getKeyName())
            ->paginate($validated['per_page'] ?? $this->perPage())
            ->withQueryString();

        return AdjustmentResource::collection($adjustments);
    }

    /**
     * The adjustment is resolved here rather than through route model binding.
     * An explicit Route::bind() would claim the "{adjustment}" parameter name
     * across the whole host application, and implicit binding cannot honour a
     * custom model configured in adjustfly.model.
     */
    public function show(Request $request, string $adjustment): AdjustmentResource
    {
        /** @var class-string<Adjustment> $model */
        $model = config('adjustfly.model', Adjustment::class);

        $record = $model::query()->findOrFail($adjustment);

        abort_unless($request->user()?->can('view', $record), 403);

        return new AdjustmentResource($record->load('adjustable'));
    }

    private function perPage(): int
    {
        return (int) config('adjustfly.routes.per_page', 20);
    }

    private function maxPerPage(): int
    {
        return (int) config('adjustfly.routes.max_per_page', 100);
    }
}
