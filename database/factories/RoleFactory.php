<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['admin', 'customer', 'superadmin', 'purchaser']),
            'description' => $this->faker->sentence(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'admin',
            'description' => 'Administrator role',
        ]);
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'customer',
            'description' => 'Customer role',
        ]);
    }
}