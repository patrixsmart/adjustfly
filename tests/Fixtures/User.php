<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Patrixsmart\Adjustfly\Traits\OwnedAdjustments;

class User extends Authenticatable
{
    use OwnedAdjustments;

    protected $table = 'users';

    protected $guarded = [];
}
