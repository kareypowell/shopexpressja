<?php

namespace Database\Factories;

use App\Models\BroadcastMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BroadcastMessageFactory extends Factory
{
    protected $model = BroadcastMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'subject' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'sender_id' => User::factory(),
            'recipient_type' => $this->faker->randomElement([BroadcastMessage::RECIPIENT_TYPE_ALL, BroadcastMessage::RECIPIENT_TYPE_SELECTED]),
            'recipient_count' => $this->faker->numberBetween(1, 100),
            'status' => BroadcastMessage::STATUS_DRAFT,
            'scheduled_at' => null,
            'sent_at' => null,
        ];
    }

    /**
     * Indicate that the broadcast message is a draft.
     */
    public function draft()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => BroadcastMessage::STATUS_DRAFT,
            ];
        });
    }

    /**
     * Indicate that the broadcast message is scheduled.
     */
    public function scheduled()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => BroadcastMessage::STATUS_SCHEDULED,
                'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 week'),
            ];
        });
    }

    /**
     * Indicate that the broadcast message is sent.
     */
    public function sent()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => BroadcastMessage::STATUS_SENT,
                'sent_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            ];
        });
    }

    /**
     * Indicate that the broadcast message failed.
     */
    public function failed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => BroadcastMessage::STATUS_FAILED,
            ];
        });
    }
}
