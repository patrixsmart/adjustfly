<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Patrixsmart\Adjustfly\Models\Adjustment;

class AdjustmentRecorded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Adjustment $adjustment,
        public readonly Model $adjustable,
    ) {}
}
