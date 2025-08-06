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
use Livewire\Livewire;

class CustomerEmailIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $customerRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        
        // Create customer role
        $this->customerRole = Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer']);
        
        // Create an office for pickup location
        \App\Models\Office::factory()->create(['id' => 1, 'name' => 1]);
    }

    /** @test */
    public function it_sends_welcome_email_when_creating_customer_with_email_enabled()
    {
        Mail::fake();
        
        $this->actingAs($this->adminUser);

        $component = Livewire::test(CustomerCreate::class)
            ->set('firstName', 'John')
            ->set('lastName', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('telephoneNumber', '1234567890')
            ->set('streetAddress', '123 Main St')
            ->set('cityTown', 'Kingston')
            ->set('parish', 'Kingston')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 1)
            ->set('sendWelcomeEmail', true)
            ->set('queueEmail', false)
            ->call('create');

        // Assert customer was created
        $this->assertDatabaseHas('users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'role_id' => $this->customerRole->id,
        ]);

        // Assert welcome email was sent
        Mail::assertSent(CustomerWelcomeEmail::class, function ($mail) {
            return $mail->customer->email === 'john.doe@example.com' &&
                   $mail->temporaryPassword !== null;
        });
    }

    /** @test */
    public function it_queues_welcome_email_when_queue_option_is_enabled()
    {
        Mail::fake();
        
        $this->actingAs($this->adminUser);

        Livewire::test(CustomerCreate::class)
            ->set('firstName', 'Jane')
            ->set('lastName', 'Smith')
            ->set('email', 'jane.smith@example.com')
            ->set('telephoneNumber', '1234567890')
            ->set('streetAddress', '456 Oak Ave')
            ->set('cityTown', 'Spanish Town')
            ->set('parish', 'St. Catherine')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 1)
            ->set('sendWelcomeEmail', true)
            ->set('queueEmail', true)
            ->call('create');

        // Assert welcome email was queued
        Mail::assertQueued(CustomerWelcomeEmail::class, function ($mail) {
            return $mail->customer->email === 'jane.smith@example.com';
        });
    }

    /** @test */
    public function it_does_not_send_email_when_email_option_is_disabled()
    {
        Mail::fake();
        
        $this->actingAs($this->adminUser);

        Livewire::test(CustomerCreate::class)
            ->set('firstName', 'Bob')
            ->set('lastName', 'Johnson')
            ->set('email', 'bob.johnson@example.com')
            ->set('telephoneNumber', '1234567890')
            ->set('streetAddress', '789 Pine St')
            ->set('cityTown', 'Montego Bay')
            ->set('parish', 'St. James')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 1)
            ->set('sendWelcomeEmail', false)
            ->call('create');

        // Assert customer was created
        $this->assertDatabaseHas('users', [
            'email' => 'bob.johnson@example.com',
        ]);

        // Assert no email was sent
        Mail::assertNothingSent();
    }

    /** @test */
    public function it_handles_email_sending_failures_gracefully()
    {
        $this->actingAs($this->adminUser);

        // Create a partial mock of CustomerEmailService to simulate failure
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

        $component = Livewire::test(CustomerCreate::class)
            ->set('firstName', 'Alice')
            ->set('lastName', 'Brown')
            ->set('email', 'alice.brown@example.com')
            ->set('telephoneNumber', '1234567890')
            ->set('streetAddress', '321 Elm St')
            ->set('cityTown', 'Mandeville')
            ->set('parish', 'Manchester')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 1)
            ->set('sendWelcomeEmail', true)
            ->set('queueEmail', false)
            ->call('create');

        // Assert customer was still created despite email failure
        $this->assertDatabaseHas('users', [
            'email' => 'alice.brown@example.com',
        ]);

        // Assert warning message was set
        $component->assertSessionHas('warning');
    }

    /** @test */
    public function it_includes_temporary_password_in_email_when_password_is_generated()
    {
        Mail::fake();
        
        $this->actingAs($this->adminUser);

        Livewire::test(CustomerCreate::class)
            ->set('firstName', 'Charlie')
            ->set('lastName', 'Wilson')
            ->set('email', 'charlie.wilson@example.com')
            ->set('telephoneNumber', '1234567890')
            ->set('streetAddress', '654 Maple Ave')
            ->set('cityTown', 'May Pen')
            ->set('parish', 'Clarendon')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 1)
            ->set('sendWelcomeEmail', true)
            ->set('generatePassword', true)
            ->set('queueEmail', false)
            ->call('create');

        Mail::assertSent(CustomerWelcomeEmail::class, function ($mail) {
            return $mail->customer->email === 'charlie.wilson@example.com' &&
                   $mail->temporaryPassword !== null &&
                   strlen($mail->temporaryPassword) >= 8;
        });
    }

    /** @test */
    public function it_does_not_include_password_in_email_when_password_is_manually_set()
    {
        Mail::fake();
        
        $this->actingAs($this->adminUser);

        Livewire::test(CustomerCreate::class)
            ->set('firstName', 'Diana')
            ->set('lastName', 'Davis')
            ->set('email', 'diana.davis@example.com')
            ->set('telephoneNumber', '1234567890')
            ->set('streetAddress', '987 Cedar St')
            ->set('cityTown', 'Port Antonio')
            ->set('parish', 'Portland')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 1)
            ->set('sendWelcomeEmail', true)
            ->set('generatePassword', false)
            ->set('password', 'manualpassword123')
            ->set('passwordConfirmation', 'manualpassword123')
            ->set('queueEmail', false)
            ->call('create');

        Mail::assertSent(CustomerWelcomeEmail::class, function ($mail) {
            return $mail->customer->email === 'diana.davis@example.com' &&
                   $mail->temporaryPassword === null;
        });
    }

    /** @test */
    public function it_can_retry_failed_email_delivery()
    {
        Mail::fake();
        
        $this->actingAs($this->adminUser);

        // Create a customer first
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

        $component = Livewire::test(CustomerCreate::class)
            ->set('emailStatus', 'failed')
            ->set('emailMessage', 'SMTP connection failed')
            ->set('generatePassword', true)
            ->set('password', 'testpassword123')
            ->set('queueEmail', false)
            ->call('retryWelcomeEmail', $customer->id);

        // Assert email was sent on retry
        Mail::assertSent(CustomerWelcomeEmail::class, function ($mail) use ($customer) {
            return $mail->customer->id === $customer->id;
        });
    }

    /** @test */
    public function it_displays_email_status_correctly_in_component()
    {
        Mail::fake();
        
        $this->actingAs($this->adminUser);

        $component = Livewire::test(CustomerCreate::class)
            ->set('firstName', 'Status')
            ->set('lastName', 'Test')
            ->set('email', 'status.test@example.com')
            ->set('telephoneNumber', '1234567890')
            ->set('streetAddress', '111 Status St')
            ->set('cityTown', 'Kingston')
            ->set('parish', 'Kingston')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 1)
            ->set('sendWelcomeEmail', true)
            ->set('queueEmail', false)
            ->call('create');

        // Check that email status was set
        $this->assertEquals('sent', $component->get('emailStatus'));
        $this->assertStringContainsString('sent successfully', $component->get('emailMessage'));
    }

    /** @test */
    public function it_shows_appropriate_session_messages_for_email_status()
    {
        Mail::fake();
        
        $this->actingAs($this->adminUser);

        // Test queued email
        $component = Livewire::test(CustomerCreate::class)
            ->set('firstName', 'Queue')
            ->set('lastName', 'Test')
            ->set('email', 'queue.test@example.com')
            ->set('telephoneNumber', '1234567890')
            ->set('streetAddress', '222 Queue St')
            ->set('cityTown', 'Kingston')
            ->set('parish', 'Kingston')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 1)
            ->set('sendWelcomeEmail', true)
            ->set('queueEmail', true)
            ->call('create');

        $component->assertSessionHas('email_info');
        $this->assertStringContainsString('queued for delivery', session('email_info'));
    }

    /** @test */
    public function it_tracks_email_delivery_id_and_retry_count()
    {
        Mail::fake();
        
        $this->actingAs($this->adminUser);

        $component = Livewire::test(CustomerCreate::class)
            ->set('firstName', 'Tracking')
            ->set('lastName', 'Test')
            ->set('email', 'tracking.test@example.com')
            ->set('telephoneNumber', '1234567890')
            ->set('streetAddress', '333 Tracking St')
            ->set('cityTown', 'Kingston')
            ->set('parish', 'Kingston')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 1)
            ->set('sendWelcomeEmail', true)
            ->set('queueEmail', false)
            ->call('create');

        // Check that email delivery ID was set
        $this->assertNotNull($component->get('emailDeliveryId'));
        $this->assertStringStartsWith('email_', $component->get('emailDeliveryId'));
        
        // Check initial retry count
        $this->assertEquals(0, $component->get('emailRetryCount'));
    }

    /** @test */
    public function it_limits_email_retry_attempts()
    {
        $this->actingAs($this->adminUser);

        // Create a customer first
        $customer = User::factory()->create([
            'first_name' => 'Retry',
            'last_name' => 'Limit',
            'email' => 'retry.limit@example.com',
            'role_id' => $this->customerRole->id,
        ]);

        Profile::factory()->create([
            'user_id' => $customer->id,
            'account_number' => 'ACC789012',
        ]);

        $component = Livewire::test(CustomerCreate::class)
            ->set('emailRetryCount', 3)
            ->call('retryWelcomeEmail', $customer->id);

        // Should not increment retry count when limit is exceeded
        $this->assertEquals(3, $component->get('emailRetryCount'));
    }

    /** @test */
    public function it_can_toggle_email_details_display()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(CustomerCreate::class)
            ->set('showEmailDetails', false)
            ->call('toggleEmailDetails');

        $this->assertTrue($component->get('showEmailDetails'));

        $component->call('toggleEmailDetails');
        $this->assertFalse($component->get('showEmailDetails'));
    }

    /** @test */
    public function it_can_check_email_delivery_status()
    {
        $this->actingAs($this->adminUser);

        // Mock the email service
        $mockEmailService = \Mockery::mock(CustomerEmailService::class);
        $mockEmailService->shouldReceive('checkDeliveryStatus')
            ->once()
            ->with('test_delivery_id')
            ->andReturn([
                'found' => true,
                'status' => 'processed',
                'message' => 'Email has been processed successfully',
            ]);

        $this->app->instance(CustomerEmailService::class, $mockEmailService);

        $component = Livewire::test(CustomerCreate::class)
            ->set('emailDeliveryId', 'test_delivery_id')
            ->call('checkEmailDeliveryStatus');

        // Check that the method was called successfully by verifying the component state
        $this->assertEquals('processed', $component->get('emailStatus'));
    }

    /** @test */
    public function it_handles_missing_delivery_id_when_checking_status()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(CustomerCreate::class)
            ->set('emailDeliveryId', null)
            ->call('checkEmailDeliveryStatus');

        // Check that the method handled the missing delivery ID correctly
        $this->assertNull($component->get('emailDeliveryId'));
    }
}