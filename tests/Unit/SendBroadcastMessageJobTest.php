<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\SendBroadcastMessageJob;
use App\Jobs\SendBroadcastEmailJob;
use App\Models\BroadcastMessage;
use App\Models\BroadcastDelivery;
use App\Models\User;
use App\Models\Role;
use App\Services\BroadcastMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Exception;

class SendBroadcastMessageJobTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $customerRole;
    protected $customers;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear existing data to ensure clean state
        \DB::table('users')->delete();
        \DB::table('roles')->delete();

        // Create roles
        $this->customerRole = Role::factory()->create(['name' => 'customer']);
        $adminRole = Role::factory()->create(['name' => 'admin']);

        // Create admin user
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);

        // Create test customers
        $this->customers = User::factory()->count(3)->create([
            'role_id' => $this->customerRole->id,
        ]);
    }

    public function test_handles_broadcast_message_with_all_recipients()
    {
        Queue::fake();

        $broadcastMessage = BroadcastMessage::factory()->create([
            'sender_id' => $this->adminUser->id,
            'recipient_type' => BroadcastMessage::RECIPIENT_TYPE_ALL,
            'status' => BroadcastMessage::STATUS_DRAFT
        ]);

        $job = new SendBroadcastMessageJob($broadcastMessage);
        $service = app(BroadcastMessageService::class);

        $job->handle($service);

        // Assert broadcast status was updated
        $broadcastMessage->refresh();
        $this->assertEquals(BroadcastMessage::STATUS_SENT, $broadcastMessage->status);
        $this->assertNotNull($broadcastMessage->sent_at);

        // Assert delivery records were created (should be at least 3 from our setup)
        $this->assertGreaterThanOrEqual(3, $broadcastMessage->deliveries()->count());

        // Assert individual email jobs were queued
        Queue::assertPushed(SendBroadcastEmailJob::class, function ($job) {
            return $job instanceof SendBroadcastEmailJob;
        });
    }

    public function test_handles_broadcast_message_with_selected_recipients()
    {
        Queue::fake();

        $broadcastMessage = BroadcastMessage::factory()->create([
            'sender_id' => $this->adminUser->id,
            'recipient_type' => BroadcastMessage::RECIPIENT_TYPE_SELECTED,
            'status' => BroadcastMessage::STATUS_DRAFT
        ]);

        // Add selected recipients
        foreach ($this->customers->take(2) as $customer) {
            $broadcastMessage->recipients()->create([
                'customer_id' => $customer->id
            ]);
        }

        $job = new SendBroadcastMessageJob($broadcastMessage);
        $service = app(BroadcastMessageService::class);

        $job->handle($service);

        // Assert broadcast status was updated
        $broadcastMessage->refresh();
        $this->assertEquals(BroadcastMessage::STATUS_SENT, $broadcastMessage->status);

        // Assert delivery records were created for selected recipients only
        $this->assertEquals(2, $broadcastMessage->deliveries()->count());

        // Assert individual email jobs were queued
        Queue::assertPushed(SendBroadcastEmailJob::class, 2);
    }

    public function test_handles_broadcast_message_with_no_recipients()
    {
        $broadcastMessage = BroadcastMessage::factory()->create([
            'sender_id' => $this->adminUser->id,
            'recipient_type' => BroadcastMessage::RECIPIENT_TYPE_SELECTED,
            'status' => BroadcastMessage::STATUS_DRAFT
        ]);

        // No recipients added

        $job = new SendBroadcastMessageJob($broadcastMessage);
        $service = app(BroadcastMessageService::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No recipients found for broadcast message');

        $job->handle($service);
    }

    public function test_marks_broadcast_as_failed_on_exception()
    {
        $broadcastMessage = BroadcastMessage::factory()->create([
            'sender_id' => $this->adminUser->id,
            'recipient_type' => BroadcastMessage::RECIPIENT_TYPE_ALL,
            'status' => BroadcastMessage::STATUS_DRAFT
        ]);

        // Mock service to throw exception
        $mockService = $this->createMock(BroadcastMessageService::class);
        $mockService->method('getRecipients')
                   ->willThrowException(new Exception('Service error'));

        $job = new SendBroadcastMessageJob($broadcastMessage);

        try {
            $job->handle($mockService);
        } catch (Exception $e) {
            // Expected
        }

        // Assert broadcast was marked as failed
        $broadcastMessage->refresh();
        $this->assertEquals(BroadcastMessage::STATUS_FAILED, $broadcastMessage->status);
    }

    public function test_failed_method_marks_broadcast_as_failed()
    {
        $broadcastMessage = BroadcastMessage::factory()->create([
            'status' => BroadcastMessage::STATUS_SENDING
        ]);

        $job = new SendBroadcastMessageJob($broadcastMessage);
        $exception = new Exception('Job failed');

        $job->failed($exception);

        // Assert broadcast was marked as failed
        $broadcastMessage->refresh();
        $this->assertEquals(BroadcastMessage::STATUS_FAILED, $broadcastMessage->status);
    }

    public function test_logs_processing_events()
    {
        Log::shouldReceive('info')
           ->with('Starting broadcast message processing', \Mockery::type('array'))
           ->once();

        Log::shouldReceive('info')
           ->with('Broadcast message processing completed successfully', \Mockery::type('array'))
           ->once();

        Queue::fake();

        $broadcastMessage = BroadcastMessage::factory()->create([
            'sender_id' => $this->adminUser->id,
            'recipient_type' => BroadcastMessage::RECIPIENT_TYPE_ALL,
            'status' => BroadcastMessage::STATUS_DRAFT
        ]);

        $job = new SendBroadcastMessageJob($broadcastMessage);
        $service = app(BroadcastMessageService::class);

        $job->handle($service);
    }

    public function test_logs_error_on_failure()
    {
        Log::shouldReceive('info')->once(); // Starting log
        Log::shouldReceive('error')
           ->with('Failed to process broadcast message', \Mockery::type('array'))
           ->once();

        $broadcastMessage = BroadcastMessage::factory()->create([
            'sender_id' => $this->adminUser->id,
            'recipient_type' => BroadcastMessage::RECIPIENT_TYPE_SELECTED,
            'status' => BroadcastMessage::STATUS_DRAFT
        ]);

        $job = new SendBroadcastMessageJob($broadcastMessage);
        $service = app(BroadcastMessageService::class);

        try {
            $job->handle($service);
        } catch (Exception $e) {
            // Expected due to no recipients
        }
    }
}