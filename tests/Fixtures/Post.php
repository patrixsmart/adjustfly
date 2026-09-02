<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Patrixsmart\Adjustfly\Traits\HasAdjustments;

class Post extends Model
{
    use HasAdjustments;

    protected $guarded = [];

    /** @var array<int, string> */
    protected array $adjustmentExcluded = ['secret'];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
