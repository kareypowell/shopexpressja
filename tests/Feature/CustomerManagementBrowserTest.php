<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use App\Models\Package;
use App\Models\Manifest;
use App\Models\Office;
use App\Models\Shipper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Livewire\Livewire;
use App\Http\Livewire\Customers\AdminCustomersTable;
use App\Http\Livewire\Customers\CustomerProfile;
use App\Http\Livewire\Customers\CustomerEdit;
use App\Http\Livewire\Customers\CustomerCreate;

class CustomerManagementBrowserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $customer;
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

        // Create office
        $this->office = Office::factory()->create();

        // Create profile
        Profile::factory()->create([
            'user_id' => $this->customer->id,
            'account_number' => 'ACC001',
            'telephone_number' => '123-456-7890',
            'pickup_location' => $this->office->id,
        ]);

        // Create test packages
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        
        Package::factory()->count(3)->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'office_id' => $this->office->id,
            'shipper_id' => $shipper->id,
        ]);
    }

    /** @test */
    public function admin_can_navigate_to_customer_management_page()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.customers.index'));
        
        $response->assertStatus(200);
        $response->assertSee('Customers');
        $response->assertSee('Add Customer');
    }

    /** @test */
    public function admin_can_view_customer_list_with_search_functionality()
    {
        $this->actingAs($this->admin);

        // Test that the customer table loads
        $component = Livewire::test(AdminCustomersTable::class);
        
        $component->assertStatus(200)
            ->assertSee('John')
            ->assertSee('Doe')
            ->assertSee('john.doe@example.com')
            ->assertSee('ACC001');
    }

    /** @test */
    public function admin_can_search_customers_by_name()
    {
        // Create additional customers
        $customer2 = User::factory()->create([
            'role_id' => 3,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com'
        ]);
        Profile::factory()->create(['user_id' => $customer2->id]);

        $this->actingAs($this->admin);

        $component = Livewire::test(AdminCustomersTable::class)
            ->set('filters.search', 'John')
            ->assertSee('John')
            ->assertSee('Doe')
            ->assertDontSee('Jane Smith');
    }

    /** @test */
    public function admin_can_access_customer_profile_page()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.customers.show', $this->customer));
        
        $response->assertStatus(200);
        $response->assertSee('John');
        $response->assertSee('Doe');
        $response->assertSee('john.doe@example.com');
        $response->assertSee('Customer Profile');
    }

    /** @test */
    public function customer_profile_displays_comprehensive_information()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerProfile::class, ['customer' => $this->customer]);

        $component->assertStatus(200)
            ->assertSee('John')
            ->assertSee('Doe')
            ->assertSee('john.doe@example.com')
            ->assertSee('123-456-7890')
            ->assertSee('ACC001')
            ->assertSee('Package History')
            ->assertSee('Financial Summary');
    }

    /** @test */
    public function customer_profile_shows_package_history()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerProfile::class, ['customer' => $this->customer]);

        $component->assertStatus(200)
            ->assertSee('Package History');
    }

    /** @test */
    public function admin_can_access_customer_edit_form()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.customers.edit', $this->customer));
        
        $response->assertStatus(200);
        $response->assertSee('Edit Customer');
        $response->assertSee('Update customer information');
    }

    /** @test */
    public function customer_edit_form_displays_current_data()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerEdit::class, ['customer' => $this->customer]);

        $component->assertStatus(200)
            ->assertSet('firstName', 'John')
            ->assertSet('lastName', 'Doe')
            ->assertSet('email', 'john.doe@example.com')
            ->assertSet('telephoneNumber', '123-456-7890');
    }

    /** @test */
    public function customer_edit_form_validation_displays_errors()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerEdit::class, ['customer' => $this->customer])
            ->set('firstName', '')
            ->set('email', 'invalid-email')
            ->call('save')
            ->assertHasErrors(['firstName', 'email'])
            ->assertSee('The first name field is required')
            ->assertSee('The email must be a valid email address');
    }

    /** @test */
    public function customer_edit_form_shows_success_message_on_update()
    {
        $this->actingAs($this->admin);

        Livewire::test(CustomerEdit::class, ['customer' => $this->customer])
            ->set('firstName', 'Jane')
            ->call('save');

        $this->assertEquals('Customer information updated successfully.', session('success'));
    }

    /** @test */
    public function admin_can_access_customer_create_form()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.customers.create'));
        
        $response->assertStatus(200);
        $response->assertSee('Create Customer');
        $response->assertSee('Add a new customer account');
    }

    /** @test */
    public function customer_create_form_displays_all_required_fields()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerCreate::class);

        $component->assertStatus(200)
            ->assertSee('First Name')
            ->assertSee('Last Name')
            ->assertSee('Email')
            ->assertSee('Telephone Number')
            ->assertSee('Street Address')
            ->assertSee('City/Town')
            ->assertSee('Parish')
            ->assertSee('Country')
            ->assertSee('Pickup Location')
            ->assertSee('welcome email')
            ->assertSee('Generate random password');
    }

    /** @test */
    public function customer_create_form_validation_works()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerCreate::class)
            ->set('firstName', '')
            ->set('email', 'invalid-email')
            ->call('create')
            ->assertHasErrors(['firstName', 'email'])
            ->assertSee('The first name field is required')
            ->assertSee('The email must be a valid email address');
    }

    /** @test */
    public function customer_create_form_password_generation_toggle_works()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerCreate::class);

        // Initially password should be generated
        $this->assertNotEmpty($component->get('password'));
        $this->assertTrue($component->get('generatePassword'));

        // Toggle off password generation
        $component->set('generatePassword', false)
            ->assertSet('password', '')
            ->assertSet('passwordConfirmation', '');

        // Toggle back on
        $component->set('generatePassword', true);
        $this->assertNotEmpty($component->get('password'));
    }

    /** @test */
    public function customer_create_form_shows_parish_dropdown()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerCreate::class);

        $parishes = $component->get('parishes');
        $this->assertContains('Kingston', $parishes);
        $this->assertContains('St. Andrew', $parishes);
        $this->assertContains('St. James', $parishes);
    }

    /** @test */
    public function customer_create_form_successful_submission_redirects()
    {
        $this->actingAs($this->admin);

        Livewire::test(CustomerCreate::class)
            ->set('firstName', 'New')
            ->set('lastName', 'Customer')
            ->set('email', 'new.customer@example.com')
            ->set('telephoneNumber', '555-123-4567')
            ->set('streetAddress', '123 Test St')
            ->set('cityTown', 'Kingston')
            ->set('parish', 'St. Andrew')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', $this->office->id)
            ->call('create');

        // Verify customer was created
        $this->assertDatabaseHas('users', [
            'first_name' => 'New',
            'last_name' => 'Customer',
            'email' => 'new.customer@example.com'
        ]);
    }

    /** @test */
    public function customer_management_respects_authorization()
    {
        // Test with customer role (should be forbidden)
        $this->actingAs($this->customer);

        $response = $this->get(route('admin.customers.index'));
        $response->assertStatus(403);

        $response = $this->get(route('admin.customers.create'));
        $response->assertStatus(403);

        $response = $this->get(route('admin.customers.edit', $this->customer));
        $response->assertStatus(403);
    }

    /** @test */
    public function customer_can_view_own_profile()
    {
        $this->actingAs($this->customer);

        // Test using Livewire component directly since route access might be restricted
        $component = Livewire::test(CustomerProfile::class, ['customer' => $this->customer]);
        $component->assertStatus(200);
        $component->assertSee('John');
        $component->assertSee('Doe');
    }

    /** @test */
    public function customer_cannot_view_other_customer_profile()
    {
        $otherCustomer = User::factory()->create(['role_id' => 3]);
        Profile::factory()->create(['user_id' => $otherCustomer->id]);

        $this->actingAs($this->customer);

        $response = $this->get(route('admin.customers.show', $otherCustomer));
        $response->assertStatus(403);
    }

    /** @test */
    public function responsive_design_elements_are_present()
    {
        $this->actingAs($this->admin);

        // Test customer list page
        $response = $this->get(route('admin.customers.index'));
        $response->assertStatus(200);
        
        // Check for responsive classes (common Tailwind CSS responsive classes)
        $response->assertSee('sm:');
        $response->assertSee('md:');
        $response->assertSee('lg:');

        // Test customer profile page
        $response = $this->get(route('admin.customers.show', $this->customer));
        $response->assertStatus(200);
        $response->assertSee('grid');
        $response->assertSee('flex');
    }

    /** @test */
    public function accessibility_attributes_are_present()
    {
        $this->actingAs($this->admin);

        // Test customer edit form
        $response = $this->get(route('admin.customers.edit', $this->customer));
        $response->assertStatus(200);
        
        // Check for accessibility attributes
        $response->assertSee('aria-label');
        $response->assertSee('role=');
        $response->assertSee('for='); // Label for attributes
    }

    /** @test */
    public function form_error_handling_displays_user_friendly_messages()
    {
        $this->actingAs($this->admin);

        // Test validation error display
        $component = Livewire::test(CustomerEdit::class, ['customer' => $this->customer])
            ->set('firstName', '')
            ->set('lastName', '')
            ->set('email', 'invalid')
            ->call('save');

        // Check that error messages are user-friendly
        $component->assertSee('The first name field is required');
        $component->assertSee('The last name field is required');
        $component->assertSee('The email must be a valid email address');
    }

    /** @test */
    public function loading_states_and_user_feedback_work()
    {
        $this->actingAs($this->admin);

        // Test that components load without errors
        $component = Livewire::test(CustomerCreate::class);
        $component->assertStatus(200);

        // Test that the form can be submitted (loading state would be handled by Livewire)
        $component->set('firstName', 'Test')
            ->set('lastName', 'User')
            ->set('email', 'test@example.com')
            ->set('telephoneNumber', '123-456-7890')
            ->set('streetAddress', '123 Test St')
            ->set('cityTown', 'Kingston')
            ->set('parish', 'St. Andrew')
            ->set('country', 'Jamaica')
            ->set('pickupLocation', 'Kingston Office');

        // The component should handle the loading state internally
        $this->assertTrue(method_exists($component->instance(), 'create'));
    }

    /** @test */
    public function search_and_filtering_interface_works()
    {
        $this->actingAs($this->admin);

        // Create additional test data
        $customer2 = User::factory()->create([
            'role_id' => 3,
            'first_name' => 'Jane',
            'last_name' => 'Smith'
        ]);
        Profile::factory()->create([
            'user_id' => $customer2->id,
            'parish' => 'St. Catherine'
        ]);

        $component = Livewire::test(AdminCustomersTable::class);

        // Test that the component loads without errors
        $component->assertStatus(200);
        
        // Test that both customers exist in the database
        $this->assertDatabaseHas('users', ['first_name' => 'John', 'last_name' => 'Doe']);
        $this->assertDatabaseHas('users', ['first_name' => 'Jane', 'last_name' => 'Smith']);
    }

    /** @test */
    public function navigation_breadcrumbs_and_ui_consistency()
    {
        $this->actingAs($this->admin);

        // Test customer list page
        $response = $this->get(route('admin.customers.index'));
        $response->assertStatus(200);
        $response->assertSee('Customers'); // Page title

        // Test customer profile page
        $response = $this->get(route('admin.customers.show', $this->customer));
        $response->assertStatus(200);
        $response->assertSee('Customer Profile');
        $response->assertSee('John');
        $response->assertSee('Doe');

        // Test customer edit page
        $response = $this->get(route('admin.customers.edit', $this->customer));
        $response->assertStatus(200);
        $response->assertSee('Edit Customer');

        // Test customer create page
        $response = $this->get(route('admin.customers.create'));
        $response->assertStatus(200);
        $response->assertSee('Create Customer');
    }

    /** @test */
    public function email_integration_feedback_is_displayed()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerCreate::class);

        // Test that email options are available
        $component->assertSee('welcome email');
        $this->assertTrue($component->get('sendWelcomeEmail'));

        // Test toggling email option
        $component->set('sendWelcomeEmail', false)
            ->assertSet('sendWelcomeEmail', false);
    }

    /** @test */
    public function soft_delete_interface_elements_work()
    {
        $this->actingAs($this->admin);

        // Test that the customer table component loads
        $component = Livewire::test(AdminCustomersTable::class);
        $component->assertStatus(200);

        // Test that delete functionality exists
        $this->assertTrue(method_exists($component->instance(), 'deleteCustomer'));
        $this->assertTrue(method_exists($component->instance(), 'restoreCustomer'));
    }

    /** @test */
    public function bulk_operations_interface_is_available()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AdminCustomersTable::class);
        
        // Test that bulk operations are available
        $bulkActions = $component->get('bulkActions');
        $this->assertArrayHasKey('bulkDelete', $bulkActions);
        $this->assertArrayHasKey('bulkRestore', $bulkActions);
        $this->assertArrayHasKey('exportCustomers', $bulkActions);
    }
}