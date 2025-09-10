<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\RoleChangeAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleChangeAuditFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RoleChangeAudit::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'changed_by_user_id' => User::factory(),
            'old_role_id' => Role::factory(),
            'new_role_id' => Role::factory(),
            'reason' => $this->faker->sentence(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    /**
     * Indicate that this is an initial role assignment (no old role).
     */
    public function initialAssignment()
    {
        return $this->state(function (array $attributes) {
            return [
                'old_role_id' => null,
                'reason' => 'Initial role assignment',
            ];
        });
    }

    /**
     * Indicate that this audit has no reason provided.
     */
    public function withoutReason()
    {
        return $this->state(function (array $attributes) {
            return [
                'reason' => null,
            ];
        });
    }

    /**
     * Indicate that this audit has no IP address or user agent.
     */
    public function withoutRequestData()
    {
        return $this->state(function (array $attributes) {
            return [
                'ip_address' => null,
                'user_agent' => null,
            ];
        });
    }

    /**
     * Create an audit from a specific number of days ago.
     */
    public function daysAgo(int $days)
    {
        return $this->state(function (array $attributes) use ($days) {
            $date = now()->subDays($days);
            return [
                'created_at' => $date,
                'updated_at' => $date,
            ];
        });
    }
}
