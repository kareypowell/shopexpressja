<?php

namespace Tests\Unit;

use App\Models\BroadcastMessage;
use App\Models\BroadcastRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastRecipientModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_broadcast_recipient_has_fillable_attributes()
    {
        $fillable = [
            'broadcast_message_id',
            'customer_id'
        ];

        $broadcastRecipient = new BroadcastRecipient();
        $this->assertEquals($fillable, $broadcastRecipient->getFillable());
    }

    public function test_broadcast_recipient_belongs_to_broadcast_message()
    {
        $broadcastMessage = BroadcastMessage::factory()->create();
        $broadcastRecipient = BroadcastRecipient::factory()->create([
            'broadcast_message_id' => $broadcastMessage->id
        ]);

        $this->assertInstanceOf(BroadcastMessage::class, $broadcastRecipient->broadcastMessage);
        $this->assertEquals($broadcastMessage->id, $broadcastRecipient->broadcastMessage->id);
    }

    public function test_broadcast_recipient_belongs_to_customer()
    {
        $customer = User::factory()->create();
        $broadcastRecipient = BroadcastRecipient::factory()->create([
            'customer_id' => $customer->id
        ]);

        $this->assertInstanceOf(User::class, $broadcastRecipient->customer);
        $this->assertEquals($customer->id, $broadcastRecipient->customer->id);
    }

    public function test_broadcast_recipient_can_be_created_with_relationships()
    {
        $broadcastMessage = BroadcastMessage::factory()->create();
        $customer = User::factory()->create();

        $broadcastRecipient = BroadcastRecipient::create([
            'broadcast_message_id' => $broadcastMessage->id,
            'customer_id' => $customer->id
        ]);

        $this->assertDatabaseHas('broadcast_recipients', [
            'id' => $broadcastRecipient->id,
            'broadcast_message_id' => $broadcastMessage->id,
            'customer_id' => $customer->id
        ]);
    }
}
