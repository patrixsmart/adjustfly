<?php

namespace Patrixsmart\Adjustfly\Traits;

use Patrixsmart\Adjustfly\Models\Adjustment;

trait HasAdjustments
{
    /**
     *
     */
    public function recordAdjustment()
    {
        return $this->adjustments()->create($this->adjustedProperties());
    }

    /**
     *
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

        return compact('before','after','user_id');
    }

    /**
     *
     */
    public function adjustments()
    {
        return $this->morphMany(Adjustment::class,'adjustable');
    }
}
