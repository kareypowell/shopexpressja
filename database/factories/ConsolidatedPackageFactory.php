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

    /**
     * State for specific admin creator
     */
    public function createdBy(User $admin): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $admin->id,
        ]);
    }

    /**
     * State for delivered consolidation
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PackageStatus::DELIVERED,
        ]);
    }

    /**
     * State for processing consolidation
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PackageStatus::PROCESSING,
        ]);
    }

    /**
     * State for customs consolidation
     */
    public function customs(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PackageStatus::CUSTOMS,
        ]);
    }

    /**
     * State for shipped consolidation
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PackageStatus::SHIPPED,
        ]);
    }

    /**
     * State for ready consolidation
     */
    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PackageStatus::READY,
        ]);
    }

    /**
     * State for high value consolidation
     */
    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_weight' => $this->faker->randomFloat(2, 10, 50),
            'total_quantity' => $this->faker->numberBetween(5, 15),
            'total_freight_price' => $this->faker->randomFloat(2, 200, 800),
            'total_customs_duty' => $this->faker->randomFloat(2, 50, 200),
            'total_storage_fee' => $this->faker->randomFloat(2, 20, 80),
            'total_delivery_fee' => $this->faker->randomFloat(2, 20, 60),
        ]);
    }

    /**
     * State for small consolidation
     */
    public function small(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_weight' => $this->faker->randomFloat(2, 1, 5),
            'total_quantity' => $this->faker->numberBetween(2, 3),
            'total_freight_price' => $this->faker->randomFloat(2, 20, 80),
            'total_customs_duty' => $this->faker->randomFloat(2, 5, 25),
            'total_storage_fee' => $this->faker->randomFloat(2, 2, 10),
            'total_delivery_fee' => $this->faker->randomFloat(2, 3, 12),
        ]);
    }

    /**
     * State for historical consolidation (created in the past)
     */
    public function historical(): static
    {
        $daysAgo = $this->faker->numberBetween(7, 30);
        return $this->state(fn (array $attributes) => [
            'consolidated_at' => now()->subDays($daysAgo),
            'created_at' => now()->subDays($daysAgo),
            'updated_at' => now()->subDays($daysAgo - $this->faker->numberBetween(1, 5)),
        ]);
    }

    /**
     * State for consolidation with specific notes
     */
    public function withNotes(string $notes): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => $notes,
        ]);
    }
}