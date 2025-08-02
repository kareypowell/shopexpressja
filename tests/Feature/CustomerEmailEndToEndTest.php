<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use App\Http\Livewire\Customers\CustomerCreate;
use App\Mail\CustomerWelcomeEmail;
use App\Services\CustomerEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

class CustomerEmailEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $customerRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        
        // Create customer role
        $this->customerRole = Role::factory()->create(['name' => 'customer']);
    }

    /** @test */
    public function it_completes_full_customer_creation_with_email_workflow()
    {
        Mail::fake();
        
        $this->actingAs($this->adminUser);

        // Step 1: Create customer with email enabled
        $component = Livewire::test(CustomerCreate::class)
            ->set('firstName', 'John')
            ->set('lastName', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('telephoneNumber', '1234567890')
            ->set('streetAddress', '123 Main St')
            ->set('cityTown', 'Kingston')
            ->set('parish', 'Kingston')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 'Kingston Office')
            ->set('sendWelcomeEmail', true)
            ->set('queueEmail', false)
            ->set('generatePassword', true)
            ->call('create');

        // Step 2: Verify customer was created
        $customer = User::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('John', $customer->first_name);
        $this->assertEquals('Doe', $customer->last_name);
        $this->assertEquals($this->customerRole->id, $customer->role_id);

        // Step 3: Verify profile was created with account number
        $this->assertNotNull($customer->profile);
        $this->assertNotNull($customer->profile->account_number);

        // Step 4: Verify email was sent
        Mail::assertSent(CustomerWelcomeEmail::class, function ($mail) use ($customer) {
            return $mail->customer->id === $customer->id &&
                   $mail->temporaryPassword !== null;
        });

        // Step 5: Verify email status tracking
        $this->assertEquals('sent', $component->get('emailStatus'));
        $this->assertNotNull($component->get('emailDeliveryId'));
        $this->assertEquals(0, $component->get('emailRetryCount'));

        // Step 6: Verify success message
        $component->assertSessionHas('success');
        $this->assertStringContainsString('Customer John Doe has been created successfully', session('success'));
    }

    /** @test */
    public function it_handles_email_failure_and_retry_workflow()
    {
        $this->actingAs($this->adminUser);

        // Step 1: Mock email service to fail initially
        $mockEmailService = \Mockery::mock(CustomerEmailService::class);
        $mockEmailService->shouldReceive('sendWelcomeEmail')
            ->once()
            ->andReturn([
                'success' => false,
                'status' => 'failed',
                'message' => 'SMTP connection failed',
                'error' => 'SMTP connection failed'
            ]);

        $this->app->instance(CustomerEmailService::class, $mockEmailService);

        // Step 2: Create customer (should succeed despite email failure)
        $component = Livewire::test(CustomerCreate::class)
            ->set('firstName', 'Jane')
            ->set('lastName', 'Smith')
            ->set('email', 'jane.smith@example.com')
            ->set('telephoneNumber', '1234567890')
            ->set('streetAddress', '456 Oak Ave')
            ->set('cityTown', 'Spanish Town')
            ->set('parish', 'St. Catherine')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 'Spanish Town Office')
            ->set('sendWelcomeEmail', true)
            ->set('queueEmail', false)
            ->call('create');

        // Step 3: Verify customer was still created
        $customer = User::where('email', 'jane.smith@example.com')->first();
        $this->assertNotNull($customer);

        // Step 4: Verify email failure was handled gracefully
        $this->assertEquals('failed', $component->get('emailStatus'));
        $component->assertSessionHas('warning');

        // Step 5: Mock successful retry
        $mockEmailService->shouldReceive('retryFailedEmail')
            ->once()
            ->andReturn([
                'success' => true,
                'status' => 'sent',
                'message' => 'Welcome email sent successfully on retry',
                'retry_count' => 1,
                'delivery_id' => 'email_retry_123',
            ]);

        // Step 6: Retry email sending
        $component->call('retryWelcomeEmail', $customer->id);

        // Step 7: Verify retry was successful
        $this->assertEquals('sent', $component->get('emailStatus'));
        $this->assertEquals(1, $component->get('emailRetryCount'));
    }

    /** @test */
    public function it_handles_queued_email_workflow_with_status_checking()
    {
        Mail::fake();
        Queue::fake();
        
        $this->actingAs($this->adminUser);

        // Step 1: Create customer with queued email
        $component = Livewire::test(CustomerCreate::class)
            ->set('firstName', 'Bob')
            ->set('lastName', 'Johnson')
            ->set('email', 'bob.johnson@example.com')
            ->set('telephoneNumber', '1234567890')
            ->set('streetAddress', '789 Pine St')
            ->set('cityTown', 'Montego Bay')
            ->set('parish', 'St. James')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 'Montego Bay Office')
            ->set('sendWelcomeEmail', true)
            ->set('queueEmail', true)
            ->call('create');

        // Step 2: Verify customer was created
        $customer = User::where('email', 'bob.johnson@example.com')->first();
        $this->assertNotNull($customer);

        // Step 3: Verify email was queued
        Mail::assertQueued(CustomerWelcomeEmail::class);
        $this->assertEquals('queued', $component->get('emailStatus'));

        // Step 4: Mock delivery status check
        $mockEmailService = \Mockery::mock(CustomerEmailService::class);
        $mockEmailService->shouldReceive('checkDeliveryStatus')
            ->once()
            ->andReturn([
                'found' => true,
                'status' => 'processed',
                'message' => 'Email has been processed successfully',
            ]);

        $this->app->instance(CustomerEmailService::class, $mockEmailService);

        // Step 5: Check delivery status
        $component->call('checkEmailDeliveryStatus');

        // Step 6: Verify status was updated
        $this->assertEquals('processed', $component->get('emailStatus'));
    }

    /** @test */
    public function it_prevents_excessive_retry_attempts()
    {
        $this->actingAs($this->adminUser);

        // Create a customer
        $customer = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test.user@example.com',
            'role_id' => $this->customerRole->id,
        ]);

        Profile::factory()->create([
            'user_id' => $customer->id,
            'account_number' => 'ACC123456',
        ]);

        // Set retry count to maximum
        $component = Livewire::test(CustomerCreate::class)
            ->set('emailRetryCount', 3);

        // Attempt to retry (should be blocked)
        $component->call('retryWelcomeEmail', $customer->id);

        // Verify retry was blocked - the retry count should not change
        $this->assertEquals(3, $component->get('emailRetryCount'));
    }

    /** @test */
    public function it_handles_email_configuration_issues()
    {
        $this->actingAs($this->adminUser);

        // Mock email service to simulate configuration issues
        $mockEmailService = \Mockery::mock(CustomerEmailService::class);
        $mockEmailService->shouldReceive('sendWelcomeEmail')
            ->once()
            ->andThrow(new \Exception('Mail configuration not found'));

        $this->app->instance(CustomerEmailService::class, $mockEmailService);

        // Create customer (should handle email service exception)
        $component = Livewire::test(CustomerCreate::class)
            ->set('firstName', 'Config')
            ->set('lastName', 'Test')
            ->set('email', 'config.test@example.com')
            ->set('telephoneNumber', '1234567890')
            ->set('streetAddress', '111 Config St')
            ->set('cityTown', 'Kingston')
            ->set('parish', 'Kingston')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 'Kingston Office')
            ->set('sendWelcomeEmail', true)
            ->call('create');

        // Verify customer was still created
        $customer = User::where('email', 'config.test@example.com')->first();
        $this->assertNotNull($customer);

        // Verify warning message was shown
        $component->assertSessionHas('warning');
        $this->assertStringContainsString('Customer created successfully, but welcome email could not be sent', session('warning'));
    }

    /** @test */
    public function it_toggles_email_details_display_correctly()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(CustomerCreate::class);

        // Initially details should be hidden
        $this->assertFalse($component->get('showEmailDetails'));

        // Toggle to show details
        $component->call('toggleEmailDetails');
        $this->assertTrue($component->get('showEmailDetails'));

        // Toggle to hide details
        $component->call('toggleEmailDetails');
        $this->assertFalse($component->get('showEmailDetails'));
    }

    /** @test */
    public function it_includes_all_required_data_in_welcome_email()
    {
        Mail::fake();
        
        $this->actingAs($this->adminUser);

        $component = Livewire::test(CustomerCreate::class)
            ->set('firstName', 'Complete')
            ->set('lastName', 'Data')
            ->set('email', 'complete.data@example.com')
            ->set('telephoneNumber', '1234567890')
            ->set('streetAddress', '999 Complete St')
            ->set('cityTown', 'Kingston')
            ->set('parish', 'Kingston')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 'Kingston Office')
            ->set('sendWelcomeEmail', true)
            ->set('queueEmail', false)
            ->set('generatePassword', true)
            ->call('create');

        // Verify email contains all required data
        Mail::assertSent(CustomerWelcomeEmail::class, function ($mail) {
            return $mail->customer->email === 'complete.data@example.com' &&
                   $mail->temporaryPassword !== null &&
                   strlen($mail->temporaryPassword) >= 8 &&
                   $mail->customer->profile->account_number !== null;
        });
    }
}