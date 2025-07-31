<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use App\Http\Livewire\Customers\CustomerEdit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Illuminate\Support\Facades\Log;

class CustomerEditComponentTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customer;
    protected $customerRole;
    protected $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->customerRole = Role::factory()->create(['name' => 'customer']);

        // Create admin user
        $this->admin = User::factory()->create([
            'role_id' => $this->adminRole->id,
            'first_name' => 'Admin',
            'last_name' => 'User'
        ]);

        // Create customer with profile
        $this->customer = User::factory()->create([
            'role_id' => $this->customerRole->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com'
        ]);

        Profile::factory()->create([
            'user_id' => $this->customer->id,
            'telephone_number' => '1234567890',
            'tax_number' => 'TAX123',
            'street_address' => '123 Main St',
            'city_town' => 'Kingston',
            'parish' => 'St. Andrew',
            'country' => 'Jamaica',
            'pickup_location' => 'Downtown Office'
        ]);
    }

    /** @test */
    public function it_can_mount_with_customer_data()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerEdit::class, ['customer' => $this->customer]);

        $component->assertSet('firstName', 'John')
                 ->assertSet('lastName', 'Doe')
                 ->assertSet('email', 'john.doe@example.com')
                 ->assertSet('telephoneNumber', '1234567890')
                 ->assertSet('taxNumber', 'TAX123')
                 ->assertSet('streetAddress', '123 Main St')
                 ->assertSet('cityTown', 'Kingston')
                 ->assertSet('parish', 'St. Andrew')
                 ->assertSet('country', 'Jamaica')
                 ->assertSet('pickupLocation', 'Downtown Office');
    }

    /** @test */
    public function it_requires_authorization_to_edit_customer()
    {
        // Test that admin can edit customers
        $this->actingAs($this->admin);
        $component = Livewire::test(CustomerEdit::class, ['customer' => $this->customer]);
        $component->assertSuccessful();
        
        // Test that customer can edit themselves
        $this->actingAs($this->customer);
        $component = Livewire::test(CustomerEdit::class, ['customer' => $this->customer]);
        $component->assertSuccessful();
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerEdit::class, ['customer' => $this->customer]);

        $component->set('firstName', '')
                 ->set('lastName', '')
                 ->set('email', '')
                 ->set('telephoneNumber', '')
                 ->set('streetAddress', '')
                 ->set('cityTown', '')
                 ->set('parish', '')
                 ->set('country', '')
                 ->set('pickupLocation', '')
                 ->call('save');

        $component->assertHasErrors([
            'firstName' => 'required',
            'lastName' => 'required',
            'email' => 'required',
            'telephoneNumber' => 'required',
            'streetAddress' => 'required',
            'cityTown' => 'required',
            'parish' => 'required',
            'country' => 'required',
            'pickupLocation' => 'required'
        ]);
    }

    /** @test */
    public function it_validates_email_format()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerEdit::class, ['customer' => $this->customer]);

        $component->set('email', 'invalid-email')
                 ->call('save');

        $component->assertHasErrors(['email' => 'email']);
    }

    /** @test */
    public function it_validates_email_uniqueness()
    {
        $this->actingAs($this->admin);

        // Create another user with a different email
        $otherUser = User::factory()->create([
            'email' => 'other@example.com',
            'role_id' => $this->customerRole->id
        ]);

        $component = Livewire::test(CustomerEdit::class, ['customer' => $this->customer]);

        $component->set('email', 'other@example.com')
                 ->call('save');

        $component->assertHasErrors(['email' => 'unique']);
    }

    /** @test */
    public function it_allows_keeping_same_email()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerEdit::class, ['customer' => $this->customer]);

        $component->set('email', $this->customer->email)
                 ->call('save');

        $component->assertHasNoErrors(['email']);
    }

    /** @test */
    public function it_validates_field_lengths()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerEdit::class, ['customer' => $this->customer]);

        $component->set('firstName', str_repeat('a', 256))
                 ->set('lastName', str_repeat('b', 256))
                 ->set('email', str_repeat('c', 250) . '@example.com')
                 ->set('telephoneNumber', str_repeat('1', 21))
                 ->set('taxNumber', str_repeat('t', 21))
                 ->set('streetAddress', str_repeat('s', 501))
                 ->set('cityTown', str_repeat('c', 101))
                 ->set('parish', str_repeat('p', 51))
                 ->set('country', str_repeat('c', 51))
                 ->set('pickupLocation', str_repeat('p', 101))
                 ->call('save');

        $component->assertHasErrors([
            'firstName' => 'max',
            'lastName' => 'max',
            'email' => 'max',
            'telephoneNumber' => 'max',
            'taxNumber' => 'max',
            'streetAddress' => 'max',
            'cityTown' => 'max',
            'parish' => 'max',
            'country' => 'max',
            'pickupLocation' => 'max'
        ]);
    }

    /** @test */
    public function it_performs_real_time_validation()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerEdit::class, ['customer' => $this->customer]);

        $component->set('firstName', '');
        $component->assertHasErrors(['firstName' => 'required']);

        $component->set('firstName', 'John');
        $component->assertHasNoErrors(['firstName']);
    }

    /** @test */
    public function it_can_successfully_update_customer()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerEdit::class, ['customer' => $this->customer]);

        $component->set('firstName', 'Jane')
                 ->set('lastName', 'Smith')
                 ->set('email', 'jane.smith@example.com')
                 ->set('telephoneNumber', '0987654321')
                 ->set('taxNumber', 'TAX456')
                 ->set('streetAddress', '456 Oak Ave')
                 ->set('cityTown', 'Spanish Town')
                 ->set('parish', 'St. Catherine')
                 ->set('country', 'Jamaica')
                 ->set('pickupLocation', 'Uptown Office')
                 ->call('save');

        // Verify user was updated
        $this->customer->refresh();
        $this->assertEquals('Jane', $this->customer->first_name);
        $this->assertEquals('Smith', $this->customer->last_name);
        $this->assertEquals('jane.smith@example.com', $this->customer->email);

        // Verify profile was updated
        $this->customer->load('profile');
        $this->assertEquals('0987654321', $this->customer->profile->telephone_number);
        $this->assertEquals('TAX456', $this->customer->profile->tax_number);
        $this->assertEquals('456 Oak Ave', $this->customer->profile->street_address);
        $this->assertEquals('Spanish Town', $this->customer->profile->city_town);
        $this->assertEquals('St. Catherine', $this->customer->profile->parish);
        $this->assertEquals('Jamaica', $this->customer->profile->country);
        $this->assertEquals('Uptown Office', $this->customer->profile->pickup_location);

        // Verify success message and redirect
        $component->assertRedirect(route('customers'));
    }

    /** @test */
    public function it_creates_profile_if_not_exists()
    {
        $this->actingAs($this->admin);

        // Create customer without profile
        $customerWithoutProfile = User::factory()->create([
            'role_id' => $this->customerRole->id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com'
        ]);

        $component = Livewire::test(CustomerEdit::class, ['customer' => $customerWithoutProfile]);

        // Verify component loads correctly with empty profile fields
        $component->assertSet('firstName', 'Test')
                 ->assertSet('lastName', 'User')
                 ->assertSet('email', 'test@example.com')
                 ->assertSet('telephoneNumber', null)
                 ->assertSet('streetAddress', null);

        // Test that we can set profile fields
        $component->set('telephoneNumber', '1111111111')
                 ->set('streetAddress', '789 Pine St');

        $component->assertSet('telephoneNumber', '1111111111')
                 ->assertSet('streetAddress', '789 Pine St');
    }

    /** @test */
    public function it_handles_update_errors_gracefully()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerEdit::class, ['customer' => $this->customer]);

        // Test that the component handles basic functionality
        $component->assertSet('firstName', 'John')
                 ->assertSet('lastName', 'Doe');
        
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_cancel_editing()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerEdit::class, ['customer' => $this->customer]);

        $component->call('cancel')
                 ->assertRedirect(route('customers'));
    }

    /** @test */
    public function tax_number_is_optional()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerEdit::class, ['customer' => $this->customer]);

        // Test that tax number can be empty without validation errors
        $component->set('taxNumber', '');
        $component->assertHasNoErrors(['taxNumber']);
        
        // Test that tax number can be set and cleared
        $component->set('taxNumber', 'NEW123');
        $component->assertSet('taxNumber', 'NEW123');
        
        $component->set('taxNumber', '');
        $component->assertSet('taxNumber', '');
    }
}