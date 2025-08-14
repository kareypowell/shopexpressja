<?php

namespace Database\Factories;

use App\Models\ConsolidatedPackage;
use App\Models\User;
use App\Enums\PackageStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsolidatedPackageFactory extends Factory
{
    protected $model = ConsolidatedPackage::class;

    public function definition(): array
    {
        return [
            'consolidated_tracking_number' => $this->generateTrackingNumber(),
            'customer_id' => User::factory(),
            'created_by' => User::factory(),
            'total_weight' => $this->faker->randomFloat(2, 1, 100),
            'total_quantity' => $this->faker->numberBetween(2, 10),
            'total_freight_price' => $this->faker->randomFloat(2, 50, 500),
            'total_customs_duty' => $this->faker->randomFloat(2, 10, 100),
            'total_storage_fee' => $this->faker->randomFloat(2, 5, 50),
            'total_delivery_fee' => $this->faker->randomFloat(2, 10, 30),
            'status' => $this->faker->randomElement([
                PackageStatus::PENDING,
                PackageStatus::PROCESSING,
                PackageStatus::CUSTOMS,
                PackageStatus::READY,
            ]),
            'consolidated_at' => now(),
            'unconsolidated_at' => null,
            'is_active' => true,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Generate a consolidated tracking number
     */
    private function generateTrackingNumber(): string
    {
        $date = now()->format('Ymd');
        $sequence = $this->faker->numberBetween(1, 9999);
        
        return sprintf('CONS-%s-%04d', $date, $sequence);
    }

    /**
     * State for inactive consolidation
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'unconsolidated_at' => now(),
        ]);
    }

    /**
     * State for specific status
     */
    public function withStatus(string $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    /**
     * State for specific customer
     */
    public function forCustomer(User $customer): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customer->id,
        ]);
    }
}