<?php

namespace Database\Factories;

use App\Models\PackageDistributionItem;
use App\Models\PackageDistribution;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageDistributionItemFactory extends Factory
{
    protected $model = PackageDistributionItem::class;

    public function definition()
    {
        $freightPrice = $this->faker->randomFloat(2, 10, 100);
        $customsDuty = $this->faker->randomFloat(2, 5, 50);
        $storageFee = $this->faker->randomFloat(2, 2, 20);
        $deliveryFee = $this->faker->randomFloat(2, 5, 25);

        return [
            'distribution_id' => PackageDistribution::factory(),
            'package_id' => Package::factory(),
            'freight_price' => $freightPrice,
            'clearance_fee' => $customsDuty,
            'storage_fee' => $storageFee,
            'delivery_fee' => $deliveryFee,
            'total_cost' => $freightPrice + $customsDuty + $storageFee + $deliveryFee,
        ];
    }
}