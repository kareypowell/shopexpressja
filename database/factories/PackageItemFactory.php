<?php

namespace Database\Factories;

use App\Models\PackageItem;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageItemFactory extends Factory
{
    protected $model = PackageItem::class;

    public function definition()
    {
        return [
            'package_id' => Package::factory(),
            'description' => $this->faker->sentence(3),
            'quantity' => $this->faker->numberBetween(1, 10),
            'weight_per_item' => $this->faker->randomFloat(2, 0.1, 50.0),
        ];
    }
}