<?php

namespace Patrixsmart\Adjustfly\Traits;

use Patrixsmart\Adjustfly\Models\Adjustment;

trait HasAdjustments
{
    /**
     * Saves the model adjustments made to the database.
     */
    public function recordAdjustment()
    {
        return $this->adjustments()->create($this->adjustedProperties());
    }

    /**
     * Get all the model fields old and new values adjusted by the user
     */
    public function adjustedProperties()
    {
        $user_id = auth()->id();

        $before = json_encode(
            array_intersect_key(
                $this->fresh()->toArray(),
                $this->getDirty()
            )
        );

        $after = json_encode($this->getDirty());

        return compact('before', 'after', 'user_id');
    }

    /**
     * Shows all adjustments made in the model
     */
    public function adjustments()
    {
        return $this->morphMany(Adjustment::class, 'adjustable');
    }

    /**
     * Shows all adjustments made in the model with pagination.
     */
    public function paginatedAdjustments($numberOfItems = 20)
    {
        return $this->adjustments()->paginate($numberOfItems);
    }

    /**
     * Shows all adjustments made in the model with simple pagination.
     */
    public function simplePaginatedAdjustments($numberOfItems = 20)
    {
        return $this->adjustments()->simplePaginate($numberOfItems);
    }
}
