<?php

namespace Database\Factories;

use App\Models\Manifest;
use Illuminate\Database\Eloquent\Factories\Factory;

class ManifestFactory extends Factory
{
    protected $model = Manifest::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'shipment_date' => $this->faker->date(),
            'reservation_number' => $this->faker->unique()->numerify('RES-####'),
            'exchange_rate' => $this->faker->randomFloat(2, 1.0, 2.0),
            'type' => $this->faker->randomElement(['air', 'sea']),
            'is_open' => true,
            'flight_number' => $this->faker->bothify('??###'),
            'flight_destination' => $this->faker->city(),
            'vessel_name' => $this->faker->words(2, true),
            'voyage_number' => $this->faker->numerify('V###'),
            'departure_port' => $this->faker->city(),
            'arrival_port' => $this->faker->city(),
            'estimated_arrival_date' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
        ];
    }

    public function sea(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'sea',
        ]);
    }

    public function air(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'air',
        ]);
    }
}