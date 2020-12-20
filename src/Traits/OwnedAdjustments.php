<?php

namespace Patrixsmart\Adjustfly\Traits;

use Patrixsmart\Adjustfly\Models\Adjustment;

trait HasAdjustments
{
    /**
     *
     */
    public function ownedAdjustments()
    {
        return $this->hasMany(Adjustment::class);
    }
}
