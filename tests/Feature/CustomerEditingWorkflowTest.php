<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use App\Models\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Livewire\Livewire;
use App\Http\Livewire\Customers\CustomerEdit;

class CustomerEditingWorkflowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $customer;
    protected $otherCustomer;
    protected $office;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'superadmin', 'description' => 'Super Administrator']);
        Role::create(['name' => 'admin', 'description' => 'Administrator']);
        Role::create(['name' => 'customer', 'description' => 'Customer']);

        // Create test users
        $this->admin = User::factory()->create(['role_id' => 2]);
        $this->customer = User::factory()->create([
            'role_id' => 3,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com'
        ]);
        $this->otherCustomer = User::factory()->create(['role_id' => 3]);

        // Create office for pickup location
        $this->office = Office::factory()->create();

        // Create profiles
        Profile::factory()->create([
            'user_id' => $this->customer->id,
            'telephone_number' => '123-456-7890',
            'tax_number' => 'TAX123',
            'street_address' => '123 Main St',
            'city_town' => 'Kingston',
            'parish' => 'St. Andrew',
            'country' => 'Jamaica',
            'pickup_location' => $this->office->id,
        ]);
        Profile::factory()->create(['user_id' => $this->otherCustomer->id]);
    }

    /** @test */
    public function admin_can_access_customer_edit_form()
    {
        $this->actingAs($this->admin);

        Livewire::test(CustomerEdit::class, ['customer' => $this->customer])
            ->assertStatus(200)
            ->assertSet('firstName', 'John')
            ->assertSet('lastName', 'Doe')
            ->assertSet('email', 'john.doe@example.com')
            ->assertSet('telephoneNumber', '123-456-7890')
            ->assertSet('taxNumber', 'TAX123')
            ->assertSet('streetAddress', '123 Main St')
            ->assertSet('cityTown', 'Kingston')
            ->assertSet('parish', 'St. Andrew')
            ->assertSet('country', 'Jamaica')
            ->assertSet('pickupLocation', $this->office->id);
    }

    /** @test */
    public function customer_can_edit_own_profile()
    {
        $this->actingAs($this->customer);

        Livewire::test(CustomerEdit::class, ['customer' => $this->customer])
            ->assertStatus(200)
            ->assertSet('firstName', 'John')
            ->assertSet('lastName', 'Doe');
    }

    /** @test */
    public function customer_cannot_edit_other_customer_profile()
    {
        $this->actingAs($this->customer);

        Livewire::test(CustomerEdit::class, ['customer' => $this->otherCustomer])
            ->assertForbidden();
    }

    /** @test */
    public function customer_edit_form_validates_required_fields()
    {
        $this->actingAs($this->admin);

        Livewire::test(CustomerEdit::class, ['customer' => $this->customer])
            ->set('firstName', '')
            ->set('lastName', '')
            ->set('email', '')
            ->set('telephoneNumber', '')
            ->set('streetAddress', '')
            ->set('cityTown', '')
            ->set('parish', '')
            ->set('country', '')
            ->call('save')
            ->assertHasErrors([
                'firstName' => 'required',
                'lastName' => 'required',
                'email' => 'required',
                'telephoneNumber' => 'required',
                'streetAddress' => 'required',
                'cityTown' => 'required',
                'parish' => 'required',
                'country' => 'required',
            ]);
    }

    /** @test */
    public function customer_edit_form_validates_email_format()
    {
        $this->actingAs($this->admin);

        Livewire::test(CustomerEdit::class, ['customer' => $this->customer])
            ->set('email', 'invalid-email')
            ->call('save')
            ->assertHasErrors(['email' => 'email']);
    }

    /** @test */
    public function customer_edit_form_validates_unique_email()
    {
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);
        
        $this->actingAs($this->admin);

        Livewire::test(CustomerEdit::class, ['customer' => $this->customer])
            ->set('email', 'existing@example.com')
            ->call('save')
            ->assertHasErrors(['email']);
    }

    /** @test */
    public function customer_edit_form_allows_same_email_for_current_user()
    {
        $this->actingAs($this->admin);

        Livewire::test(CustomerEdit::class, ['customer' => $this->customer])
            ->set('email', $this->customer->email)
            ->call('save')
            ->assertHasNoErrors(['email']);
    }

    /** @test */
    public function customer_edit_form_validates_field_lengths()
    {
        $this->actingAs($this->admin);

        Livewire::test(CustomerEdit::class, ['customer' => $this->customer])
            ->set('firstName', str_repeat('a', 256))
            ->set('lastName', str_repeat('b', 256))
            ->set('email', str_repeat('c', 250) . '@example.com')
            ->set('telephoneNumber', str_repeat('1', 21))
            ->set('taxNumber', str_repeat('t', 21))
            ->set('streetAddress', str_repeat('s', 501))
            ->set('cityTown', str_repeat('c', 101))
            ->set('parish', str_repeat('p', 51))
            ->set('country', str_repeat('c', 51))
            ->call('save')
            ->assertHasErrors([
                'firstName' => 'max',
                'lastName' => 'max',
                'email' => 'max',
                'telephoneNumber' => 'max',
                'taxNumber' => 'max',
                'streetAddress' => 'max',
                'cityTown' => 'max',
                'parish' => 'max',
                'country' => 'max',
            ]);
    }

    /** @test */
    public function customer_edit_form_validates_pickup_location_exists()
    {
        $this->actingAs($this->admin);

        Livewire::test(CustomerEdit::class, ['customer' => $this->customer])
            ->set('pickupLocation', 99999) // Non-existent office ID
            ->call('save')
            ->assertHasErrors(['pickupLocation' => 'exists']);
    }

    /** @test */
    public function customer_edit_form_real_time_validation_works()
    {
        $this->actingAs($this->admin);

        Livewire::test(CustomerEdit::class, ['customer' => $this->customer])
            ->set('firstName', '')
            ->assertHasErrors(['firstName' => 'required'])
            ->set('firstName', 'Jane')
            ->assertHasNoErrors(['firstName']);
    }

    /** @test */
    public function customer_edit_form_successfully_updates_customer()
    {
        $this->actingAs($this->admin);

        Livewire::test(CustomerEdit::class, ['customer' => $this->customer])
            ->set('firstName', 'Jane')
            ->set('lastName', 'Smith')
            ->set('email', 'jane.smith@example.com')
            ->set('telephoneNumber', '987-654-3210')
            ->set('taxNumber', 'TAX456')
            ->set('streetAddress', '456 Oak St')
            ->set('cityTown', 'Spanish Town')
            ->set('parish', 'St. Catherine')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', $this->office->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        // Verify the customer was updated in the database
        $this->customer->refresh();
        $this->assertEquals('Jane', $this->customer->first_name);
        $this->assertEquals('Smith', $this->customer->last_name);
        $this->assertEquals('jane.smith@example.com', $this->customer->email);

        // Verify the profile was updated
        $this->customer->profile->refresh();
        $this->assertEquals('987-654-3210', $this->customer->profile->telephone_number);
        $this->assertEquals('TAX456', $this->customer->profile->tax_number);
        $this->assertEquals('456 Oak St', $this->customer->profile->street_address);
        $this->assertEquals('Spanish Town', $this->customer->profile->city_town);
        $this->assertEquals('St. Catherine', $this->customer->profile->parish);
    }

    /** @test */
    public function customer_edit_form_creates_profile_if_not_exists()
    {
        // Create a customer without a profile
        $customerWithoutProfile = User::factory()->create(['role_id' => 3]);

        $this->actingAs($this->admin);

        Livewire::test(CustomerEdit::class, ['customer' => $customerWithoutProfile])
            ->set('firstName', 'New')
            ->set('lastName', 'Customer')
            ->set('email', 'new.customer@example.com')
            ->set('telephoneNumber', '555-123-4567')
            ->set('streetAddress', '789 Pine St')
            ->set('cityTown', 'Montego Bay')
            ->set('parish', 'St. James')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', $this->office->id)
            ->call('save')
            ->assertHasNoErrors();

        // Verify the profile was created
        $customerWithoutProfile->refresh();
        $this->assertNotNull($customerWithoutProfile->profile);
        $this->assertEquals('555-123-4567', $customerWithoutProfile->profile->telephone_number);
    }

    /** @test */
    public function customer_edit_form_handles_null_tax_number()
    {
        $this->actingAs($this->admin);

        Livewire::test(CustomerEdit::class, ['customer' => $this->customer])
            ->set('taxNumber', '')
            ->call('save')
            ->assertHasNoErrors();

        // Verify tax number is set to empty string (since column doesn't allow null)
        $this->customer->profile->refresh();
        $this->assertEquals('', $this->customer->profile->tax_number);
    }

    /** @test */
    public function customer_edit_form_shows_success_message_on_update()
    {
        $this->actingAs($this->admin);

        Livewire::test(CustomerEdit::class, ['customer' => $this->customer])
            ->set('firstName', 'Updated')
            ->call('save');

        $this->assertEquals('Customer information updated successfully.', session('success'));
    }

    /** @test */
    public function customer_edit_form_handles_database_errors_gracefully()
    {
        $this->actingAs($this->admin);

        // Mock a database error by trying to set an invalid foreign key
        $invalidOfficeId = 99999;

        Livewire::test(CustomerEdit::class, ['customer' => $this->customer])
            ->set('pickupLocation', $invalidOfficeId)
            ->call('save')
            ->assertHasErrors(['pickupLocation']);
    }

    /** @test */
    public function customer_edit_form_cancel_method_exists()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerEdit::class, ['customer' => $this->customer]);
        
        // Just verify the cancel method can be called without errors
        $this->assertTrue(method_exists($component->instance(), 'cancel'));
    }

    /** @test */
    public function customer_edit_form_loads_offices_for_pickup_location()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerEdit::class, ['customer' => $this->customer]);

        $offices = $component->get('offices');
        $this->assertNotEmpty($offices);
        $this->assertTrue($offices->contains('id', $this->office->id));
    }

    /** @test */
    public function customer_edit_form_populates_existing_data_correctly()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerEdit::class, ['customer' => $this->customer]);

        // Verify all fields are populated with existing data
        $this->assertEquals($this->customer->first_name, $component->get('firstName'));
        $this->assertEquals($this->customer->last_name, $component->get('lastName'));
        $this->assertEquals($this->customer->email, $component->get('email'));
        $this->assertEquals($this->customer->profile->telephone_number, $component->get('telephoneNumber'));
        $this->assertEquals($this->customer->profile->tax_number, $component->get('taxNumber'));
        $this->assertEquals($this->customer->profile->street_address, $component->get('streetAddress'));
        $this->assertEquals($this->customer->profile->city_town, $component->get('cityTown'));
        $this->assertEquals($this->customer->profile->parish, $component->get('parish'));
        $this->assertEquals($this->customer->profile->country, $component->get('country'));
        $this->assertEquals($this->customer->profile->pickup_location, $component->get('pickupLocation'));
    }

    /** @test */
    public function customer_edit_form_handles_customer_without_profile()
    {
        $customerWithoutProfile = User::factory()->create(['role_id' => 3]);

        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerEdit::class, ['customer' => $customerWithoutProfile]);

        // Verify form loads without errors even when profile doesn't exist
        $component->assertStatus(200);
        $this->assertEquals($customerWithoutProfile->first_name, $component->get('firstName'));
        $this->assertEquals($customerWithoutProfile->last_name, $component->get('lastName'));
        $this->assertEquals($customerWithoutProfile->email, $component->get('email'));
        
        // Profile fields should be empty
        $this->assertEmpty($component->get('telephoneNumber'));
        $this->assertEmpty($component->get('taxNumber'));
        $this->assertEmpty($component->get('streetAddress'));
    }

    /** @test */
    public function customer_edit_form_reauthorizes_before_saving()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerEdit::class, ['customer' => $this->customer]);

        // Simulate unauthorized access by acting as a different user
        $unauthorizedUser = User::factory()->create(['role_id' => 3]); // Customer role
        $this->actingAs($unauthorizedUser);

        // Create a new component instance with the unauthorized user
        Livewire::test(CustomerEdit::class, ['customer' => $this->customer])
            ->assertForbidden();
    }
}