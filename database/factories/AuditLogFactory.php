<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $eventTypes = ['authentication', 'authorization', 'model_created', 'model_updated', 'model_deleted', 'business_action', 'financial_transaction', 'security_event', 'system_event'];
        $actions = ['login', 'logout', 'create', 'update', 'delete', 'failed_login', 'unauthorized_access', 'consolidate', 'payment_processed'];
        $auditableTypes = ['App\Models\User', 'App\Models\Package', 'App\Models\Manifest', 'App\Models\ConsolidatedPackage'];

        return [
            'user_id' => User::factory(),
            'event_type' => $this->faker->randomElement($eventTypes),
            'auditable_type' => $this->faker->randomElement($auditableTypes),
            'auditable_id' => $this->faker->numberBetween(1, 100),
            'action' => $this->faker->randomElement($actions),
            'old_values' => $this->faker->optional()->passthrough([
                'field1' => $this->faker->word,
                'field2' => $this->faker->sentence,
            ]),
            'new_values' => $this->faker->optional()->passthrough([
                'field1' => $this->faker->word,
                'field2' => $this->faker->sentence,
            ]),
            'url' => $this->faker->optional()->url,
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
            'additional_data' => $this->faker->optional()->passthrough([
                'extra_info' => $this->faker->sentence,
                'context' => $this->faker->word,
            ]),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the audit log is for authentication events.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function authentication()
    {
        return $this->state(function (array $attributes) {
            return [
                'event_type' => 'authentication',
                'action' => $this->faker->randomElement(['login', 'logout', 'failed_login']),
                'auditable_type' => null,
                'auditable_id' => null,
            ];
        });
    }

    /**
     * Indicate that the audit log is for security events.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function securityEvent()
    {
        return $this->state(function (array $attributes) {
            return [
                'event_type' => 'security_event',
                'action' => $this->faker->randomElement(['failed_login', 'unauthorized_access', 'suspicious_activity']),
            ];
        });
    }

    /**
     * Indicate that the audit log is for model changes.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function modelChange()
    {
        return $this->state(function (array $attributes) {
            return [
                'event_type' => $this->faker->randomElement(['model_created', 'model_updated', 'model_deleted']),
                'action' => $this->faker->randomElement(['create', 'update', 'delete']),
            ];
        });
    }
}