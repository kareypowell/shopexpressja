<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BroadcastMessageService;
use App\Models\BroadcastMessage;
use App\Models\BroadcastRecipient;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class BroadcastMessageServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BroadcastMessageService $service;
    protected User $admin;
    protected User $customer1;
    protected User $customer2;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new BroadcastMessageService();
        
        // Create roles
        $adminRole = Role::factory()->admin()->create();
        $customerRole = Role::factory()->customer()->create();
        
        // Create admin user
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        
        // Create customer users
        $this->customer1 = User::factory()->create(['role_id' => $customerRole->id]);
        $this->customer2 = User::factory()->create(['role_id' => $customerRole->id]);
        
        Auth::login($this->admin);
    }

    /** @test */
    public function it_can_create_broadcast_message_for_all_customers()
    {
        $data = [
            'subject' => 'Test Broadcast',
            'content' => 'This is a test broadcast message content.',
            'recipient_type' => 'all',
        ];

        $result = $this->service->createBroadcast($data);

        $this->assertTrue($result['success'], 'Service should return success: ' . ($result['message'] ?? 'No message'));
        $this->assertEquals('Broadcast message created successfully', $result['message']);
        $this->assertInstanceOf(BroadcastMessage::class, $result['broadcast_message']);
        
        $broadcastMessage = $result['broadcast_message'];
        $this->assertEquals($data['subject'], $broadcastMessage->subject);
        $this->assertEquals($data['content'], $broadcastMessage->content);
        $this->assertEquals($this->admin->id, $broadcastMessage->sender_id);
        $this->assertEquals('all', $broadcastMessage->recipient_type);
        $this->assertEquals(BroadcastMessage::STATUS_DRAFT, $broadcastMessage->status);
        
        // Check that recipient count matches the actual active customers count
        $expectedCount = User::activeCustomers()->count();
        $this->assertEquals($expectedCount, $broadcastMessage->recipient_count);
    }

    /** @test */
    public function it_can_create_broadcast_message_for_selected_customers()
    {
        $data = [
            'subject' => 'Test Broadcast',
            'content' => 'This is a test broadcast message content.',
            'recipient_type' => 'selected',
            'selected_customers' => [$this->customer1->id],
        ];

        $result = $this->service->createBroadcast($data);

        $this->assertTrue($result['success']);
        
        $broadcastMessage = $result['broadcast_message'];
        $this->assertEquals('selected', $broadcastMessage->recipient_type);
        $this->assertEquals(1, $broadcastMessage->recipient_count);
        
        // Check that recipient was attached
        $this->assertCount(1, $broadcastMessage->recipients);
        $this->assertEquals($this->customer1->id, $broadcastMessage->recipients->first()->customer_id);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_broadcast()
    {
        $data = [
            'subject' => '',
            'content' => '',
            'recipient_type' => 'invalid',
        ];

        $this->expectException(ValidationException::class);
        $this->service->createBroadcast($data);
    }

    /** @test */
    public function it_validates_selected_customers_when_recipient_type_is_selected()
    {
        $data = [
            'subject' => 'Test Broadcast',
            'content' => 'This is a test broadcast message content.',
            'recipient_type' => 'selected',
            // Missing selected_customers
        ];

        $this->expectException(ValidationException::class);
        $this->service->createBroadcast($data);
    }

    /** @test */
    public function it_can_save_new_draft()
    {
        $data = [
            'subject' => 'Draft Subject',
            'content' => 'Draft content',
            'recipient_type' => 'all',
        ];

        $result = $this->service->saveDraft($data);

        $this->assertTrue($result['success']);
        $this->assertEquals('Draft saved successfully', $result['message']);
        
        $broadcastMessage = $result['broadcast_message'];
        $this->assertEquals($data['subject'], $broadcastMessage->subject);
        $this->assertEquals($data['content'], $broadcastMessage->content);
        $this->assertEquals(BroadcastMessage::STATUS_DRAFT, $broadcastMessage->status);
    }

    /** @test */
    public function it_can_save_draft_with_minimal_data()
    {
        $data = [
            'subject' => 'Just a subject',
        ];

        $result = $this->service->saveDraft($data);

        $this->assertTrue($result['success']);
        
        $broadcastMessage = $result['broadcast_message'];
        $this->assertEquals('Just a subject', $broadcastMessage->subject);
        $this->assertEquals('', $broadcastMessage->content);
        $this->assertEquals('all', $broadcastMessage->recipient_type);
    }

    /** @test */
    public function it_can_update_existing_draft()
    {
        // Create initial draft
        $broadcastMessage = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_DRAFT,
            'subject' => 'Original Subject',
            'content' => 'Original content',
        ]);

        $updateData = [
            'subject' => 'Updated Subject',
            'content' => 'Updated content',
            'recipient_type' => 'selected',
            'selected_customers' => [$this->customer1->id, $this->customer2->id],
        ];

        $result = $this->service->saveDraft($updateData, $broadcastMessage->id);

        $this->assertTrue($result['success']);
        
        $updatedMessage = $result['broadcast_message'];
        $this->assertEquals('Updated Subject', $updatedMessage->subject);
        $this->assertEquals('Updated content', $updatedMessage->content);
        $this->assertEquals('selected', $updatedMessage->recipient_type);
        $this->assertEquals(2, $updatedMessage->recipient_count);
        $this->assertCount(2, $updatedMessage->recipients);
    }

    /** @test */
    public function it_cannot_update_non_draft_message()
    {
        $broadcastMessage = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_SENT,
        ]);

        $updateData = [
            'subject' => 'Updated Subject',
        ];

        $result = $this->service->saveDraft($updateData, $broadcastMessage->id);

        $this->assertFalse($result['success']);
        $this->assertEquals('Only draft messages can be edited', $result['message']);
    }

    /** @test */
    public function it_can_get_all_customer_recipients()
    {
        $broadcastMessage = BroadcastMessage::factory()->create([
            'recipient_type' => 'all',
        ]);

        $recipients = $this->service->getRecipients($broadcastMessage);
        $expectedCount = User::activeCustomers()->count();

        $this->assertCount($expectedCount, $recipients);
        
        // If we have the expected customers, check they're included
        if ($expectedCount >= 2) {
            $recipientIds = $recipients->pluck('id')->toArray();
            $this->assertContains($this->customer1->id, $recipientIds);
            $this->assertContains($this->customer2->id, $recipientIds);
        }
    }

    /** @test */
    public function it_can_get_selected_customer_recipients()
    {
        $broadcastMessage = BroadcastMessage::factory()->create([
            'recipient_type' => 'selected',
        ]);

        // Create recipient record
        BroadcastRecipient::create([
            'broadcast_message_id' => $broadcastMessage->id,
            'customer_id' => $this->customer1->id,
        ]);

        $recipients = $this->service->getRecipients($broadcastMessage);

        $this->assertCount(1, $recipients);
        $recipientIds = $recipients->pluck('id')->toArray();
        $this->assertContains($this->customer1->id, $recipientIds);
        $this->assertNotContains($this->customer2->id, $recipientIds);
    }

    /** @test */
    public function it_can_create_delivery_records()
    {
        $broadcastMessage = BroadcastMessage::factory()->create();
        $recipients = collect([$this->customer1, $this->customer2]);

        $result = $this->service->createDeliveryRecords($broadcastMessage, $recipients);

        $this->assertTrue($result['success']);
        $this->assertEquals('Delivery records created successfully', $result['message']);
        $this->assertEquals(2, $result['count']);

        $this->assertDatabaseHas('broadcast_deliveries', [
            'broadcast_message_id' => $broadcastMessage->id,
            'customer_id' => $this->customer1->id,
            'email' => $this->customer1->email,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('broadcast_deliveries', [
            'broadcast_message_id' => $broadcastMessage->id,
            'customer_id' => $this->customer2->id,
            'email' => $this->customer2->email,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_calculates_recipient_count_correctly_for_all_customers()
    {
        $data = ['recipient_type' => 'all'];
        
        $count = $this->invokeMethod($this->service, 'calculateRecipientCount', [$data]);
        $expectedCount = User::activeCustomers()->count();
        
        $this->assertEquals($expectedCount, $count);
    }

    /** @test */
    public function it_calculates_recipient_count_correctly_for_selected_customers()
    {
        $data = [
            'recipient_type' => 'selected',
            'selected_customers' => [$this->customer1->id, $this->customer2->id],
        ];
        
        $count = $this->invokeMethod($this->service, 'calculateRecipientCount', [$data]);
        
        $this->assertEquals(2, $count);
    }

    /**
     * Helper method to invoke protected/private methods
     */
    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /** @test */
    public function it_can_schedule_broadcast_message()
    {
        $broadcastMessage = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_DRAFT,
        ]);

        $scheduledAt = Carbon::now()->addHours(2)->toDateTimeString();
        
        $result = $this->service->scheduleBroadcast($broadcastMessage->id, $scheduledAt);

        $this->assertTrue($result['success']);
        $this->assertEquals('Broadcast message scheduled successfully', $result['message']);
        
        $updatedMessage = $result['broadcast_message'];
        $this->assertEquals(BroadcastMessage::STATUS_SCHEDULED, $updatedMessage->status);
        $this->assertNotNull($updatedMessage->scheduled_at);
        $this->assertEquals($scheduledAt, $result['scheduled_at']);
    }

    /** @test */
    public function it_cannot_schedule_non_draft_message()
    {
        $broadcastMessage = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_SENT,
        ]);

        $scheduledAt = Carbon::now()->addHours(2)->toDateTimeString();
        
        $result = $this->service->scheduleBroadcast($broadcastMessage->id, $scheduledAt);

        $this->assertFalse($result['success']);
        $this->assertEquals('Only draft messages can be scheduled', $result['message']);
    }

    /** @test */
    public function it_cannot_schedule_message_in_the_past()
    {
        $broadcastMessage = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_DRAFT,
        ]);

        $scheduledAt = Carbon::now()->subHours(2)->toDateTimeString();
        
        $result = $this->service->scheduleBroadcast($broadcastMessage->id, $scheduledAt);

        $this->assertFalse($result['success']);
        $this->assertEquals('Scheduled time must be in the future', $result['message']);
    }

    /** @test */
    public function it_cannot_schedule_message_too_far_in_future()
    {
        $broadcastMessage = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_DRAFT,
        ]);

        $scheduledAt = Carbon::now()->addYears(2)->toDateTimeString();
        
        $result = $this->service->scheduleBroadcast($broadcastMessage->id, $scheduledAt);

        $this->assertFalse($result['success']);
        $this->assertEquals('Scheduled time cannot be more than 1 year in the future', $result['message']);
    }

    /** @test */
    public function it_can_process_scheduled_broadcasts()
    {
        // Create a scheduled broadcast that is due
        $dueBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->subMinutes(5),
            'recipient_type' => 'all',
        ]);

        // Create a scheduled broadcast that is not due yet
        $futureBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->addHours(2),
            'recipient_type' => 'all',
        ]);

        $result = $this->service->processScheduledBroadcasts();

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['processed_count']);
        $this->assertEquals(0, $result['failed_count']);

        // Check that the due broadcast was processed
        $dueBroadcast->refresh();
        $this->assertEquals(BroadcastMessage::STATUS_SENDING, $dueBroadcast->status);
        $this->assertNotNull($dueBroadcast->sent_at);

        // Check that the future broadcast was not processed
        $futureBroadcast->refresh();
        $this->assertEquals(BroadcastMessage::STATUS_SCHEDULED, $futureBroadcast->status);
        $this->assertNull($futureBroadcast->sent_at);
    }

    /** @test */
    public function it_can_cancel_scheduled_broadcast()
    {
        $broadcastMessage = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->addHours(2),
        ]);

        $result = $this->service->cancelScheduledBroadcast($broadcastMessage->id);

        $this->assertTrue($result['success']);
        $this->assertEquals('Scheduled broadcast canceled successfully', $result['message']);
        
        $updatedMessage = $result['broadcast_message'];
        $this->assertEquals(BroadcastMessage::STATUS_DRAFT, $updatedMessage->status);
        $this->assertNull($updatedMessage->scheduled_at);
    }

    /** @test */
    public function it_cannot_cancel_non_scheduled_broadcast()
    {
        $broadcastMessage = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_DRAFT,
        ]);

        $result = $this->service->cancelScheduledBroadcast($broadcastMessage->id);

        $this->assertFalse($result['success']);
        $this->assertEquals('Only scheduled messages can be canceled', $result['message']);
    }

    /** @test */
    public function it_handles_processing_scheduled_broadcasts_with_no_due_messages()
    {
        // Create only future scheduled broadcasts
        BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->addHours(2),
        ]);

        $result = $this->service->processScheduledBroadcasts();

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['processed_count']);
        $this->assertEquals(0, $result['failed_count']);
        $this->assertStringContainsString('Processed 0 broadcasts', $result['message']);
    }
}