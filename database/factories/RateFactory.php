<?php

namespace Database\Factories;

use App\Models\Rate;
use Illuminate\Database\Eloquent\Factories\Factory;

class RateFactory extends Factory
{
    protected $model = Rate::class;

    public function definition(): array
    {
        return [
            'weight' => $this->faker->randomFloat(0, 1, 50),
            'min_cubic_feet' => null,
            'max_cubic_feet' => null,
            'price' => $this->faker->randomFloat(2, 5.0, 50.0),
            'processing_fee' => $this->faker->randomFloat(2, 1.0, 20.0),
            'type' => $this->faker->randomElement(['air', 'sea']),
        ];
    }

    public function air(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'air',
            'weight' => $this->faker->randomFloat(0, 1, 50),
            'min_cubic_feet' => null,
            'max_cubic_feet' => null,
        ]);
    }

    public function sea(): static
    {
        return $this->state(function (array $attributes) {
            $minCubicFeet = $this->faker->randomFloat(1, 0.5, 5.0);
            $maxCubicFeet = $minCubicFeet + $this->faker->randomFloat(1, 1.0, 10.0);
            
            return [
                'type' => 'sea',
                'weight' => null,
                'min_cubic_feet' => $minCubicFeet,
                'max_cubic_feet' => $maxCubicFeet,
            ];
        });
    }

    public function seaRange(float $minCubicFeet, float $maxCubicFeet): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'sea',
            'weight' => null,
            'min_cubic_feet' => $minCubicFeet,
            'max_cubic_feet' => $maxCubicFeet,
        ]);
    }
}