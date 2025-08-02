<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CustomerRoutingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $superAdmin;
    protected $customer;
    protected $customerRole;
    protected $adminRole;
    protected $superAdminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $this->customerRole = Role::factory()->create(['name' => 'customer']);
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->superAdminRole = Role::factory()->create(['name' => 'superadmin']);

        // Create users
        $this->customer = User::factory()->create(['role_id' => $this->customerRole->id]);
        $this->admin = User::factory()->create(['role_id' => $this->adminRole->id]);
        $this->superAdmin = User::factory()->create(['role_id' => $this->superAdminRole->id]);

        // Create profiles
        Profile::factory()->create(['user_id' => $this->customer->id]);
        Profile::factory()->create(['user_id' => $this->admin->id]);
        Profile::factory()->create(['user_id' => $this->superAdmin->id]);
    }

    /** @test */
    public function customer_index_route_exists_and_requires_authentication()
    {
        // Test unauthenticated access
        $response = $this->get(route('admin.customers.index'));
        $response->assertRedirect(route('login'));

        // Test customer access (should be denied)
        $response = $this->actingAs($this->customer)->get(route('admin.customers.index'));
        $response->assertStatus(403);

        // Test admin access (should be allowed)
        $response = $this->actingAs($this->admin)->get(route('admin.customers.index'));
        $response->assertStatus(200);

        // Test superadmin access (should be allowed)
        $response = $this->actingAs($this->superAdmin)->get(route('admin.customers.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function customer_create_route_exists_and_requires_proper_authorization()
    {
        // Test unauthenticated access
        $response = $this->get(route('admin.customers.create'));
        $response->assertRedirect(route('login'));

        // Test customer access (should be denied)
        $response = $this->actingAs($this->customer)->get(route('admin.customers.create'));
        $response->assertStatus(403);

        // Test admin access (should be allowed)
        $response = $this->actingAs($this->admin)->get(route('admin.customers.create'));
        $response->assertStatus(200);

        // Test superadmin access (should be allowed)
        $response = $this->actingAs($this->superAdmin)->get(route('admin.customers.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function customer_show_route_uses_route_model_binding()
    {
        // Test with valid customer ID
        $response = $this->actingAs($this->admin)->get(route('admin.customers.show', $this->customer));
        $response->assertStatus(200);

        // Test with invalid customer ID
        $response = $this->actingAs($this->admin)->get('/admin/customers/99999');
        $response->assertStatus(404);

        // Test with non-customer user (admin trying to view another admin)
        $response = $this->actingAs($this->admin)->get(route('admin.customers.show', $this->admin));
        $response->assertStatus(404); // Should not find admin as customer
    }

    /** @test */
    public function customer_edit_route_uses_route_model_binding_and_authorization()
    {
        // Test admin can edit customer
        $response = $this->actingAs($this->admin)->get(route('admin.customers.edit', $this->customer));
        $response->assertStatus(200);

        // Test superadmin can edit customer
        $response = $this->actingAs($this->superAdmin)->get(route('admin.customers.edit', $this->customer));
        $response->assertStatus(200);

        // Test customer cannot edit other customers
        $otherCustomer = User::factory()->create(['role_id' => $this->customerRole->id]);
        Profile::factory()->create(['user_id' => $otherCustomer->id]);
        
        $response = $this->actingAs($this->customer)->get(route('admin.customers.edit', $otherCustomer));
        $response->assertStatus(403);
    }

    /** @test */
    public function route_model_binding_includes_soft_deleted_customers()
    {
        // Soft delete the customer
        $this->customer->delete();

        // Admin should still be able to access soft-deleted customer
        $response = $this->actingAs($this->admin)->get(route('admin.customers.show', $this->customer));
        $response->assertStatus(200);

        // Admin should still be able to edit soft-deleted customer
        $response = $this->actingAs($this->admin)->get(route('admin.customers.edit', $this->customer));
        $response->assertStatus(200);
    }

    /** @test */
    public function route_names_are_correctly_defined()
    {
        // Test that all route names exist
        $this->assertTrue(route('admin.customers.index') !== null);
        $this->assertTrue(route('admin.customers.create') !== null);
        $this->assertTrue(route('admin.customers.show', $this->customer) !== null);
        $this->assertTrue(route('admin.customers.edit', $this->customer) !== null);
    }

    /** @test */
    public function routes_use_correct_middleware()
    {
        $routes = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->filter(function ($route) {
                return str_starts_with($route->getName() ?? '', 'admin.customers.');
            });

        foreach ($routes as $route) {
            // All customer management routes should have auth middleware
            $this->assertContains('auth', $route->middleware());
            
            // All customer management routes should have customer.management middleware
            $this->assertContains('customer.management', $route->middleware());
        }
    }

    /** @test */
    public function breadcrumbs_are_properly_set_in_components()
    {
        // Test customer index breadcrumbs
        $response = $this->actingAs($this->admin)->get(route('admin.customers.index'));
        $response->assertStatus(200);
        $response->assertSee('Dashboard'); // Should contain breadcrumb to dashboard
        $response->assertSee('Customers'); // Should contain current page breadcrumb

        // Test customer profile breadcrumbs
        $response = $this->actingAs($this->admin)->get(route('admin.customers.show', $this->customer));
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Customers');
        $response->assertSee($this->customer->profile->first_name);

        // Test customer edit breadcrumbs
        $response = $this->actingAs($this->admin)->get(route('admin.customers.edit', $this->customer));
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Customers');
        $response->assertSee('Edit');

        // Test customer create breadcrumbs
        $response = $this->actingAs($this->admin)->get(route('admin.customers.create'));
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Customers');
        $response->assertSee('Create Customer');
    }

    /** @test */
    public function route_authorization_policies_are_applied()
    {
        // Create a regular user (not admin or superadmin)
        $regularUser = User::factory()->create(['role_id' => $this->customerRole->id]);
        Profile::factory()->create(['user_id' => $regularUser->id]);

        // Test that regular customer cannot access admin customer routes
        $response = $this->actingAs($regularUser)->get(route('admin.customers.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($regularUser)->get(route('admin.customers.create'));
        $response->assertStatus(403);

        $response = $this->actingAs($regularUser)->get(route('admin.customers.show', $this->customer));
        $response->assertStatus(403);

        $response = $this->actingAs($regularUser)->get(route('admin.customers.edit', $this->customer));
        $response->assertStatus(403);
    }

    /** @test */
    public function route_parameters_are_properly_validated()
    {
        // Test with non-numeric customer ID
        $response = $this->actingAs($this->admin)->get('/admin/customers/invalid-id');
        $response->assertStatus(404);

        // Test with customer ID that doesn't exist
        $response = $this->actingAs($this->admin)->get('/admin/customers/99999');
        $response->assertStatus(404);

        // Test with valid customer ID
        $response = $this->actingAs($this->admin)->get(route('admin.customers.show', $this->customer));
        $response->assertStatus(200);
    }
}