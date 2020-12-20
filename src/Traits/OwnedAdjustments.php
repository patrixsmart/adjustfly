<?php

namespace Patrixsmart\Adjustfly\Traits;

use Patrixsmart\Adjustfly\Models\Adjustment;

trait OwnedAdjustments
{
    /**
     *
     */
    public function ownedAdjustments()
    {
        return $this->hasMany(Adjustment::class);
    }
}
