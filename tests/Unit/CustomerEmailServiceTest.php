<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use App\Services\CustomerEmailService;
use App\Mail\CustomerWelcomeEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class CustomerEmailServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $customerEmailService;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->customerEmailService = new CustomerEmailService();
        
        // Create customer role and user
        $customerRole = Role::factory()->create(['name' => 'customer']);
        $this->customer = User::factory()->create(['role_id' => $customerRole->id]);
        
        Profile::factory()->create([
            'user_id' => $this->customer->id,
            'account_number' => 'ACC123456',
        ]);
    }

    /** @test */
    public function it_sends_welcome_email_successfully()
    {
        Mail::fake();

        $result = $this->customerEmailService->sendWelcomeEmail($this->customer, 'temp123', false);

        $this->assertTrue($result['success']);
        $this->assertEquals('sent', $result['status']);
        $this->assertStringContainsString('sent successfully', $result['message']);
        $this->assertArrayHasKey('delivery_id', $result);
        $this->assertStringStartsWith('email_', $result['delivery_id']);

        Mail::assertSent(CustomerWelcomeEmail::class, function ($mail) {
            return $mail->customer->id === $this->customer->id &&
                   $mail->temporaryPassword === 'temp123';
        });
    }

    /** @test */
    public function it_queues_welcome_email_successfully()
    {
        Mail::fake();

        $result = $this->customerEmailService->sendWelcomeEmail($this->customer, 'temp123', true);

        $this->assertTrue($result['success']);
        $this->assertEquals('queued', $result['status']);
        $this->assertStringContainsString('queued for delivery', $result['message']);
        $this->assertArrayHasKey('delivery_id', $result);

        Mail::assertQueued(CustomerWelcomeEmail::class, function ($mail) {
            return $mail->customer->id === $this->customer->id &&
                   $mail->temporaryPassword === 'temp123';
        });
    }

    /** @test */
    public function it_retries_failed_email_successfully()
    {
        Mail::fake();

        $result = $this->customerEmailService->retryFailedEmail($this->customer, 'welcome', [
            'temporaryPassword' => 'retry123',
            'queue' => false,
            'retry_count' => 2,
            'previous_delivery_id' => 'email_old_123',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('sent', $result['status']);
        $this->assertEquals(2, $result['retry_count']);
        $this->assertEquals('email_old_123', $result['previous_delivery_id']);

        Mail::assertSent(CustomerWelcomeEmail::class, function ($mail) {
            return $mail->customer->id === $this->customer->id &&
                   $mail->temporaryPassword === 'retry123';
        });
    }

    /** @test */
    public function it_checks_delivery_status_for_queued_job()
    {
        // Insert a mock job into the jobs table
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode(['data' => ['delivery_id' => 'email_test_123']]),
            'attempts' => 1,
            'reserved_at' => null,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        $result = $this->customerEmailService->checkDeliveryStatus('email_test_123');

        $this->assertTrue($result['found']);
        $this->assertEquals('queued', $result['status']);
        $this->assertStringContainsString('still queued', $result['message']);
        $this->assertEquals(1, $result['attempts']);
    }

    /** @test */
    public function it_checks_delivery_status_for_failed_job()
    {
        // Insert a mock failed job
        DB::table('failed_jobs')->insert([
            'uuid' => 'test-uuid-123',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['data' => ['delivery_id' => 'email_failed_123']]),
            'exception' => 'Test exception message',
            'failed_at' => now(),
        ]);

        $result = $this->customerEmailService->checkDeliveryStatus('email_failed_123');

        $this->assertTrue($result['found']);
        $this->assertEquals('failed', $result['status']);
        $this->assertStringContainsString('delivery failed', $result['message']);
        $this->assertArrayHasKey('failed_at', $result);
        $this->assertStringContainsString('Test exception', $result['exception']);
    }

    /** @test */
    public function it_assumes_processed_when_job_not_found()
    {
        $result = $this->customerEmailService->checkDeliveryStatus('email_not_found_123');

        $this->assertTrue($result['found']);
        $this->assertEquals('processed', $result['status']);
        $this->assertStringContainsString('processed (likely delivered successfully)', $result['message']);
    }

    /** @test */
    public function it_checks_email_configuration()
    {
        $result = $this->customerEmailService->checkEmailConfiguration();

        $this->assertArrayHasKey('configured', $result);
        $this->assertArrayHasKey('checks', $result);
        $this->assertArrayHasKey('recommendations', $result);
        
        // Should have checks for mail_driver, mail_host, queue_driver, queue_connection
        $this->assertArrayHasKey('mail_driver', $result['checks']);
        $this->assertArrayHasKey('queue_driver', $result['checks']);
    }

    /** @test */
    public function it_generates_unique_delivery_ids()
    {
        Mail::fake();

        $result1 = $this->customerEmailService->sendWelcomeEmail($this->customer, null, false);
        $result2 = $this->customerEmailService->sendWelcomeEmail($this->customer, null, false);

        $this->assertNotEquals($result1['delivery_id'], $result2['delivery_id']);
        $this->assertStringStartsWith('email_', $result1['delivery_id']);
        $this->assertStringStartsWith('email_', $result2['delivery_id']);
    }
}