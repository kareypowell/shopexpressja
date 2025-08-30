<?php

namespace Tests\Unit;

use App\Models\BroadcastMessage;
use App\Models\BroadcastRecipient;
use App\Models\BroadcastDelivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastMessageModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_broadcast_message_has_fillable_attributes()
    {
        $fillable = [
            'subject',
            'content',
            'sender_id',
            'recipient_type',
            'recipient_count',
            'status',
            'scheduled_at',
            'sent_at'
        ];

        $broadcastMessage = new BroadcastMessage();
        $this->assertEquals($fillable, $broadcastMessage->getFillable());
    }

    public function test_broadcast_message_has_correct_casts()
    {
        $broadcastMessage = new BroadcastMessage();
        $casts = $broadcastMessage->getCasts();

        $this->assertEquals('datetime', $casts['scheduled_at']);
        $this->assertEquals('datetime', $casts['sent_at']);
    }

    public function test_broadcast_message_belongs_to_sender()
    {
        $user = User::factory()->create();
        $broadcastMessage = BroadcastMessage::factory()->create(['sender_id' => $user->id]);

        $this->assertInstanceOf(User::class, $broadcastMessage->sender);
        $this->assertEquals($user->id, $broadcastMessage->sender->id);
    }

    public function test_broadcast_message_has_many_recipients()
    {
        $broadcastMessage = BroadcastMessage::factory()->create();
        $recipient = BroadcastRecipient::factory()->create(['broadcast_message_id' => $broadcastMessage->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $broadcastMessage->recipients);
        $this->assertEquals($recipient->id, $broadcastMessage->recipients->first()->id);
    }

    public function test_broadcast_message_has_many_deliveries()
    {
        $broadcastMessage = BroadcastMessage::factory()->create();
        $delivery = BroadcastDelivery::factory()->create(['broadcast_message_id' => $broadcastMessage->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $broadcastMessage->deliveries);
        $this->assertEquals($delivery->id, $broadcastMessage->deliveries->first()->id);
    }

    public function test_drafts_scope()
    {
        BroadcastMessage::factory()->create(['status' => BroadcastMessage::STATUS_DRAFT]);
        BroadcastMessage::factory()->create(['status' => BroadcastMessage::STATUS_SENT]);

        $drafts = BroadcastMessage::drafts()->get();
        $this->assertCount(1, $drafts);
        $this->assertEquals(BroadcastMessage::STATUS_DRAFT, $drafts->first()->status);
    }

    public function test_scheduled_scope()
    {
        BroadcastMessage::factory()->create(['status' => BroadcastMessage::STATUS_SCHEDULED]);
        BroadcastMessage::factory()->create(['status' => BroadcastMessage::STATUS_SENT]);

        $scheduled = BroadcastMessage::scheduled()->get();
        $this->assertCount(1, $scheduled);
        $this->assertEquals(BroadcastMessage::STATUS_SCHEDULED, $scheduled->first()->status);
    }

    public function test_sent_scope()
    {
        BroadcastMessage::factory()->create(['status' => BroadcastMessage::STATUS_SENT]);
        BroadcastMessage::factory()->create(['status' => BroadcastMessage::STATUS_DRAFT]);

        $sent = BroadcastMessage::sent()->get();
        $this->assertCount(1, $sent);
        $this->assertEquals(BroadcastMessage::STATUS_SENT, $sent->first()->status);
    }

    public function test_due_for_sending_scope()
    {
        // Create a scheduled message that's due
        BroadcastMessage::factory()->create([
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => now()->subMinute()
        ]);

        // Create a scheduled message that's not due yet
        BroadcastMessage::factory()->create([
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => now()->addHour()
        ]);

        $due = BroadcastMessage::dueForSending()->get();
        $this->assertCount(1, $due);
    }

    public function test_mark_as_sending()
    {
        $broadcastMessage = BroadcastMessage::factory()->create(['status' => BroadcastMessage::STATUS_DRAFT]);

        $result = $broadcastMessage->markAsSending();

        $this->assertTrue($result);
        $this->assertEquals(BroadcastMessage::STATUS_SENDING, $broadcastMessage->fresh()->status);
    }

    public function test_mark_as_sent()
    {
        $broadcastMessage = BroadcastMessage::factory()->create(['status' => BroadcastMessage::STATUS_SENDING]);

        $result = $broadcastMessage->markAsSent();

        $this->assertTrue($result);
        $fresh = $broadcastMessage->fresh();
        $this->assertEquals(BroadcastMessage::STATUS_SENT, $fresh->status);
        $this->assertNotNull($fresh->sent_at);
    }

    public function test_mark_as_failed()
    {
        $broadcastMessage = BroadcastMessage::factory()->create(['status' => BroadcastMessage::STATUS_SENDING]);

        $result = $broadcastMessage->markAsFailed();

        $this->assertTrue($result);
        $this->assertEquals(BroadcastMessage::STATUS_FAILED, $broadcastMessage->fresh()->status);
    }

    public function test_status_check_methods()
    {
        $draft = BroadcastMessage::factory()->create(['status' => BroadcastMessage::STATUS_DRAFT]);
        $scheduled = BroadcastMessage::factory()->create(['status' => BroadcastMessage::STATUS_SCHEDULED]);
        $sent = BroadcastMessage::factory()->create(['status' => BroadcastMessage::STATUS_SENT]);
        $failed = BroadcastMessage::factory()->create(['status' => BroadcastMessage::STATUS_FAILED]);

        $this->assertTrue($draft->isDraft());
        $this->assertFalse($draft->isScheduled());
        $this->assertFalse($draft->isSent());
        $this->assertFalse($draft->isFailed());

        $this->assertFalse($scheduled->isDraft());
        $this->assertTrue($scheduled->isScheduled());
        $this->assertFalse($scheduled->isSent());
        $this->assertFalse($scheduled->isFailed());

        $this->assertFalse($sent->isDraft());
        $this->assertFalse($sent->isScheduled());
        $this->assertTrue($sent->isSent());
        $this->assertFalse($sent->isFailed());

        $this->assertFalse($failed->isDraft());
        $this->assertFalse($failed->isScheduled());
        $this->assertFalse($failed->isSent());
        $this->assertTrue($failed->isFailed());
    }
}
