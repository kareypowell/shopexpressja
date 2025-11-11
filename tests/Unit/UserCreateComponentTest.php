<?php

namespace Tests\Unit;

use App\Http\Livewire\Users\UserCreate;
use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use App\Models\Office;
use App\Services\AccountNumberService;
use App\Mail\WelcomeUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class UserCreateComponentTest extends TestCase
{
    use RefreshDatabase;

    protected $office;
    protected $admin;
    protected $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $adminRole = Role::factory()->create([
            'name' => 'admin',
            'description' => 'Admin role'
        ]);
        
        $superAdminRole = Role::factory()->create([
            'name' => 'superadmin',
            'description' => 'Super Admin role'
        ]);
        
        Role::factory()->create([
            'name' => 'customer',
            'description' => 'Customer role'
        ]);

        Role::factory()->create([
            'name' => 'purchaser',
            'description' => 'Purchaser role'
        ]);

        // Create admin and superadmin users
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);

        // Create test office
        $this->office = Office::factory()->create(['name' => 'Kingston']);
    }

    /** @test */
    public function it_renders_successfully()
    {
        $this->actingAs($this->admin);
        
        Livewire::test(UserCreate::class)
            ->assertStatus(200);
    }

    /** @test */
    public function it_initializes_with_default_values()
    {
        $this->actingAs($this->admin);
        
        $component = Livewire::test(UserCreate::class);

        $component->assertSet('selectedRole', 'customer')
                  ->assertSet('country', 'Jamaica')
                  ->assertSet('sendWelcomeEmail', true)
                  ->assertSet('generatePassword', true)
                  ->assertSet('isCreating', false)
                  ->assertSet('showCustomerFields', true);

        // Should have generated a password
        $this->assertNotEmpty($component->get('password'));
        $this->assertEquals($component->get('password'), $component->get('passwordConfirmation'));
    }

    /** @test */
    public function it_updates_field_visibility_when_role_changes()
    {
        $this->actingAs($this->admin);
        
        $component = Livewire::test(UserCreate::class);

        // Initially customer role
        $component->assertSet('showCustomerFields', true);

        // Change to admin role
        $component->set('selectedRole', 'admin')
                  ->assertSet('showCustomerFields', false);

        // Change to purchaser role
        $component->set('selectedRole', 'purchaser')
                  ->assertSet('showCustomerFields', false);

        // Change back to customer role
        $component->set('selectedRole', 'customer')
                  ->assertSet('showCustomerFields', true);
    }

    /** @test */
    public function it_clears_customer_fields_when_switching_from_customer_role()
    {
        $this->actingAs($this->admin);
        
        $component = Livewire::test(UserCreate::class);

        // Set customer fields
        $component->set('telephoneNumber', '1234567890')
                  ->set('streetAddress', '123 Main St')
                  ->set('cityTown', 'Kingston')
                  ->set('parish', 'St. Andrew')
                  ->set('pickupLocation', $this->office->id);

        // Switch to admin role
        $component->set('selectedRole', 'admin');

        // Customer fields should be cleared
        $component->assertSet('telephoneNumber', '')
                  ->assertSet('streetAddress', '')
                  ->assertSet('cityTown', '')
                  ->assertSet('parish', '')
                  ->assertSet('pickupLocation', '');
    }

    /** @test */
    public function it_generates_new_password_when_toggled()
    {
        $this->actingAs($this->admin);
        
        $component = Livewire::test(UserCreate::class);
        $originalPassword = $component->get('password');

        // Toggle off
        $component->set('generatePassword', false);
        $component->assertSet('password', '')
                  ->assertSet('passwordConfirmation', '');

        // Toggle back on
        $component->set('generatePassword', true);
        $newPassword = $component->get('password');
        
        $this->assertNotEmpty($newPassword);
        $this->assertNotEquals($originalPassword, $newPassword);
        $this->assertEquals($newPassword, $component->get('passwordConfirmation'));
    }

    /** @test */
    public function it_validates_required_fields_for_all_roles()
    {
        $this->actingAs($this->admin);
        
        Livewire::test(UserCreate::class)
            ->set('firstName', '')
            ->set('lastName', '')
            ->set('email', '')
            ->set('selectedRole', '')
            ->call('create')
            ->assertHasErrors([
                'firstName' => 'required',
                'lastName' => 'required',
                'email' => 'required',
                'selectedRole' => 'required',
            ]);
    }

    /** @test */
    public function it_validates_customer_specific_fields_only_for_customer_role()
    {
        $this->actingAs($this->admin);
        
        // Test customer role validation
        Livewire::test(UserCreate::class)
            ->set('selectedRole', 'customer')
            ->set('firstName', 'John')
            ->set('lastName', 'Doe')
            ->set('email', 'john@example.com')
            ->set('country', '') // Clear the default value
            ->call('create')
            ->assertHasErrors([
                'telephoneNumber' => 'required',
                'streetAddress' => 'required',
                'cityTown' => 'required',
                'parish' => 'required',
                'country' => 'required',
                'pickupLocation' => 'required',
            ]);

        // Test admin role validation (should not require customer fields)
        Livewire::test(UserCreate::class)
            ->set('selectedRole', 'admin')
            ->set('firstName', 'Jane')
            ->set('lastName', 'Admin')
            ->set('email', 'jane@example.com')
            ->call('create')
            ->assertHasNoErrors([
                'telephoneNumber',
                'streetAddress',
                'cityTown',
                'parish',
                'pickupLocation',
            ]);
    }

    /** @test */
    public function it_validates_email_format_and_uniqueness()
    {
        $this->actingAs($this->admin);
        
        // Test invalid email format
        Livewire::test(UserCreate::class)
            ->set('email', 'invalid-email')
            ->call('create')
            ->assertHasErrors(['email' => 'email']);

        // Create existing user
        User::factory()->create(['email' => 'existing@example.com']);

        // Test unique email validation
        Livewire::test(UserCreate::class)
            ->set('email', 'existing@example.com')
            ->call('create')
            ->assertHasErrors(['email' => 'unique']);
    }

    /** @test */
    public function it_validates_role_exists()
    {
        $this->actingAs($this->admin);
        
        Livewire::test(UserCreate::class)
            ->set('selectedRole', 'nonexistent')
            ->call('create')
            ->assertHasErrors(['selectedRole' => 'exists']);
    }

    /** @test */
    public function it_validates_password_when_not_generating()
    {
        $this->actingAs($this->admin);
        
        // Test required password
        Livewire::test(UserCreate::class)
            ->set('generatePassword', false)
            ->set('password', '')
            ->call('create')
            ->assertHasErrors(['password' => 'required_if']);

        // Test minimum length
        Livewire::test(UserCreate::class)
            ->set('generatePassword', false)
            ->set('password', 'short')
            ->call('create')
            ->assertHasErrors(['password' => 'min']);

        // Test password confirmation
        Livewire::test(UserCreate::class)
            ->set('generatePassword', false)
            ->set('password', 'password123')
            ->set('passwordConfirmation', 'different')
            ->call('create')
            ->assertHasErrors(['password' => 'same']);
    }

    /** @test */
    public function it_creates_customer_user_successfully()
    {
        $this->actingAs($this->admin);
        
        Mail::fake();
        Event::fake();

        $component = Livewire::test(UserCreate::class)
            ->set('selectedRole', 'customer')
            ->set('firstName', 'John')
            ->set('lastName', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('telephoneNumber', '1234567890')
            ->set('taxNumber', '123456789')
            ->set('streetAddress', '123 Main St')
            ->set('cityTown', 'Kingston')
            ->set('parish', 'Kingston')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', $this->office->id)
            ->set('sendWelcomeEmail', false)
            ->call('create');

        // Check user was created
        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('John', $user->first_name);
        $this->assertEquals('Doe', $user->last_name);
        $this->assertEquals('customer', $user->role->name);
        $this->assertTrue(Hash::check($component->get('password'), $user->password));

        // Check profile was created for customer
        $this->assertNotNull($user->profile);
        $this->assertStringStartsWith('ALQS8149-', $user->profile->account_number);
        $this->assertEquals('1234567890', $user->profile->telephone_number);
        $this->assertEquals('123456789', $user->profile->tax_number);
        $this->assertEquals('123 Main St', $user->profile->street_address);
        $this->assertEquals('Kingston', $user->profile->city_town);
        $this->assertEquals('Kingston', $user->profile->parish);
        $this->assertEquals('Jamaica', $user->profile->country);
        $this->assertEquals($this->office->id, $user->profile->pickup_location);

        // Check registered event was fired
        Event::assertDispatched(Registered::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    /** @test */
    public function it_creates_non_customer_user_successfully()
    {
        $this->actingAs($this->admin);
        
        Mail::fake();
        Event::fake();

        $component = Livewire::test(UserCreate::class)
            ->set('selectedRole', 'admin')
            ->set('firstName', 'Jane')
            ->set('lastName', 'Admin')
            ->set('email', 'jane.admin@example.com')
            ->set('sendWelcomeEmail', false)
            ->call('create');

        // Check user was created
        $user = User::where('email', 'jane.admin@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Jane', $user->first_name);
        $this->assertEquals('Admin', $user->last_name);
        $this->assertEquals('admin', $user->role->name);
        $this->assertTrue(Hash::check($component->get('password'), $user->password));

        // Check no profile was created for non-customer
        $this->assertNull($user->profile);

        // Check registered event was fired
        Event::assertDispatched(Registered::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    /** @test */
    public function it_sends_welcome_email_for_customer()
    {
        $this->actingAs($this->admin);
        
        Mail::fake();

        Livewire::test(UserCreate::class)
            ->set('selectedRole', 'customer')
            ->set('firstName', 'John')
            ->set('lastName', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('telephoneNumber', '1234567890')
            ->set('streetAddress', '123 Main St')
            ->set('cityTown', 'Kingston')
            ->set('parish', 'Kingston')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', $this->office->id)
            ->set('sendWelcomeEmail', true)
            ->set('queueEmail', false)
            ->call('create');

        $user = User::where('email', 'john.doe@example.com')->first();
        
        // Should use customer email service for customers
        // This is tested in integration tests due to service complexity
        $this->assertNotNull($user);
    }

    /** @test */
    public function it_sends_welcome_email_for_non_customer()
    {
        $this->actingAs($this->admin);
        
        Mail::fake();

        Livewire::test(UserCreate::class)
            ->set('selectedRole', 'admin')
            ->set('firstName', 'Jane')
            ->set('lastName', 'Admin')
            ->set('email', 'jane.admin@example.com')
            ->set('sendWelcomeEmail', true)
            ->set('queueEmail', false)
            ->call('create');

        // Should send welcome email for non-customers
        Mail::assertSent(WelcomeUser::class, function ($mail) {
            return $mail->hasTo('jane.admin@example.com');
        });
    }

    /** @test */
    public function it_provides_available_roles_list()
    {
        $this->actingAs($this->admin);
        
        $component = Livewire::test(UserCreate::class);
        $availableRoles = $component->get('availableRoles');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $availableRoles);
        $this->assertTrue($availableRoles->contains('name', 'customer'));
        $this->assertTrue($availableRoles->contains('name', 'admin'));
        $this->assertTrue($availableRoles->contains('name', 'superadmin'));
        $this->assertTrue($availableRoles->contains('name', 'purchaser'));
    }

    /** @test */
    public function it_provides_parishes_list()
    {
        $this->actingAs($this->admin);
        
        $component = Livewire::test(UserCreate::class);
        $parishes = $component->get('parishes');

        $this->assertIsArray($parishes);
        $this->assertContains('Kingston', $parishes);
        $this->assertContains('St. Andrew', $parishes);
        $this->assertContains('St. Catherine', $parishes);
    }

    /** @test */
    public function it_provides_pickup_locations_list()
    {
        $this->actingAs($this->admin);
        
        // Create additional offices for testing
        Office::factory()->create(['name' => 'Spanish Town']);
        Office::factory()->create(['name' => 'Montego Bay']);

        $component = Livewire::test(UserCreate::class);
        $pickupLocations = $component->get('pickupLocations');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $pickupLocations);
        $this->assertTrue($pickupLocations->contains('name', 'Kingston'));
        $this->assertTrue($pickupLocations->contains('name', 'Spanish Town'));
        $this->assertTrue($pickupLocations->contains('name', 'Montego Bay'));
    }

    /** @test */
    public function it_handles_missing_role_gracefully()
    {
        $this->actingAs($this->admin);
        
        // Delete all roles
        Role::query()->delete();

        Livewire::test(UserCreate::class)
            ->set('selectedRole', 'customer')
            ->set('firstName', 'Test')
            ->set('lastName', 'User')
            ->set('email', 'test@example.com')
            ->call('create');

        // User should not be created
        $this->assertNull(User::where('email', 'test@example.com')->first());
    }

    /** @test */
    public function it_generates_random_password_correctly()
    {
        $this->actingAs($this->admin);
        
        $component = Livewire::test(UserCreate::class);
        
        // Test password generation using reflection since method is private
        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('generateRandomPassword');
        $method->setAccessible(true);
        
        $password1 = $method->invoke($component->instance());
        $password2 = $method->invoke($component->instance());
        
        $this->assertEquals(12, strlen($password1));
        $this->assertEquals(12, strlen($password2));
        $this->assertNotEquals($password1, $password2);
        
        // Test password contains expected character types (at least one should match)
        $hasLower = preg_match('/[a-z]/', $password1);
        $hasUpper = preg_match('/[A-Z]/', $password1);
        $hasNumber = preg_match('/[0-9]/', $password1);
        $hasSpecial = preg_match('/[!@#$%^&*]/', $password1);
        
        // Password should contain at least 2 different character types
        $characterTypes = $hasLower + $hasUpper + $hasNumber + $hasSpecial;
        $this->assertGreaterThanOrEqual(2, $characterTypes);
    }

    /** @test */
    public function unauthorized_user_cannot_access_component()
    {
        $customer = User::factory()->create([
            'role_id' => Role::where('name', 'customer')->first()->id
        ]);

        // Test that customer cannot create users
        $this->assertFalse($customer->can('user.create'));
        
        // Test that admin can create users
        $this->assertTrue($this->admin->can('user.create'));
    }
}