<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationRoleVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles if they don't exist
        Role::firstOrCreate(['name' => 'superadmin'], ['description' => 'Super Administrator']);
        Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);
        Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer']);
        Role::firstOrCreate(['name' => 'purchaser'], ['description' => 'Purchaser']);
    }

    /**
     * Test that superadmin can see both User Management and Role Management sections.
     */
    public function test_superadmin_can_see_all_navigation_sections()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $superadmin = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($superadmin)->get('/admin/dashboard');

        $response->assertStatus(200)
                ->assertSee('User Management')
                ->assertSee('Role Management')
                ->assertSee('Create User')
                ->assertSee('Manage Users')
                ->assertSee('Manage Roles');
    }

    /**
     * Test that admin can see User Management but not Role Management.
     */
    public function test_admin_can_see_user_management_but_not_role_management()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/customers');

        $response->assertStatus(200)
                ->assertSee('User Management')
                ->assertDontSee('Role Management')
                ->assertSee('Create User')
                ->assertSee('Manage Users')
                ->assertDontSee('Manage Roles');
    }

    /**
     * Test that customer cannot see User Management or Role Management.
     */
    public function test_customer_cannot_see_management_sections()
    {
        $customerRole = Role::where('name', 'customer')->first();
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($customer)->get('/');

        $response->assertStatus(200)
                ->assertDontSee('User Management')
                ->assertDontSee('Role Management')
                ->assertDontSee('Create User')
                ->assertDontSee('Manage Users')
                ->assertDontSee('Manage Roles');
    }

    /**
     * Test that purchaser cannot see User Management or Role Management.
     */
    public function test_purchaser_cannot_see_management_sections()
    {
        $purchaserRole = Role::where('name', 'purchaser')->first();
        $purchaser = User::factory()->create([
            'role_id' => $purchaserRole->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($purchaser)->get('/');

        $response->assertStatus(200)
                ->assertDontSee('User Management')
                ->assertDontSee('Role Management')
                ->assertDontSee('Create User')
                ->assertDontSee('Manage Users')
                ->assertDontSee('Manage Roles');
    }

    /**
     * Test navigation links are accessible for superadmin.
     */
    public function test_superadmin_can_access_navigation_routes()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $superadmin = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        // Test User Management routes
        $this->actingAs($superadmin)
             ->get('/admin/users/create')
             ->assertStatus(200);

        $this->actingAs($superadmin)
             ->get('/admin/users')
             ->assertStatus(200);

        // Test Role Management routes
        $this->actingAs($superadmin)
             ->get('/admin/roles')
             ->assertStatus(200);
    }

    /**
     * Test navigation links are accessible for admin.
     */
    public function test_admin_can_access_user_management_routes()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);

        // Test User Management routes
        $this->actingAs($admin)
             ->get('/admin/users/create')
             ->assertStatus(200);

        $this->actingAs($admin)
             ->get('/admin/users')
             ->assertStatus(200);

        // Admin should not be able to access Role Management
        $this->actingAs($admin)
             ->get('/admin/roles')
             ->assertStatus(403); // Should be forbidden
    }

    /**
     * Test that customers cannot access management routes.
     */
    public function test_customer_cannot_access_management_routes()
    {
        $customerRole = Role::where('name', 'customer')->first();
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'email_verified_at' => now(),
        ]);

        // Customer should not be able to access User Management
        $this->actingAs($customer)
             ->get('/admin/users/create')
             ->assertStatus(403); // Should be forbidden

        $this->actingAs($customer)
             ->get('/admin/users')
             ->assertStatus(403); // Should be forbidden

        // Customer should not be able to access Role Management
        $this->actingAs($customer)
             ->get('/admin/roles')
             ->assertStatus(403); // Should be forbidden
    }

    /**
     * Test that purchasers cannot access management routes.
     */
    public function test_purchaser_cannot_access_management_routes()
    {
        $purchaserRole = Role::where('name', 'purchaser')->first();
        $purchaser = User::factory()->create([
            'role_id' => $purchaserRole->id,
            'email_verified_at' => now(),
        ]);

        // Purchaser should not be able to access User Management
        $this->actingAs($purchaser)
             ->get('/admin/users/create')
             ->assertStatus(403); // Should be forbidden

        $this->actingAs($purchaser)
             ->get('/admin/users')
             ->assertStatus(403); // Should be forbidden

        // Purchaser should not be able to access Role Management
        $this->actingAs($purchaser)
             ->get('/admin/roles')
             ->assertStatus(403); // Should be forbidden
    }

    /**
     * Test that role helper methods work correctly.
     */
    public function test_role_helper_methods_work_correctly()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $customerRole = Role::where('name', 'customer')->first();
        $purchaserRole = Role::where('name', 'purchaser')->first();

        $superadmin = User::factory()->create(['role_id' => $superadminRole->id]);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $customer = User::factory()->create(['role_id' => $customerRole->id]);
        $purchaser = User::factory()->create(['role_id' => $purchaserRole->id]);

        // Test superadmin
        $this->assertTrue($superadmin->isSuperAdmin());
        $this->assertFalse($superadmin->isAdmin());
        $this->assertFalse($superadmin->isCustomer());
        $this->assertFalse($superadmin->isPurchaser());

        // Test admin
        $this->assertFalse($admin->isSuperAdmin());
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isCustomer());
        $this->assertFalse($admin->isPurchaser());

        // Test customer
        $this->assertFalse($customer->isSuperAdmin());
        $this->assertFalse($customer->isAdmin());
        $this->assertTrue($customer->isCustomer());
        $this->assertFalse($customer->isPurchaser());

        // Test purchaser
        $this->assertFalse($purchaser->isSuperAdmin());
        $this->assertFalse($purchaser->isAdmin());
        $this->assertFalse($purchaser->isCustomer());
        $this->assertTrue($purchaser->isPurchaser());
    }

    /**
     * Test that navigation sections are properly conditionally rendered.
     */
    public function test_navigation_conditional_rendering()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $customerRole = Role::where('name', 'customer')->first();

        $superadmin = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);

        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'email_verified_at' => now(),
        ]);

        // Test superadmin sees both sections
        $response = $this->actingAs($superadmin)->get('/admin/dashboard');
        $response->assertSee('User Management')
                ->assertSee('Role Management');

        // Test admin sees only User Management
        $response = $this->actingAs($admin)->get('/admin/customers');
        $response->assertSee('User Management')
                ->assertDontSee('Role Management');

        // Test customer sees neither section
        $response = $this->actingAs($customer)->get('/');
        $response->assertDontSee('User Management')
                ->assertDontSee('Role Management');
    }
}