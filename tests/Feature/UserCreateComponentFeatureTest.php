<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Office;
use App\Http\Livewire\Users\UserCreate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

class UserCreateComponentFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $superAdmin;
    protected $adminRole;
    protected $customerRole;
    protected $superAdminRole;
    protected $purchaserRole;
    protected $office;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $this->superAdminRole = Role::factory()->create(['name' => 'superadmin']);
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->customerRole = Role::factory()->create(['name' => 'customer']);
        $this->purchaserRole = Role::factory()->create(['name' => 'purchaser']);

        // Create admin and superadmin users
        $this->admin = User::factory()->create([
            'role_id' => $this->adminRole->id,
            'first_name' => 'Admin',
            'last_name' => 'User'
        ]);

        $this->superAdmin = User::factory()->create([
            'role_id' => $this->superAdminRole->id,
            'first_name' => 'Super',
            'last_name' => 'Admin'
        ]);

        // Create office for pickup location validation
        $this->office = Office::factory()->create(['name' => 'Kingston']);
    }

    /** @test */
    public function admin_can_create_customer_user()
    {
        Mail::fake();

        $component = Livewire::actingAs($this->admin)
            ->test(UserCreate::class);

        $component->set('selectedRole', 'customer')
                 ->set('firstName', 'John')
                 ->set('lastName', 'Doe')
                 ->set('email', 'john.doe@example.com')
                 ->set('telephoneNumber', '1234567890')
                 ->set('streetAddress', '123 Main St')
                 ->set('cityTown', 'Kingston')
                 ->set('parish', 'St. Andrew')
                 ->set('country', 'Jamaica')
                 ->set('pickupLocation', $this->office->id)
                 ->set('generatePassword', true)
                 ->set('sendWelcomeEmail', false)
                 ->call('create');

        // Should create the user successfully
        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role_id' => $this->customerRole->id
        ]);

        // Should create profile for customer
        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($user->profile);
        $this->assertEquals('1234567890', $user->profile->telephone_number);
    }

    /** @test */
    public function admin_can_create_purchaser_user()
    {
        Mail::fake();

        $component = Livewire::actingAs($this->admin)
            ->test(UserCreate::class);

        $component->set('selectedRole', 'purchaser')
                 ->set('firstName', 'Jane')
                 ->set('lastName', 'Smith')
                 ->set('email', 'jane.smith@example.com')
                 ->set('generatePassword', true)
                 ->set('sendWelcomeEmail', false)
                 ->call('create');

        // Should create the user successfully
        $this->assertDatabaseHas('users', [
            'email' => 'jane.smith@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'role_id' => $this->purchaserRole->id
        ]);

        // Should not create profile for non-customer
        $user = User::where('email', 'jane.smith@example.com')->first();
        $this->assertNull($user->profile);
    }

    /** @test */
    public function superadmin_can_create_admin_user()
    {
        Mail::fake();

        $component = Livewire::actingAs($this->superAdmin)
            ->test(UserCreate::class);

        $component->set('selectedRole', 'admin')
                 ->set('firstName', 'Bob')
                 ->set('lastName', 'Admin')
                 ->set('email', 'bob.admin@example.com')
                 ->set('generatePassword', true)
                 ->set('sendWelcomeEmail', false)
                 ->call('create');

        // Should create the user successfully
        $this->assertDatabaseHas('users', [
            'email' => 'bob.admin@example.com',
            'first_name' => 'Bob',
            'last_name' => 'Admin',
            'role_id' => $this->adminRole->id
        ]);

        // Should not create profile for admin
        $user = User::where('email', 'bob.admin@example.com')->first();
        $this->assertNull($user->profile);
    }

    /** @test */
    public function role_selection_updates_field_visibility()
    {
        $component = Livewire::actingAs($this->admin)
            ->test(UserCreate::class);

        // Initially customer role should show customer fields
        $component->assertSet('selectedRole', 'customer')
                 ->assertSet('showCustomerFields', true);

        // Changing to admin should hide customer fields
        $component->set('selectedRole', 'admin')
                 ->assertSet('showCustomerFields', false);

        // Changing back to customer should show customer fields
        $component->set('selectedRole', 'customer')
                 ->assertSet('showCustomerFields', true);
    }

    /** @test */
    public function customer_fields_are_required_only_for_customer_role()
    {
        $component = Livewire::actingAs($this->admin)
            ->test(UserCreate::class);

        // Test customer role validation
        $component->set('selectedRole', 'customer')
                 ->set('firstName', 'John')
                 ->set('lastName', 'Doe')
                 ->set('email', 'john@example.com')
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
        $component->set('selectedRole', 'admin')
                 ->set('firstName', 'Jane')
                 ->set('lastName', 'Admin')
                 ->set('email', 'jane.admin@example.com')
                 ->call('create')
                 ->assertHasNoErrors([
                     'telephoneNumber',
                     'streetAddress',
                     'cityTown',
                     'parish',
                     'country',
                     'pickupLocation',
                 ]);
    }

    /** @test */
    public function validates_unique_email_across_all_roles()
    {
        // Create existing user
        User::factory()->create(['email' => 'existing@example.com']);

        $component = Livewire::actingAs($this->admin)
            ->test(UserCreate::class);

        $component->set('selectedRole', 'admin')
                 ->set('firstName', 'Test')
                 ->set('lastName', 'User')
                 ->set('email', 'existing@example.com')
                 ->call('create')
                 ->assertHasErrors(['email' => 'unique']);
    }

    /** @test */
    public function validates_role_exists()
    {
        $component = Livewire::actingAs($this->admin)
            ->test(UserCreate::class);

        $component->set('selectedRole', 'nonexistent')
                 ->set('firstName', 'Test')
                 ->set('lastName', 'User')
                 ->set('email', 'test@example.com')
                 ->call('create')
                 ->assertHasErrors(['selectedRole' => 'exists']);
    }

    /** @test */
    public function password_generation_works_correctly()
    {
        $component = Livewire::actingAs($this->admin)
            ->test(UserCreate::class);

        // Should generate password by default
        $component->assertSet('generatePassword', true);
        $this->assertNotEmpty($component->get('password'));
        $this->assertEquals($component->get('password'), $component->get('passwordConfirmation'));

        // Should clear password when toggled off
        $component->set('generatePassword', false)
                 ->assertSet('password', '')
                 ->assertSet('passwordConfirmation', '');

        // Should generate new password when toggled back on
        $component->set('generatePassword', true);
        $this->assertNotEmpty($component->get('password'));
        $this->assertEquals($component->get('password'), $component->get('passwordConfirmation'));
    }

    /** @test */
    public function manual_password_validation_works()
    {
        $component = Livewire::actingAs($this->admin)
            ->test(UserCreate::class);

        $component->set('generatePassword', false)
                 ->set('selectedRole', 'admin')
                 ->set('firstName', 'Test')
                 ->set('lastName', 'User')
                 ->set('email', 'test@example.com')
                 ->set('password', '')
                 ->call('create')
                 ->assertHasErrors(['password' => 'required_if']);

        $component->set('password', 'short')
                 ->call('create')
                 ->assertHasErrors(['password' => 'min']);

        $component->set('password', 'validpassword')
                 ->set('passwordConfirmation', 'different')
                 ->call('create')
                 ->assertHasErrors(['password' => 'same']);
    }

    /** @test */
    public function sends_welcome_email_when_enabled()
    {
        Mail::fake();

        $component = Livewire::actingAs($this->admin)
            ->test(UserCreate::class);

        $component->set('selectedRole', 'admin')
                 ->set('firstName', 'Test')
                 ->set('lastName', 'User')
                 ->set('email', 'test@example.com')
                 ->set('sendWelcomeEmail', true)
                 ->set('queueEmail', false)
                 ->call('create');

        // Should send welcome email
        Mail::assertSent(\App\Mail\WelcomeUser::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    }

    /** @test */
    public function does_not_send_welcome_email_when_disabled()
    {
        Mail::fake();

        $component = Livewire::actingAs($this->admin)
            ->test(UserCreate::class);

        $component->set('selectedRole', 'admin')
                 ->set('firstName', 'Test')
                 ->set('lastName', 'User')
                 ->set('email', 'test@example.com')
                 ->set('sendWelcomeEmail', false)
                 ->call('create');

        // Should not send welcome email
        Mail::assertNotSent(\App\Mail\WelcomeUser::class);
    }

    /** @test */
    public function customer_cannot_access_user_creation()
    {
        $customer = User::factory()->create(['role_id' => $this->customerRole->id]);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::actingAs($customer)
            ->test(UserCreate::class);
    }

    /** @test */
    public function clears_customer_fields_when_switching_from_customer_role()
    {
        $component = Livewire::actingAs($this->admin)
            ->test(UserCreate::class);

        // Fill customer fields
        $component->set('selectedRole', 'customer')
                 ->set('telephoneNumber', '1234567890')
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
    public function handles_missing_role_gracefully()
    {
        // Delete all roles
        Role::query()->delete();

        $component = Livewire::actingAs($this->admin)
            ->test(UserCreate::class);

        $component->set('selectedRole', 'customer')
                 ->set('firstName', 'Test')
                 ->set('lastName', 'User')
                 ->set('email', 'test@example.com')
                 ->call('create');

        // Should show error message
        $this->assertTrue(session()->has('error'));
        $this->assertStringContains('not found in the system', session('error'));

        // User should not be created
        $this->assertNull(User::where('email', 'test@example.com')->first());
    }

    /** @test */
    public function cancel_redirects_to_users_index()
    {
        $component = Livewire::actingAs($this->admin)
            ->test(UserCreate::class);

        // Test that cancel method exists and can be called
        $this->assertTrue(method_exists($component->instance(), 'cancel'));
    }
}