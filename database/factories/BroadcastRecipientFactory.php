<?php

namespace Database\Factories;

use App\Models\BroadcastMessage;
use App\Models\BroadcastRecipient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BroadcastRecipientFactory extends Factory
{
    protected $model = BroadcastRecipient::class;

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
        ];
    }
}
