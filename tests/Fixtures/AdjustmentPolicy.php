<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Tests\Fixtures;

use Patrixsmart\Adjustfly\Models\Adjustment;

class AdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function view(User $user, Adjustment $adjustment): bool
    {
        return (bool) $user->is_admin;
    }
}
