<?php

namespace Database\Factories;

use App\Models\PackageDistribution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageDistributionFactory extends Factory
{
    protected $model = PackageDistribution::class;

    public function definition()
    {
        return [
            'receipt_number' => 'RCP' . now()->format('YmdHis') . rand(100, 999),
            'customer_id' => User::factory(),
            'distributed_by' => User::factory(),
            'distributed_at' => now(),
            'total_amount' => $this->faker->randomFloat(2, 50, 500),
            'amount_collected' => $this->faker->randomFloat(2, 0, 500),
            'credit_applied' => 0,
            'account_balance_applied' => 0,
            'write_off_amount' => 0,
            'payment_status' => $this->faker->randomElement(['paid', 'partial', 'unpaid']),
            'notes' => $this->faker->optional()->sentence(),
            'receipt_path' => '',
            'email_sent' => false,
        ];
    }

    public function paid()
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_status' => 'paid',
                'amount_collected' => $attributes['total_amount'],
            ];
        });
    }

    public function partial()
    {
        return $this->state(function (array $attributes) {
            $totalAmount = $attributes['total_amount'];
            return [
                'payment_status' => 'partial',
                'amount_collected' => $totalAmount * 0.7, // 70% paid
            ];
        });
    }

    public function unpaid()
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_status' => 'unpaid',
                'amount_collected' => 0,
            ];
        });
    }
}