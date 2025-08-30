<?php

namespace Database\Factories;

use App\Models\BroadcastDelivery;
use App\Models\BroadcastMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BroadcastDeliveryFactory extends Factory
{
    protected $model = BroadcastDelivery::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'broadcast_message_id' => BroadcastMessage::factory(),
            'customer_id' => User::factory(),
            'email' => $this->faker->safeEmail(),
            'status' => BroadcastDelivery::STATUS_PENDING,
            'sent_at' => null,
            'failed_at' => null,
            'error_message' => null,
        ];
    }

    /**
     * Indicate that the delivery is pending.
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => BroadcastDelivery::STATUS_PENDING,
                'sent_at' => null,
                'failed_at' => null,
                'error_message' => null,
            ];
        });
    }

    /**
     * Indicate that the delivery was sent.
     */
    public function sent()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => BroadcastDelivery::STATUS_SENT,
                'sent_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
                'failed_at' => null,
                'error_message' => null,
            ];
        });
    }

    /**
     * Indicate that the delivery failed.
     */
    public function failed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => BroadcastDelivery::STATUS_FAILED,
                'sent_at' => null,
                'failed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
                'error_message' => $this->faker->sentence(),
            ];
        });
    }

    /**
     * Indicate that the delivery bounced.
     */
    public function bounced()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => BroadcastDelivery::STATUS_BOUNCED,
                'sent_at' => null,
                'failed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
                'error_message' => 'Email bounced',
            ];
        });
    }
}
