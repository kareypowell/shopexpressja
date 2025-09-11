<?php

namespace Tests\Browser;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SidebarNavigationRoleVisibilityTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'superadmin', 'description' => 'Super Administrator']);
        Role::create(['name' => 'admin', 'description' => 'Administrator']);
        Role::create(['name' => 'customer', 'description' => 'Customer']);
        Role::create(['name' => 'purchaser', 'description' => 'Purchaser']);
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

        $this->browse(function (Browser $browser) use ($superadmin) {
            $browser->loginAs($superadmin)
                    ->visit('/')
                    ->waitFor('nav')
                    ->assertSee('User Management')
                    ->assertSee('Role Management')
                    ->click('button:contains("User Management")')
                    ->waitFor('a:contains("Create User")')
                    ->assertSee('Create User')
                    ->assertSee('Manage Users')
                    ->click('button:contains("Role Management")')
                    ->waitFor('a:contains("Manage Roles")')
                    ->assertSee('Manage Roles');
        });
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

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                    ->visit('/')
                    ->waitFor('nav')
                    ->assertSee('User Management')
                    ->assertDontSee('Role Management')
                    ->click('button:contains("User Management")')
                    ->waitFor('a:contains("Create User")')
                    ->assertSee('Create User')
                    ->assertSee('Manage Users');
        });
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

        $this->browse(function (Browser $browser) use ($customer) {
            $browser->loginAs($customer)
                    ->visit('/')
                    ->waitFor('nav')
                    ->assertDontSee('User Management')
                    ->assertDontSee('Role Management');
        });
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

        $this->browse(function (Browser $browser) use ($purchaser) {
            $browser->loginAs($purchaser)
                    ->visit('/')
                    ->waitFor('nav')
                    ->assertDontSee('User Management')
                    ->assertDontSee('Role Management');
        });
    }

    /**
     * Test navigation links route to correct pages for superadmin.
     */
    public function test_superadmin_navigation_links_route_correctly()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $superadmin = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($superadmin) {
            $browser->loginAs($superadmin)
                    ->visit('/')
                    ->waitFor('nav')
                    ->click('button:contains("User Management")')
                    ->waitFor('a:contains("Create User")')
                    ->click('a:contains("Create User")')
                    ->waitForLocation('/admin/users/create')
                    ->assertPathIs('/admin/users/create')
                    ->back()
                    ->waitFor('nav')
                    ->click('button:contains("User Management")')
                    ->waitFor('a:contains("Manage Users")')
                    ->click('a:contains("Manage Users")')
                    ->waitForLocation('/admin/users')
                    ->assertPathIs('/admin/users')
                    ->back()
                    ->waitFor('nav')
                    ->click('button:contains("Role Management")')
                    ->waitFor('a:contains("Manage Roles")')
                    ->click('a:contains("Manage Roles")')
                    ->waitForLocation('/admin/roles')
                    ->assertPathIs('/admin/roles');
        });
    }

    /**
     * Test navigation links route to correct pages for admin.
     */
    public function test_admin_navigation_links_route_correctly()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                    ->visit('/')
                    ->waitFor('nav')
                    ->click('button:contains("User Management")')
                    ->waitFor('a:contains("Create User")')
                    ->click('a:contains("Create User")')
                    ->waitForLocation('/admin/users/create')
                    ->assertPathIs('/admin/users/create')
                    ->back()
                    ->waitFor('nav')
                    ->click('button:contains("User Management")')
                    ->waitFor('a:contains("Manage Users")')
                    ->click('a:contains("Manage Users")')
                    ->waitForLocation('/admin/users')
                    ->assertPathIs('/admin/users');
        });
    }

    /**
     * Test mobile navigation visibility for different roles.
     */
    public function test_mobile_navigation_visibility_for_different_roles()
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

        // Test superadmin mobile navigation
        $this->browse(function (Browser $browser) use ($superadmin) {
            $browser->loginAs($superadmin)
                    ->resize(375, 667) // Mobile viewport
                    ->visit('/')
                    ->waitFor('button[type="button"]:first-child') // Mobile menu button
                    ->click('button[type="button"]:first-child')
                    ->waitFor('.fixed.inset-0.flex.z-40')
                    ->assertSee('User Management')
                    ->assertSee('Role Management');
        });

        // Test admin mobile navigation
        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                    ->resize(375, 667) // Mobile viewport
                    ->visit('/')
                    ->waitFor('button[type="button"]:first-child') // Mobile menu button
                    ->click('button[type="button"]:first-child')
                    ->waitFor('.fixed.inset-0.flex.z-40')
                    ->assertSee('User Management')
                    ->assertDontSee('Role Management');
        });

        // Test customer mobile navigation
        $this->browse(function (Browser $browser) use ($customer) {
            $browser->loginAs($customer)
                    ->resize(375, 667) // Mobile viewport
                    ->visit('/')
                    ->waitFor('button[type="button"]:first-child') // Mobile menu button
                    ->click('button[type="button"]:first-child')
                    ->waitFor('.fixed.inset-0.flex.z-40')
                    ->assertDontSee('User Management')
                    ->assertDontSee('Role Management');
        });
    }

    /**
     * Test that navigation sections expand and collapse correctly.
     */
    public function test_navigation_sections_expand_and_collapse()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $superadmin = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($superadmin) {
            $browser->loginAs($superadmin)
                    ->visit('/')
                    ->waitFor('nav')
                    // Test User Management expansion
                    ->assertDontSee('Create User') // Should be collapsed initially
                    ->click('button:contains("User Management")')
                    ->waitFor('a:contains("Create User")')
                    ->assertSee('Create User')
                    ->assertSee('Manage Users')
                    // Test collapse
                    ->click('button:contains("User Management")')
                    ->waitUntilMissing('a:contains("Create User")')
                    ->assertDontSee('Create User')
                    // Test Role Management expansion
                    ->assertDontSee('Manage Roles') // Should be collapsed initially
                    ->click('button:contains("Role Management")')
                    ->waitFor('a:contains("Manage Roles")')
                    ->assertSee('Manage Roles')
                    // Test collapse
                    ->click('button:contains("Role Management")')
                    ->waitUntilMissing('a:contains("Manage Roles")')
                    ->assertDontSee('Manage Roles');
        });
    }

    /**
     * Test that active route highlighting works correctly.
     */
    public function test_active_route_highlighting()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $superadmin = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($superadmin) {
            $browser->loginAs($superadmin)
                    ->visit('/admin/users')
                    ->waitFor('nav')
                    // User Management section should be expanded and highlighted
                    ->assertSee('Create User')
                    ->assertSee('Manage Users')
                    // Check that the parent button has active styling
                    ->assertAttribute('button:contains("User Management")', 'class', function ($class) {
                        return str_contains($class, 'bg-gray-900') && str_contains($class, 'text-white');
                    })
                    // Check that the active link has active styling
                    ->assertAttribute('a:contains("Manage Users")', 'class', function ($class) {
                        return str_contains($class, 'bg-gray-900') && str_contains($class, 'text-white');
                    });
        });
    }
}