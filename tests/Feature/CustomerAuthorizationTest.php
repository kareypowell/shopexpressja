<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Livewire\Livewire;
use App\Http\Livewire\Customers\AdminCustomersTable;
use App\Http\Livewire\Customers\CustomerProfile;
use App\Http\Livewire\Customers\CustomerEdit;
use App\Http\Livewire\Customers\CustomerCreate;

class CustomerAuthorizationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'superadmin', 'description' => 'Super Administrator']);
        Role::create(['name' => 'admin', 'description' => 'Administrator']);
        Role::create(['name' => 'customer', 'description' => 'Customer']);
    }

    /** @test */
    public function superadmin_can_access_all_customer_operations()
    {
        $superadmin = User::factory()->create(['role_id' => 1]);
        $customer = User::factory()->create(['role_id' => 3]);

        $this->actingAs($superadmin);

        // Test viewAny
        $this->assertTrue($superadmin->can('customer.viewAny'));

        // Test view
        $this->assertTrue($superadmin->can('customer.view', $customer));

        // Test create
        $this->assertTrue($superadmin->can('customer.create'));

        // Test update
        $this->assertTrue($superadmin->can('customer.update', $customer));

        // Test delete
        $this->assertTrue($superadmin->can('customer.delete', $customer));

        // Test restore
        $this->assertTrue($superadmin->can('customer.restore', $customer));

        // Test viewFinancials
        $this->assertTrue($superadmin->can('customer.viewFinancials', $customer));

        // Test viewPackages
        $this->assertTrue($superadmin->can('customer.viewPackages', $customer));

        // Test bulkOperations
        $this->assertTrue($superadmin->can('customer.bulkOperations'));

        // Test export
        $this->assertTrue($superadmin->can('customer.export'));
    }

    /** @test */
    public function admin_can_access_all_customer_operations()
    {
        $admin = User::factory()->create(['role_id' => 2]);
        $customer = User::factory()->create(['role_id' => 3]);

        $this->actingAs($admin);

        // Test viewAny
        $this->assertTrue($admin->can('customer.viewAny'));

        // Test view
        $this->assertTrue($admin->can('customer.view', $customer));

        // Test create
        $this->assertTrue($admin->can('customer.create'));

        // Test update
        $this->assertTrue($admin->can('customer.update', $customer));

        // Test delete
        $this->assertTrue($admin->can('customer.delete', $customer));

        // Test restore
        $this->assertTrue($admin->can('customer.restore', $customer));

        // Test viewFinancials
        $this->assertTrue($admin->can('customer.viewFinancials', $customer));

        // Test viewPackages
        $this->assertTrue($admin->can('customer.viewPackages', $customer));

        // Test bulkOperations
        $this->assertTrue($admin->can('customer.bulkOperations'));

        // Test export
        $this->assertTrue($admin->can('customer.export'));
    }

    /** @test */
    public function customer_can_only_view_own_profile()
    {
        $customer1 = User::factory()->create(['role_id' => 3]);
        $customer2 = User::factory()->create(['role_id' => 3]);

        $this->actingAs($customer1);

        // Test viewAny - should be false
        $this->assertFalse($customer1->can('customer.viewAny'));

        // Test view own profile - should be true
        $this->assertTrue($customer1->can('customer.view', $customer1));

        // Test view other customer profile - should be false
        $this->assertFalse($customer1->can('customer.view', $customer2));

        // Test create - should be false
        $this->assertFalse($customer1->can('customer.create'));

        // Test update own profile - should be true
        $this->assertTrue($customer1->can('customer.update', $customer1));

        // Test update other customer profile - should be false
        $this->assertFalse($customer1->can('customer.update', $customer2));

        // Test delete - should be false
        $this->assertFalse($customer1->can('customer.delete', $customer1));
        $this->assertFalse($customer1->can('customer.delete', $customer2));

        // Test viewFinancials own - should be true
        $this->assertTrue($customer1->can('customer.viewFinancials', $customer1));

        // Test viewFinancials other - should be false
        $this->assertFalse($customer1->can('customer.viewFinancials', $customer2));

        // Test viewPackages own - should be true
        $this->assertTrue($customer1->can('customer.viewPackages', $customer1));

        // Test viewPackages other - should be false
        $this->assertFalse($customer1->can('customer.viewPackages', $customer2));

        // Test bulkOperations - should be false
        $this->assertFalse($customer1->can('customer.bulkOperations'));

        // Test export - should be false
        $this->assertFalse($customer1->can('customer.export'));
    }

    /** @test */
    public function customer_management_middleware_blocks_unauthorized_users()
    {
        $customer = User::factory()->create(['role_id' => 3]);

        $this->actingAs($customer);

        // Test that customer cannot access admin customer routes
        $response = $this->get(route('admin.customers.index'));
        $response->assertStatus(403);

        $response = $this->get(route('admin.customers.create'));
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_customer_management_routes()
    {
        $admin = User::factory()->create(['role_id' => 2]);

        $this->actingAs($admin);

        // Test that admin can access customer management routes
        $response = $this->get(route('admin.customers.index'));
        $response->assertStatus(200);

        $response = $this->get(route('admin.customers.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_access_customer_management_routes()
    {
        $superadmin = User::factory()->create(['role_id' => 1]);

        $this->actingAs($superadmin);

        // Test that superadmin can access customer management routes
        $response = $this->get(route('admin.customers.index'));
        $response->assertStatus(200);

        $response = $this->get(route('admin.customers.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_customers_table_enforces_authorization()
    {
        $admin = User::factory()->create(['role_id' => 2]);
        $customer = User::factory()->create(['role_id' => 3]);

        $this->actingAs($admin);

        // Test that AdminCustomersTable component loads for authorized user
        Livewire::test(AdminCustomersTable::class)
            ->assertStatus(200);
    }

    /** @test */
    public function customer_profile_enforces_authorization()
    {
        $admin = User::factory()->create(['role_id' => 2]);
        $customer = User::factory()->create(['role_id' => 3]);

        $this->actingAs($admin);

        // Test that CustomerProfile component loads for authorized user
        Livewire::test(CustomerProfile::class, ['customer' => $customer])
            ->assertStatus(200);
    }

    /** @test */
    public function customer_edit_enforces_authorization()
    {
        $admin = User::factory()->create(['role_id' => 2]);
        $customer = User::factory()->create(['role_id' => 3]);

        $this->actingAs($admin);

        // Test that CustomerEdit component loads for authorized user
        Livewire::test(CustomerEdit::class, ['customer' => $customer])
            ->assertStatus(200);
    }

    /** @test */
    public function customer_create_enforces_authorization()
    {
        $admin = User::factory()->create(['role_id' => 2]);

        $this->actingAs($admin);

        // Test that CustomerCreate component loads for authorized user
        Livewire::test(CustomerCreate::class)
            ->assertStatus(200);
    }

    /** @test */
    public function unauthorized_user_cannot_access_customer_components()
    {
        $customer = User::factory()->create(['role_id' => 3]);
        $otherCustomer = User::factory()->create(['role_id' => 3]);

        $this->actingAs($customer);

        // Test that customer cannot access AdminCustomersTable
        Livewire::test(AdminCustomersTable::class)
            ->assertForbidden();

        // Test that customer cannot access other customer's profile
        Livewire::test(CustomerProfile::class, ['customer' => $otherCustomer])
            ->assertForbidden();

        // Test that customer cannot access other customer's edit form
        Livewire::test(CustomerEdit::class, ['customer' => $otherCustomer])
            ->assertForbidden();

        // Test that customer cannot access customer creation
        Livewire::test(CustomerCreate::class)
            ->assertForbidden();
    }
}