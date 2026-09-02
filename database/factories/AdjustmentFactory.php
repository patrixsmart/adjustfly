<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Patrixsmart\Adjustfly\Models\Adjustment;

/**
 * @extends Factory<Adjustment>
 */
class AdjustmentFactory extends Factory
{
    protected $model = Adjustment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'adjustable_type' => $this->faker->word(),
            'adjustable_id' => $this->faker->numberBetween(1, 1000),
            'event' => 'updated',
            'before' => ['name' => $this->faker->name()],
            'after' => ['name' => $this->faker->name()],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            config('adjustfly.user.foreign_key', 'user_id') => null,
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $adjustable
     */
    public function forAdjustable(object $adjustable): static
    {
        return $this->state(fn (): array => [
            'adjustable_type' => $adjustable->getMorphClass(),
            'adjustable_id' => $adjustable->getKey(),
        ]);
    }
}
