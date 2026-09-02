<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Patrixsmart\Adjustfly\Models\Adjustment;

/**
 * @mixin Model
 */
trait OwnedAdjustments
{
    /**
     * Every adjustment this user is credited with.
     *
     * @return HasMany<Adjustment, $this>
     */
    public function ownedAdjustments(): HasMany
    {
        $relation = $this->hasMany(
            config('adjustfly.model', Adjustment::class),
            config('adjustfly.user.foreign_key', 'user_id')
        );

        return $relation
            ->orderByDesc($relation->getRelated()->getQualifiedCreatedAtColumn())
            ->orderByDesc($relation->getRelated()->getQualifiedKeyName());
    }

    /**
     * @return LengthAwarePaginator<int, Adjustment>
     */
    public function paginatedOwnedAdjustments(?int $perPage = null): LengthAwarePaginator
    {
        return $this->ownedAdjustments()->paginate(
            $perPage ?? (int) config('adjustfly.routes.per_page', 20)
        );
    }
}
