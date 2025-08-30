<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\SendBroadcastEmailJob;
use App\Models\BroadcastMessage;
use App\Models\BroadcastDelivery;
use App\Models\User;
use App\Models\Role;
use App\Mail\CustomerBroadcastEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class SendBroadcastEmailJobTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $customer;
    protected $broadcastMessage;
    protected $broadcastDelivery;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $customerRole = Role::factory()->create(['name' => 'customer']);
        $adminRole = Role::factory()->create(['name' => 'admin']);

        // Create users
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'email' => 'customer@example.com'
        ]);

        // Create broadcast message
        $this->broadcastMessage = BroadcastMessage::factory()->create([
            'sender_id' => $this->adminUser->id,
            'subject' => 'Test Broadcast',
            'content' => 'This is a test broadcast message.'
        ]);

        // Create broadcast delivery
        $this->broadcastDelivery = BroadcastDelivery::factory()->create([
            'broadcast_message_id' => $this->broadcastMessage->id,
            'customer_id' => $this->customer->id,
            'email' => $this->customer->email,
            'status' => BroadcastDelivery::STATUS_PENDING
        ]);
    }

    public function test_sends_email_successfully()
    {
        Mail::fake();

        $job = new SendBroadcastEmailJob($this->broadcastDelivery);
        $job->handle();

        // Assert email was queued
        Mail::assertQueued(CustomerBroadcastEmail::class, function ($mail) {
            return $mail->hasTo($this->customer->email);
        });

        // Assert delivery was marked as sent
        $this->broadcastDelivery->refresh();
        $this->assertEquals(BroadcastDelivery::STATUS_SENT, $this->broadcastDelivery->status);
        $this->assertNotNull($this->broadcastDelivery->sent_at);
        $this->assertNull($this->broadcastDelivery->failed_at);
        $this->assertNull($this->broadcastDelivery->error_message);
    }

    public function test_handles_email_sending_failure()
    {
        Mail::shouldReceive('to')
            ->andReturnSelf()
            ->shouldReceive('send')
            ->andThrow(new Exception('SMTP connection failed'));

        $job = new SendBroadcastEmailJob($this->broadcastDelivery);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('SMTP connection failed');

        $job->handle();

        // Assert delivery was marked as failed
        $this->broadcastDelivery->refresh();
        $this->assertEquals(BroadcastDelivery::STATUS_FAILED, $this->broadcastDelivery->status);
        $this->assertNotNull($this->broadcastDelivery->failed_at);
        $this->assertEquals('SMTP connection failed', $this->broadcastDelivery->error_message);
    }

    public function test_loads_relationships_before_sending()
    {
        Mail::fake();

        // Create delivery without loaded relationships
        $delivery = BroadcastDelivery::create([
            'broadcast_message_id' => $this->broadcastMessage->id,
            'customer_id' => $this->customer->id,
            'email' => $this->customer->email,
            'status' => BroadcastDelivery::STATUS_PENDING
        ]);

        $job = new SendBroadcastEmailJob($delivery);
        $job->handle();

        // Assert relationships were loaded and email was queued
        Mail::assertQueued(CustomerBroadcastEmail::class, function ($mail) use ($delivery) {
            return $mail->hasTo($delivery->email) &&
                   $mail->broadcastMessage->id === $this->broadcastMessage->id &&
                   $mail->customer->id === $this->customer->id;
        });
    }

    public function test_failed_method_marks_delivery_as_failed()
    {
        $job = new SendBroadcastEmailJob($this->broadcastDelivery);
        $exception = new Exception('Permanent failure');

        $job->failed($exception);

        // Assert delivery was marked as failed
        $this->broadcastDelivery->refresh();
        $this->assertEquals(BroadcastDelivery::STATUS_FAILED, $this->broadcastDelivery->status);
        $this->assertNotNull($this->broadcastDelivery->failed_at);
        $this->assertEquals('Permanent failure', $this->broadcastDelivery->error_message);
    }

    public function test_logs_sending_events()
    {
        Log::shouldReceive('info')
           ->with('Sending broadcast email', \Mockery::type('array'))
           ->once();

        Log::shouldReceive('info')
           ->with('Broadcast email sent successfully', \Mockery::type('array'))
           ->once();

        Mail::fake();

        $job = new SendBroadcastEmailJob($this->broadcastDelivery);
        $job->handle();
    }

    public function test_logs_warning_on_failure()
    {
        Log::shouldReceive('info')->once(); // Starting log
        Log::shouldReceive('warning')
           ->with('Failed to send broadcast email', \Mockery::type('array'))
           ->once();

        Mail::shouldReceive('to')
            ->andReturnSelf()
            ->shouldReceive('send')
            ->andThrow(new Exception('Network error'));

        $job = new SendBroadcastEmailJob($this->broadcastDelivery);

        try {
            $job->handle();
        } catch (Exception $e) {
            // Expected
        }
    }

    public function test_logs_permanent_failure()
    {
        Log::shouldReceive('error')
           ->with('SendBroadcastEmailJob failed permanently', \Mockery::type('array'))
           ->once();

        $job = new SendBroadcastEmailJob($this->broadcastDelivery);
        $exception = new Exception('Permanent failure');

        $job->failed($exception);
    }

    public function test_backoff_returns_exponential_delays()
    {
        $job = new SendBroadcastEmailJob($this->broadcastDelivery);
        $backoffDelays = $job->backoff();

        $this->assertEquals([30, 120, 480], $backoffDelays);
    }

    public function test_job_properties_are_set_correctly()
    {
        $job = new SendBroadcastEmailJob($this->broadcastDelivery);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->timeout);
        $this->assertEquals($this->broadcastDelivery->id, $job->broadcastDelivery->id);
    }
}