<?php

namespace Patrixsmart\Adjustfly\Traits;

use Patrixsmart\Adjustfly\Models\Adjustment;

trait OwnedAdjustments
{
    /**
     * These displays all model adjustments made by the user
     */
    public function ownedAdjustments()
    {
        return $this->hasMany(Adjustment::class);
    }
}
