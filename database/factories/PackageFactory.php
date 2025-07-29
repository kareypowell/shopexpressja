<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\Manifest;
use App\Models\User;
use App\Models\Office;
use App\Models\Shipper;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageFactory extends Factory
{
    protected $model = Package::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'manifest_id' => Manifest::factory(),
            'shipper_id' => Shipper::factory(),
            'office_id' => Office::factory(),
            'warehouse_receipt_no' => $this->faker->numerify('WR#####'),
            'tracking_number' => $this->faker->unique()->numerify('TRK#########'),
            'description' => $this->faker->sentence(),
            'weight' => $this->faker->randomFloat(2, 0.1, 100.0),
            'status' => $this->faker->randomElement(['pending', 'processing', 'shipped', 'delivered']),
            'estimated_value' => $this->faker->randomFloat(2, 10.0, 1000.0),
            'freight_price' => $this->faker->randomFloat(2, 5.0, 100.0),
            'customs_duty' => $this->faker->randomFloat(2, 0.0, 50.0),
            'storage_fee' => $this->faker->randomFloat(2, 0.0, 20.0),
            'delivery_fee' => $this->faker->randomFloat(2, 0.0, 30.0),
            'container_type' => $this->faker->randomElement(['box', 'barrel', 'pallet']),
            'length_inches' => $this->faker->randomFloat(2, 1.0, 48.0),
            'width_inches' => $this->faker->randomFloat(2, 1.0, 48.0),
            'height_inches' => $this->faker->randomFloat(2, 1.0, 48.0),
            'cubic_feet' => $this->faker->randomFloat(3, 0.1, 10.0),
        ];
    }

    public function seaPackage(): static
    {
        return $this->state(function (array $attributes) {
            $length = $this->faker->randomFloat(2, 6.0, 48.0);
            $width = $this->faker->randomFloat(2, 6.0, 48.0);
            $height = $this->faker->randomFloat(2, 6.0, 48.0);
            
            return [
                'manifest_id' => Manifest::factory()->sea(),
                'container_type' => $this->faker->randomElement(['box', 'barrel', 'pallet']),
                'length_inches' => $length,
                'width_inches' => $width,
                'height_inches' => $height,
                'cubic_feet' => round(($length * $width * $height) / 1728, 3),
            ];
        });
    }

    public function airPackage(): static
    {
        return $this->state(fn (array $attributes) => [
            'manifest_id' => Manifest::factory()->air(),
            'container_type' => null,
            'length_inches' => null,
            'width_inches' => null,
            'height_inches' => null,
            'cubic_feet' => null,
        ]);
    }
}