<?php

namespace Tests\Unit;

use App\Models\BroadcastDelivery;
use App\Models\BroadcastMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastDeliveryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_broadcast_delivery_has_fillable_attributes()
    {
        $fillable = [
            'broadcast_message_id',
            'customer_id',
            'email',
            'status',
            'sent_at',
            'failed_at',
            'error_message'
        ];

        $broadcastDelivery = new BroadcastDelivery();
        $this->assertEquals($fillable, $broadcastDelivery->getFillable());
    }

    public function test_broadcast_delivery_has_correct_casts()
    {
        $broadcastDelivery = new BroadcastDelivery();
        $casts = $broadcastDelivery->getCasts();

        $this->assertEquals('datetime', $casts['sent_at']);
        $this->assertEquals('datetime', $casts['failed_at']);
    }

    public function test_broadcast_delivery_belongs_to_broadcast_message()
    {
        $broadcastMessage = BroadcastMessage::factory()->create();
        $broadcastDelivery = BroadcastDelivery::factory()->create([
            'broadcast_message_id' => $broadcastMessage->id
        ]);

        $this->assertInstanceOf(BroadcastMessage::class, $broadcastDelivery->broadcastMessage);
        $this->assertEquals($broadcastMessage->id, $broadcastDelivery->broadcastMessage->id);
    }

    public function test_broadcast_delivery_belongs_to_customer()
    {
        $customer = User::factory()->create();
        $broadcastDelivery = BroadcastDelivery::factory()->create([
            'customer_id' => $customer->id
        ]);

        $this->assertInstanceOf(User::class, $broadcastDelivery->customer);
        $this->assertEquals($customer->id, $broadcastDelivery->customer->id);
    }

    public function test_pending_scope()
    {
        BroadcastDelivery::factory()->create(['status' => BroadcastDelivery::STATUS_PENDING]);
        BroadcastDelivery::factory()->create(['status' => BroadcastDelivery::STATUS_SENT]);

        $pending = BroadcastDelivery::pending()->get();
        $this->assertCount(1, $pending);
        $this->assertEquals(BroadcastDelivery::STATUS_PENDING, $pending->first()->status);
    }

    public function test_sent_scope()
    {
        BroadcastDelivery::factory()->create(['status' => BroadcastDelivery::STATUS_SENT]);
        BroadcastDelivery::factory()->create(['status' => BroadcastDelivery::STATUS_PENDING]);

        $sent = BroadcastDelivery::sent()->get();
        $this->assertCount(1, $sent);
        $this->assertEquals(BroadcastDelivery::STATUS_SENT, $sent->first()->status);
    }

    public function test_failed_scope()
    {
        BroadcastDelivery::factory()->create(['status' => BroadcastDelivery::STATUS_FAILED]);
        BroadcastDelivery::factory()->create(['status' => BroadcastDelivery::STATUS_SENT]);

        $failed = BroadcastDelivery::failed()->get();
        $this->assertCount(1, $failed);
        $this->assertEquals(BroadcastDelivery::STATUS_FAILED, $failed->first()->status);
    }

    public function test_bounced_scope()
    {
        BroadcastDelivery::factory()->create(['status' => BroadcastDelivery::STATUS_BOUNCED]);
        BroadcastDelivery::factory()->create(['status' => BroadcastDelivery::STATUS_SENT]);

        $bounced = BroadcastDelivery::bounced()->get();
        $this->assertCount(1, $bounced);
        $this->assertEquals(BroadcastDelivery::STATUS_BOUNCED, $bounced->first()->status);
    }

    public function test_mark_as_sent()
    {
        $broadcastDelivery = BroadcastDelivery::factory()->create([
            'status' => BroadcastDelivery::STATUS_PENDING
        ]);

        $result = $broadcastDelivery->markAsSent();

        $this->assertTrue($result);
        $fresh = $broadcastDelivery->fresh();
        $this->assertEquals(BroadcastDelivery::STATUS_SENT, $fresh->status);
        $this->assertNotNull($fresh->sent_at);
        $this->assertNull($fresh->failed_at);
        $this->assertNull($fresh->error_message);
    }

    public function test_mark_as_failed()
    {
        $broadcastDelivery = BroadcastDelivery::factory()->create([
            'status' => BroadcastDelivery::STATUS_PENDING
        ]);
        $errorMessage = 'Email delivery failed';

        $result = $broadcastDelivery->markAsFailed($errorMessage);

        $this->assertTrue($result);
        $fresh = $broadcastDelivery->fresh();
        $this->assertEquals(BroadcastDelivery::STATUS_FAILED, $fresh->status);
        $this->assertNotNull($fresh->failed_at);
        $this->assertEquals($errorMessage, $fresh->error_message);
    }

    public function test_mark_as_bounced()
    {
        $broadcastDelivery = BroadcastDelivery::factory()->create([
            'status' => BroadcastDelivery::STATUS_PENDING
        ]);
        $errorMessage = 'Email bounced';

        $result = $broadcastDelivery->markAsBounced($errorMessage);

        $this->assertTrue($result);
        $fresh = $broadcastDelivery->fresh();
        $this->assertEquals(BroadcastDelivery::STATUS_BOUNCED, $fresh->status);
        $this->assertNotNull($fresh->failed_at);
        $this->assertEquals($errorMessage, $fresh->error_message);
    }

    public function test_status_check_methods()
    {
        $pending = BroadcastDelivery::factory()->create(['status' => BroadcastDelivery::STATUS_PENDING]);
        $sent = BroadcastDelivery::factory()->create(['status' => BroadcastDelivery::STATUS_SENT]);
        $failed = BroadcastDelivery::factory()->create(['status' => BroadcastDelivery::STATUS_FAILED]);
        $bounced = BroadcastDelivery::factory()->create(['status' => BroadcastDelivery::STATUS_BOUNCED]);

        $this->assertTrue($pending->isPending());
        $this->assertFalse($pending->isSent());
        $this->assertFalse($pending->isFailed());
        $this->assertFalse($pending->isBounced());

        $this->assertFalse($sent->isPending());
        $this->assertTrue($sent->isSent());
        $this->assertFalse($sent->isFailed());
        $this->assertFalse($sent->isBounced());

        $this->assertFalse($failed->isPending());
        $this->assertFalse($failed->isSent());
        $this->assertTrue($failed->isFailed());
        $this->assertFalse($failed->isBounced());

        $this->assertFalse($bounced->isPending());
        $this->assertFalse($bounced->isSent());
        $this->assertFalse($bounced->isFailed());
        $this->assertTrue($bounced->isBounced());
    }
}
