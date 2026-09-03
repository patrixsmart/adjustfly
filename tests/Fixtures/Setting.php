<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Patrixsmart\Adjustfly\Concerns\HasAdjustments;

/**
 * A record whose identity is not in any single attribute.
 *
 * Nothing here matches the configured label attributes, so the default
 * resolution finds nothing - which is exactly when a model should say what it
 * is called itself.
 */
class Setting extends Model
{
    use HasAdjustments;

    protected $guarded = [];

    public function adjustmentLabel(): ?string
    {
        return "{$this->module} - {$this->key}";
    }
}
