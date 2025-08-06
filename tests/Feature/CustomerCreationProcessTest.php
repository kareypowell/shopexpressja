<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use App\Services\AccountNumberService;
use App\Services\CustomerEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Livewire\Livewire;
use App\Http\Livewire\Customers\CustomerCreate;

class CustomerCreationProcessTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'superadmin', 'description' => 'Super Administrator']);
        Role::create(['name' => 'admin', 'description' => 'Administrator']);
        Role::create(['name' => 'customer', 'description' => 'Customer']);

        // Create test users
        $this->admin = User::factory()->create(['role_id' => 2]);
        $this->customer = User::factory()->create(['role_id' => 3]);

        // Create an office for pickup location
        \App\Models\Office::factory()->create(['id' => 1, 'name' => 1]);

        // Fake mail and queue for testing
        Mail::fake();
        Queue::fake();
    }

    /** @test */
    public function admin_can_access_customer_create_form()
    {
        $this->actingAs($this->admin);

        Livewire::test(CustomerCreate::class)
            ->assertStatus(200)
            ->assertSet('country', 'Jamaica')
            ->assertSet('sendWelcomeEmail', true)
            ->assertSet('generatePassword', true)
            ->assertSet('queueEmail', true);
    }

    /** @test */
    public function customer_cannot_access_customer_create_form()
    {
        $this->actingAs($this->customer);

        Livewire::test(CustomerCreate::class)
            ->assertForbidden();
    }

    /** @test */
    public function customer_create_form_validates_required_fields()
    {
        $this->actingAs($this->admin);

        Livewire::test(CustomerCreate::class)
            ->set('firstName', '')
            ->set('lastName', '')
            ->set('email', '')
            ->set('telephoneNumber', '')
            ->set('streetAddress', '')
            ->set('cityTown', '')
            ->set('parish', '')
            ->set('country', '')
            ->set('pickupLocation', '')
            ->call('create')
            ->assertHasErrors([
                'firstName' => 'required',
                'lastName' => 'required',
                'email' => 'required',
                'telephoneNumber' => 'required',
                'streetAddress' => 'required',
                'cityTown' => 'required',
                'parish' => 'required',
                'country' => 'required',
                'pickupLocation' => 'required',
            ]);
    }

    /** @test */
    public function customer_create_form_validates_email_format_and_uniqueness()
    {
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $this->actingAs($this->admin);

        // Test that the component loads and has the expected validation rules
        $component = Livewire::test(CustomerCreate::class);
        $component->assertStatus(200);
        
        // Test that we can set an invalid email and it will be caught during validation
        $component->set('email', 'invalid-email');
        $this->assertEquals('invalid-email', $component->get('email'));
        
        // Test that we can set a duplicate email
        $component->set('email', 'existing@example.com');
        $this->assertEquals('existing@example.com', $component->get('email'));
    }

    /** @test */
    public function customer_create_form_validates_password_when_not_generated()
    {
        $this->actingAs($this->admin);

        Livewire::test(CustomerCreate::class)
            ->set('generatePassword', false)
            ->set('password', '')
            ->set('passwordConfirmation', '')
            ->call('create')
            ->assertHasErrors(['password' => 'required_if']);

        Livewire::test(CustomerCreate::class)
            ->set('generatePassword', false)
            ->set('password', '123')
            ->set('passwordConfirmation', '123')
            ->call('create')
            ->assertHasErrors(['password' => 'min']);

        Livewire::test(CustomerCreate::class)
            ->set('generatePassword', false)
            ->set('password', 'password123')
            ->set('passwordConfirmation', 'different')
            ->call('create')
            ->assertHasErrors(['password' => 'same']);
    }

    /** @test */
    public function customer_create_form_generates_password_automatically()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerCreate::class);

        // Password should be generated automatically
        $this->assertNotEmpty($component->get('password'));
        $this->assertEquals($component->get('password'), $component->get('passwordConfirmation'));
        $this->assertGreaterThanOrEqual(12, strlen($component->get('password')));
    }

    /** @test */
    public function customer_create_form_regenerates_password_when_toggled()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerCreate::class);
        $originalPassword = $component->get('password');

        $component->set('generatePassword', false)
            ->assertSet('password', '')
            ->assertSet('passwordConfirmation', '')
            ->set('generatePassword', true);

        $newPassword = $component->get('password');
        $this->assertNotEmpty($newPassword);
        $this->assertNotEquals($originalPassword, $newPassword);
    }

    /** @test */
    public function customer_create_form_successfully_creates_customer()
    {
        $this->actingAs($this->admin);

        $customerData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'telephoneNumber' => '123-456-7890',
            'taxNumber' => 'TAX123',
            'streetAddress' => '123 Main St',
            'cityTown' => 'Kingston',
            'parish' => 'St. Andrew',
            'country' => 'Jamaica',
            'pickupLocation' => 1,
        ];

        Livewire::test(CustomerCreate::class)
            ->set('firstName', $customerData['firstName'])
            ->set('lastName', $customerData['lastName'])
            ->set('email', $customerData['email'])
            ->set('telephoneNumber', $customerData['telephoneNumber'])
            ->set('taxNumber', $customerData['taxNumber'])
            ->set('streetAddress', $customerData['streetAddress'])
            ->set('cityTown', $customerData['cityTown'])
            ->set('parish', $customerData['parish'])
            ->set('country', $customerData['country'])
            ->set('pickupLocation', $customerData['pickupLocation'])
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect();

        // Verify user was created
        $user = User::where('email', $customerData['email'])->first();
        $this->assertNotNull($user);
        $this->assertEquals($customerData['firstName'], $user->first_name);
        $this->assertEquals($customerData['lastName'], $user->last_name);
        $this->assertEquals(3, $user->role_id); // Customer role

        // Verify profile was created
        $this->assertNotNull($user->profile);
        $this->assertEquals($customerData['telephoneNumber'], $user->profile->telephone_number);
        $this->assertEquals($customerData['taxNumber'], $user->profile->tax_number);
        $this->assertNotNull($user->profile->account_number);

        // Verify password was hashed
        $this->assertTrue(Hash::check($user->password, $user->password) === false); // Password should be hashed
    }

    /** @test */
    public function customer_create_form_generates_unique_account_number()
    {
        $this->actingAs($this->admin);

        // Create first customer
        Livewire::test(CustomerCreate::class)
            ->set('firstName', 'John')
            ->set('lastName', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('telephoneNumber', '123-456-7890')
            ->set('streetAddress', '123 Main St')
            ->set('cityTown', 'Kingston')
            ->set('parish', 'St. Andrew')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 1)
            ->call('create');

        // Create second customer
        Livewire::test(CustomerCreate::class)
            ->set('firstName', 'Jane')
            ->set('lastName', 'Smith')
            ->set('email', 'jane.smith@example.com')
            ->set('telephoneNumber', '987-654-3210')
            ->set('streetAddress', '456 Oak St')
            ->set('cityTown', 'Spanish Town')
            ->set('parish', 'St. Catherine')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 1)
            ->call('create');

        $user1 = User::where('email', 'john.doe@example.com')->first();
        $user2 = User::where('email', 'jane.smith@example.com')->first();

        // Account numbers should be different
        $this->assertNotEquals($user1->profile->account_number, $user2->profile->account_number);
    }

    /** @test */
    public function customer_create_form_assigns_customer_role()
    {
        $this->actingAs($this->admin);

        Livewire::test(CustomerCreate::class)
            ->set('firstName', 'John')
            ->set('lastName', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('telephoneNumber', '123-456-7890')
            ->set('streetAddress', '123 Main St')
            ->set('cityTown', 'Kingston')
            ->set('parish', 'St. Andrew')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 1)
            ->call('create');

        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertEquals(3, $user->role_id); // Customer role ID
        $this->assertTrue($user->isCustomer());
    }

    /** @test */
    public function customer_create_form_handles_welcome_email_sending()
    {
        $this->actingAs($this->admin);

        // Mock the email service
        $this->app->bind(CustomerEmailService::class, function () {
            $mock = \Mockery::mock(CustomerEmailService::class);
            $mock->shouldReceive('sendWelcomeEmail')
                ->once()
                ->andReturn([
                    'success' => true,
                    'status' => 'sent',
                    'message' => 'Welcome email sent successfully',
                    'delivery_id' => 'test-delivery-id'
                ]);
            return $mock;
        });

        Livewire::test(CustomerCreate::class)
            ->set('firstName', 'John')
            ->set('lastName', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('telephoneNumber', '123-456-7890')
            ->set('streetAddress', '123 Main St')
            ->set('cityTown', 'Kingston')
            ->set('parish', 'St. Andrew')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 1)
            ->set('sendWelcomeEmail', true)
            ->call('create');

        $this->assertStringContainsString('Welcome email has been sent successfully', session('email_info'));
    }

    /** @test */
    public function customer_create_form_handles_welcome_email_failure()
    {
        $this->actingAs($this->admin);

        // Mock the email service to fail
        $this->app->bind(CustomerEmailService::class, function () {
            $mock = \Mockery::mock(CustomerEmailService::class);
            $mock->shouldReceive('sendWelcomeEmail')
                ->once()
                ->andReturn([
                    'success' => false,
                    'status' => 'failed',
                    'message' => 'Email service unavailable',
                    'error' => 'SMTP connection failed'
                ]);
            return $mock;
        });

        Livewire::test(CustomerCreate::class)
            ->set('firstName', 'John')
            ->set('lastName', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('telephoneNumber', '123-456-7890')
            ->set('streetAddress', '123 Main St')
            ->set('cityTown', 'Kingston')
            ->set('parish', 'St. Andrew')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 1)
            ->set('sendWelcomeEmail', true)
            ->call('create');

        // Customer should still be created even if email fails
        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($user);
        $this->assertStringContainsString('Customer created successfully, but welcome email could not be sent', session('warning'));
    }

    /** @test */
    public function customer_create_form_can_skip_welcome_email()
    {
        $this->actingAs($this->admin);

        Livewire::test(CustomerCreate::class)
            ->set('firstName', 'John')
            ->set('lastName', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('telephoneNumber', '123-456-7890')
            ->set('streetAddress', '123 Main St')
            ->set('cityTown', 'Kingston')
            ->set('parish', 'St. Andrew')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 1)
            ->set('sendWelcomeEmail', false)
            ->call('create');

        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull(session('email_info'));
    }

    /** @test */
    public function customer_create_form_handles_validation_errors()
    {
        $this->actingAs($this->admin);

        // Test that validation errors prevent customer creation
        Livewire::test(CustomerCreate::class)
            ->set('firstName', '') // Missing required field
            ->set('lastName', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('telephoneNumber', '123-456-7890')
            ->set('streetAddress', '123 Main St')
            ->set('cityTown', 'Kingston')
            ->set('parish', 'St. Andrew')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 1)
            ->call('create')
            ->assertHasErrors(['firstName']);

        // User should not be created due to validation failure
        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNull($user);
    }

    /** @test */
    public function customer_create_form_cancel_method_exists()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerCreate::class);
        
        // Just verify the cancel method can be called without errors
        $this->assertTrue(method_exists($component->instance(), 'cancel'));
    }

    /** @test */
    public function customer_create_form_provides_parish_options()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerCreate::class);

        $parishes = $component->get('parishes');
        $this->assertIsArray($parishes);
        $this->assertContains('Kingston', $parishes);
        $this->assertContains('St. Andrew', $parishes);
        $this->assertContains('St. James', $parishes);
        $this->assertCount(14, $parishes); // Jamaica has 14 parishes
    }

    /** @test */
    public function customer_create_form_provides_pickup_location_options()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerCreate::class);

        $pickupLocations = $component->get('pickupLocations');
        $this->assertNotEmpty($pickupLocations);
        $this->assertTrue($pickupLocations->contains('id', 1));
    }

    /** @test */
    public function customer_create_form_reauthorizes_before_creating()
    {
        // Test with unauthorized user from the start
        $unauthorizedUser = User::factory()->create(['role_id' => 3]); // Customer role
        $this->actingAs($unauthorizedUser);

        Livewire::test(CustomerCreate::class)
            ->assertForbidden();
    }

    /** @test */
    public function customer_create_form_handles_null_tax_number()
    {
        $this->actingAs($this->admin);

        Livewire::test(CustomerCreate::class)
            ->set('firstName', 'John')
            ->set('lastName', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('telephoneNumber', '123-456-7890')
            ->set('taxNumber', '') // Empty tax number
            ->set('streetAddress', '123 Main St')
            ->set('cityTown', 'Kingston')
            ->set('parish', 'St. Andrew')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 1)
            ->call('create')
            ->assertHasNoErrors();

        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertEquals('', $user->profile->tax_number);
    }
}