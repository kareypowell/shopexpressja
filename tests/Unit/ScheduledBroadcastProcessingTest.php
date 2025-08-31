<?php

namespace Tests\Unit;

use App\Models\BroadcastMessage;
use App\Models\BroadcastRecipient;
use App\Models\BroadcastDelivery;
use App\Models\User;
use App\Services\BroadcastMessageService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ScheduledBroadcastProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected BroadcastMessageService $service;
    protected User $admin;
    protected $customers;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new BroadcastMessageService();
        
        // Create admin user
        $this->admin = User::factory()->create([
            'role_id' => 2, // Admin role
            'email' => 'admin@test.com'
        ]);
        
        // Create test customers
        $this->customers = User::factory()->count(3)->create([
            'role_id' => 3, // Customer role
            'deleted_at' => null
        ]);
        
        Mail::fake();
    }

    /** @test */
    public function it_processes_due_scheduled_broadcasts()
    {
        // Create a scheduled broadcast that is due
        $broadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Due Broadcast',
            'content' => 'This broadcast is due',
            'recipient_type' => 'all',
            'recipient_count' => $this->customers->count(),
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->subMinutes(10),
        ]);

        // Process scheduled broadcasts
        $result = $this->service->processScheduledBroadcasts();

        // Assert processing was successful
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['processed_count']);
        $this->assertEquals(0, $result['failed_count']);

        // Assert broadcast status was updated
        $broadcast->refresh();
        $this->assertEquals(BroadcastMessage::STATUS_SENT, $broadcast->status);
        $this->assertNotNull($broadcast->sent_at);

        // Assert delivery records were created
        $deliveryCount = BroadcastDelivery::where('broadcast_message_id', $broadcast->id)->count();
        $this->assertEquals($this->customers->count(), $deliveryCount);
    }

    /** @test */
    public function it_ignores_future_scheduled_broadcasts()
    {
        // Create a scheduled broadcast that is not yet due
        $broadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Future Broadcast',
            'content' => 'This broadcast is in the future',
            'recipient_type' => 'all',
            'recipient_count' => $this->customers->count(),
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->addHours(2),
        ]);

        // Process scheduled broadcasts
        $result = $this->service->processScheduledBroadcasts();

        // Assert no broadcasts were processed
        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['processed_count']);
        $this->assertEquals(0, $result['failed_count']);

        // Assert broadcast status remains unchanged
        $broadcast->refresh();
        $this->assertEquals(BroadcastMessage::STATUS_SCHEDULED, $broadcast->status);
        $this->assertNull($broadcast->sent_at);
    }

    /** @test */
    public function it_processes_multiple_due_broadcasts()
    {
        // Create multiple scheduled broadcasts that are due
        $broadcasts = collect();
        for ($i = 0; $i < 3; $i++) {
            $broadcasts->push(BroadcastMessage::factory()->create([
                'sender_id' => $this->admin->id,
                'subject' => "Broadcast {$i}",
                'content' => "Content for broadcast {$i}",
                'recipient_type' => 'all',
                'recipient_count' => $this->customers->count(),
                'status' => BroadcastMessage::STATUS_SCHEDULED,
                'scheduled_at' => Carbon::now()->subMinutes(5 + $i),
            ]));
        }

        // Process scheduled broadcasts
        $result = $this->service->processScheduledBroadcasts();

        // Assert all broadcasts were processed
        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['processed_count']);
        $this->assertEquals(0, $result['failed_count']);

        // Assert all broadcasts were updated
        foreach ($broadcasts as $broadcast) {
            $broadcast->refresh();
            $this->assertEquals(BroadcastMessage::STATUS_SENT, $broadcast->status);
            $this->assertNotNull($broadcast->sent_at);
        }
    }

    /** @test */
    public function it_handles_selected_recipients_correctly()
    {
        // Select only 2 customers
        $selectedCustomers = $this->customers->take(2);
        
        $broadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Selected Recipients',
            'content' => 'This goes to selected recipients',
            'recipient_type' => 'selected',
            'recipient_count' => $selectedCustomers->count(),
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->subMinutes(5),
        ]);

        // Create recipient records
        foreach ($selectedCustomers as $customer) {
            BroadcastRecipient::create([
                'broadcast_message_id' => $broadcast->id,
                'customer_id' => $customer->id,
            ]);
        }

        // Process scheduled broadcasts
        $result = $this->service->processScheduledBroadcasts();

        // Assert processing was successful
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['processed_count']);

        // Assert delivery records were created only for selected recipients
        $deliveryCount = BroadcastDelivery::where('broadcast_message_id', $broadcast->id)->count();
        $this->assertEquals($selectedCustomers->count(), $deliveryCount);

        // Verify correct customers received delivery records
        $deliveredCustomerIds = BroadcastDelivery::where('broadcast_message_id', $broadcast->id)
            ->pluck('customer_id')
            ->sort()
            ->values()
            ->toArray();
        
        $expectedCustomerIds = $selectedCustomers->pluck('id')->sort()->values()->toArray();
        $this->assertEquals($expectedCustomerIds, $deliveredCustomerIds);
    }

    /** @test */
    public function it_handles_individual_broadcast_failures()
    {
        // Create a valid broadcast
        $validBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Valid Broadcast',
            'content' => 'This is valid',
            'recipient_type' => 'all',
            'recipient_count' => $this->customers->count(),
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->subMinutes(5),
        ]);

        // Create a broadcast with no recipients (will fail)
        $invalidBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Invalid Broadcast',
            'content' => 'This has no recipients',
            'recipient_type' => 'selected',
            'recipient_count' => 0,
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->subMinutes(3),
        ]);

        // Process scheduled broadcasts
        $result = $this->service->processScheduledBroadcasts();

        // Assert partial success (valid broadcast processed, invalid failed)
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['processed_count']);
        $this->assertEquals(1, $result['failed_count']);
        $this->assertCount(1, $result['errors']);

        // Assert valid broadcast was processed
        $validBroadcast->refresh();
        $this->assertEquals(BroadcastMessage::STATUS_SENT, $validBroadcast->status);

        // Assert invalid broadcast was marked as failed
        $invalidBroadcast->refresh();
        $this->assertEquals(BroadcastMessage::STATUS_FAILED, $invalidBroadcast->status);
    }

    /** @test */
    public function it_prevents_duplicate_processing_by_updating_status()
    {
        // Create a scheduled broadcast
        $broadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Duplicate Test',
            'content' => 'Testing duplicate prevention',
            'recipient_type' => 'all',
            'recipient_count' => $this->customers->count(),
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->subMinutes(5),
        ]);

        // Process scheduled broadcasts
        $result1 = $this->service->processScheduledBroadcasts();

        // Assert first processing was successful
        $this->assertTrue($result1['success']);
        $this->assertEquals(1, $result1['processed_count']);

        // Process again immediately
        $result2 = $this->service->processScheduledBroadcasts();

        // Assert second processing found no broadcasts to process
        $this->assertTrue($result2['success']);
        $this->assertEquals(0, $result2['processed_count']);

        // Assert broadcast is no longer in scheduled status
        $broadcast->refresh();
        $this->assertNotEquals(BroadcastMessage::STATUS_SCHEDULED, $broadcast->status);
    }

    /** @test */
    public function it_logs_processing_activities()
    {
        Log::spy();

        // Create a scheduled broadcast
        $broadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Logging Test',
            'content' => 'Testing logging',
            'recipient_type' => 'all',
            'recipient_count' => $this->customers->count(),
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->subMinutes(5),
        ]);

        // Process scheduled broadcasts
        $this->service->processScheduledBroadcasts();

        // Assert individual email sending was logged
        Log::shouldHaveReceived('info')
            ->with('Email sent successfully', \Mockery::type('array'))
            ->times($this->customers->count());
    }

    /** @test */
    public function it_handles_service_exceptions_gracefully()
    {
        Log::spy();

        // Create a broadcast with no recipients (selected type but no recipients)
        $broadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Error Test',
            'content' => 'This will cause an error',
            'recipient_type' => 'selected', // Selected but no recipients will be added
            'recipient_count' => 0,
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->subMinutes(5),
        ]);

        // Process scheduled broadcasts
        $result = $this->service->processScheduledBroadcasts();

        // Assert the service handled the error gracefully
        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['processed_count']);
        $this->assertEquals(1, $result['failed_count']);
        $this->assertCount(1, $result['errors']);

        // Assert error was logged
        Log::shouldHaveReceived('error')
            ->with('Failed to process scheduled broadcast', \Mockery::type('array'))
            ->once();
    }

    /** @test */
    public function it_creates_delivery_records_with_correct_status()
    {
        // Create a scheduled broadcast
        $broadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Delivery Status Test',
            'content' => 'Testing delivery record creation',
            'recipient_type' => 'all',
            'recipient_count' => $this->customers->count(),
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->subMinutes(5),
        ]);

        // Process scheduled broadcasts
        $result = $this->service->processScheduledBroadcasts();

        // Assert processing was successful
        $this->assertTrue($result['success']);

        // Assert delivery records were created with correct initial status
        $deliveries = BroadcastDelivery::where('broadcast_message_id', $broadcast->id)->get();
        
        $this->assertCount($this->customers->count(), $deliveries);
        
        foreach ($deliveries as $delivery) {
            $this->assertEquals('sent', $delivery->status);
            $this->assertNotNull($delivery->sent_at);
            $this->assertNull($delivery->failed_at);
            $this->assertNull($delivery->error_message);
            $this->assertContains($delivery->customer_id, $this->customers->pluck('id')->toArray());
        }
    }

    /** @test */
    public function it_returns_correct_result_structure()
    {
        // Create scheduled broadcasts
        BroadcastMessage::factory()->count(2)->create([
            'sender_id' => $this->admin->id,
            'recipient_type' => 'all',
            'recipient_count' => $this->customers->count(),
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->subMinutes(5),
        ]);

        // Process scheduled broadcasts
        $result = $this->service->processScheduledBroadcasts();

        // Assert result structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('processed_count', $result);
        $this->assertArrayHasKey('failed_count', $result);
        $this->assertArrayHasKey('errors', $result);

        // Assert result values
        $this->assertTrue($result['success']);
        $this->assertIsString($result['message']);
        $this->assertIsInt($result['processed_count']);
        $this->assertIsInt($result['failed_count']);
        $this->assertIsArray($result['errors']);
        $this->assertEquals(2, $result['processed_count']);
        $this->assertEquals(0, $result['failed_count']);
    }
}