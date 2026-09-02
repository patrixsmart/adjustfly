<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Patrixsmart\Adjustfly\Models\Adjustment;

/**
 * @mixin Adjustment
 */
class AdjustmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'adjustable_type' => $this->adjustable_type,
            'adjustable_id' => $this->adjustable_id,
            'changed' => $this->changedAttributes(),
            'before' => $this->before,
            'after' => $this->after,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at?->toIso8601String(),
            'adjustable' => $this->whenLoaded('adjustable'),
            'user' => $this->whenLoaded('user'),
        ];
    }
}
