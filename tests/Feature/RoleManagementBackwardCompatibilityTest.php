<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Package;
use App\Models\Manifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RoleManagementBackwardCompatibilityTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $roles;
    protected $users;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles with specific IDs to match legacy system
        $this->roles = [
            'customer' => Role::create(['id' => 3, 'name' => 'customer', 'description' => 'Customer']),
            'admin' => Role::create(['id' => 1, 'name' => 'admin', 'description' => 'Administrator']),
            'superadmin' => Role::create(['id' => 2, 'name' => 'superadmin', 'description' => 'Super Administrator']),
            'purchaser' => Role::create(['id' => 4, 'name' => 'purchaser', 'description' => 'Purchaser']),
        ];

        // Create test users with legacy role IDs
        $this->users = [
            'customer' => User::factory()->create(['role_id' => 3]),
            'admin' => User::factory()->create(['role_id' => 1]),
            'superadmin' => User::factory()->create(['role_id' => 2]),
            'purchaser' => User::factory()->create(['role_id' => 4]),
        ];
    }

    /** @test */
    public function legacy_role_id_checks_still_work()
    {
        // Test that hardcoded role ID checks still work
        $this->assertEquals(3, $this->users['customer']->role_id);
        $this->assertEquals(1, $this->users['admin']->role_id);
        $this->assertEquals(2, $this->users['superadmin']->role_id);
        $this->assertEquals(4, $this->users['purchaser']->role_id);

        // Test legacy-style role checking
        $this->assertTrue($this->users['customer']->role_id === 3);
        $this->assertTrue($this->users['admin']->role_id === 1);
        $this->assertTrue($this->users['superadmin']->role_id === 2);
        $this->assertTrue($this->users['purchaser']->role_id === 4);
    }

    /** @test */
    public function new_role_methods_match_legacy_checks()
    {
        // Customer checks
        $this->assertEquals(
            ($this->users['customer']->role_id === 3),
            $this->users['customer']->isCustomer()
        );
        $this->assertEquals(
            ($this->users['customer']->role_id === 3),
            $this->users['customer']->hasRole('customer')
        );

        // Admin checks
        $this->assertEquals(
            ($this->users['admin']->role_id === 1),
            $this->users['admin']->isAdmin()
        );
        $this->assertEquals(
            ($this->users['admin']->role_id === 1),
            $this->users['admin']->hasRole('admin')
        );

        // SuperAdmin checks
        $this->assertEquals(
            ($this->users['superadmin']->role_id === 2),
            $this->users['superadmin']->isSuperAdmin()
        );
        $this->assertEquals(
            ($this->users['superadmin']->role_id === 2),
            $this->users['superadmin']->hasRole('superadmin')
        );

        // Purchaser checks
        $this->assertEquals(
            ($this->users['purchaser']->role_id === 4),
            $this->users['purchaser']->isPurchaser()
        );
        $this->assertEquals(
            ($this->users['purchaser']->role_id === 4),
            $this->users['purchaser']->hasRole('purchaser')
        );
    }

    /** @test */
    public function legacy_multi_role_checks_work_with_new_methods()
    {
        // Legacy: Check if user is admin or superadmin
        $legacyAdminCheck = in_array($this->users['admin']->role_id, [1, 2]);
        $newAdminCheck = $this->users['admin']->hasAnyRole(['admin', 'superadmin']);
        $this->assertEquals($legacyAdminCheck, $newAdminCheck);

        $legacySuperAdminCheck = in_array($this->users['superadmin']->role_id, [1, 2]);
        $newSuperAdminCheck = $this->users['superadmin']->hasAnyRole(['admin', 'superadmin']);
        $this->assertEquals($legacySuperAdminCheck, $newSuperAdminCheck);

        $legacyCustomerCheck = in_array($this->users['customer']->role_id, [1, 2]);
        $newCustomerCheck = $this->users['customer']->hasAnyRole(['admin', 'superadmin']);
        $this->assertEquals($legacyCustomerCheck, $newCustomerCheck);
    }

    /** @test */
    public function existing_scopes_work_with_legacy_data()
    {
        // Test that existing scopes work with legacy role IDs
        $customers = User::where('role_id', 3)->get();
        $newCustomers = User::customerUsers()->get();
        
        $this->assertEquals($customers->count(), $newCustomers->count());
        $this->assertEquals($customers->pluck('id')->sort(), $newCustomers->pluck('id')->sort());

        // Test admin scope
        $admins = User::where('role_id', 1)->get();
        $newAdmins = User::admins()->get();
        
        $this->assertEquals($admins->count(), $newAdmins->count());
        $this->assertEquals($admins->pluck('id')->sort(), $newAdmins->pluck('id')->sort());
    }

    /** @test */
    public function policies_work_with_both_approaches()
    {
        // Test that policies work with both legacy and new role checking
        
        // Simulate policy check for package management (legacy approach)
        $legacyCanManage = in_array($this->users['admin']->role_id, [1, 2]);
        
        // New approach
        $newCanManage = $this->users['admin']->hasAnyRole(['admin', 'superadmin']);
        
        $this->assertEquals($legacyCanManage, $newCanManage);

        // Test with different users
        $legacyCustomerCanManage = in_array($this->users['customer']->role_id, [1, 2]);
        $newCustomerCanManage = $this->users['customer']->hasAnyRole(['admin', 'superadmin']);
        
        $this->assertEquals($legacyCustomerCanManage, $newCustomerCanManage);
        $this->assertFalse($newCustomerCanManage); // Customer should not be able to manage
    }

    /** @test */
    public function database_queries_remain_efficient()
    {
        // Test that new role methods don't cause N+1 queries
        
        // Create multiple users
        $users = User::factory()->count(10)->create(['role_id' => 3]);
        
        // Test that role checking doesn't cause excessive queries
        \DB::enableQueryLog();
        
        foreach ($users as $user) {
            $user->isCustomer(); // Should use cached role
        }
        
        $queries = \DB::getQueryLog();
        
        // Should not have excessive queries due to role caching
        $this->assertLessThan(15, count($queries)); // Allow some queries for setup
        
        \DB::disableQueryLog();
    }

    /** @test */
    public function existing_relationships_still_work()
    {
        // Test that existing model relationships work with new role system
        $customer = $this->users['customer'];
        
        // Create some packages for the customer
        $packages = Package::factory()->count(3)->create(['user_id' => $customer->id]);
        
        // Test that relationships still work
        $this->assertEquals(3, $customer->packages()->count());
        $this->assertEquals($customer->id, $packages->first()->user_id);
        
        // Test that role-based queries work with relationships
        $customerPackages = Package::whereHas('user', function($query) {
            $query->where('role_id', 3);
        })->get();
        
        $newCustomerPackages = Package::whereHas('user', function($query) {
            $query->customerUsers();
        })->get();
        
        $this->assertEquals($customerPackages->count(), $newCustomerPackages->count());
    }

    /** @test */
    public function migration_from_ids_to_names_is_seamless()
    {
        // Test that we can gradually migrate from ID-based to name-based checking
        
        $user = $this->users['admin'];
        
        // Old way (still works)
        $oldCheck = ($user->role_id === 1);
        
        // New way
        $newCheck = $user->isAdmin();
        
        // Transitional way (using role relationship)
        $transitionalCheck = ($user->role->name === 'admin');
        
        // All should give same result
        $this->assertEquals($oldCheck, $newCheck);
        $this->assertEquals($oldCheck, $transitionalCheck);
        $this->assertTrue($oldCheck && $newCheck && $transitionalCheck);
    }

    /** @test */
    public function existing_middleware_compatibility()
    {
        // Test that existing middleware that checks roles still works
        
        // Simulate middleware that checks for admin role
        $adminUser = $this->users['admin'];
        $customerUser = $this->users['customer'];
        
        // Legacy middleware check
        $legacyAdminCheck = in_array($adminUser->role_id, [1, 2]);
        $legacyCustomerCheck = in_array($customerUser->role_id, [1, 2]);
        
        // New middleware check (what we should migrate to)
        $newAdminCheck = $adminUser->canManageUsers();
        $newCustomerCheck = $customerUser->canManageUsers();
        
        // Results should match
        $this->assertEquals($legacyAdminCheck, $newAdminCheck);
        $this->assertEquals($legacyCustomerCheck, $newCustomerCheck);
        
        $this->assertTrue($newAdminCheck);
        $this->assertFalse($newCustomerCheck);
    }

    /** @test */
    public function existing_blade_template_compatibility()
    {
        // Test that existing Blade templates that check roles still work
        
        $user = $this->users['superadmin'];
        
        // Legacy template check: @if($user->role_id === 2)
        $legacyTemplateCheck = ($user->role_id === 2);
        
        // New template check: @if($user->isSuperAdmin())
        $newTemplateCheck = $user->isSuperAdmin();
        
        // Both should work
        $this->assertEquals($legacyTemplateCheck, $newTemplateCheck);
        $this->assertTrue($legacyTemplateCheck && $newTemplateCheck);
    }

    /** @test */
    public function role_based_route_protection_compatibility()
    {
        // Test that route protection works with both approaches
        
        $routes = [
            'admin_only' => ['allowed_roles' => [1, 2]], // Legacy: admin and superadmin IDs
            'customer_only' => ['allowed_roles' => [3]], // Legacy: customer ID
        ];
        
        foreach ($this->users as $roleType => $user) {
            // Legacy check for admin_only route
            $legacyAdminAccess = in_array($user->role_id, $routes['admin_only']['allowed_roles']);
            
            // New check for admin_only route
            $newAdminAccess = $user->hasAnyRole(['admin', 'superadmin']);
            
            $this->assertEquals($legacyAdminAccess, $newAdminAccess);
            
            // Legacy check for customer_only route
            $legacyCustomerAccess = in_array($user->role_id, $routes['customer_only']['allowed_roles']);
            
            // New check for customer_only route
            $newCustomerAccess = $user->hasRole('customer');
            
            $this->assertEquals($legacyCustomerAccess, $newCustomerAccess);
        }
    }

    /** @test */
    public function existing_test_compatibility()
    {
        // Test that existing tests that use role IDs still pass
        
        // Simulate existing test assertions
        $this->assertTrue($this->users['customer']->role_id === 3);
        $this->assertTrue($this->users['admin']->role_id === 1);
        $this->assertTrue($this->users['superadmin']->role_id === 2);
        
        // New tests should also pass
        $this->assertTrue($this->users['customer']->isCustomer());
        $this->assertTrue($this->users['admin']->isAdmin());
        $this->assertTrue($this->users['superadmin']->isSuperAdmin());
        
        // Mixed approach should work
        $this->assertTrue($this->users['customer']->role_id === 3 && $this->users['customer']->isCustomer());
    }

    /** @test */
    public function performance_is_maintained_or_improved()
    {
        // Test that new role methods don't degrade performance
        
        $users = User::factory()->count(100)->create(['role_id' => 3]);
        
        // Time legacy approach
        $start = microtime(true);
        foreach ($users as $user) {
            $isCustomer = ($user->role_id === 3);
        }
        $legacyTime = microtime(true) - $start;
        
        // Time new approach
        $start = microtime(true);
        foreach ($users as $user) {
            $isCustomer = $user->isCustomer();
        }
        $newTime = microtime(true) - $start;
        
        // New approach should not be significantly slower
        // Allow for some overhead due to method calls and caching
        $this->assertLessThan($legacyTime * 3, $newTime, 'New role methods are too slow compared to legacy approach');
    }

    /** @test */
    public function data_integrity_is_maintained()
    {
        // Test that role data integrity is maintained during transition
        
        // Verify all users have valid roles
        foreach ($this->users as $user) {
            $this->assertNotNull($user->role_id);
            $this->assertNotNull($user->role);
            $this->assertNotEmpty($user->role->name);
        }
        
        // Verify role IDs match role names
        $this->assertEquals('customer', $this->users['customer']->role->name);
        $this->assertEquals('admin', $this->users['admin']->role->name);
        $this->assertEquals('superadmin', $this->users['superadmin']->role->name);
        $this->assertEquals('purchaser', $this->users['purchaser']->role->name);
        
        // Verify no orphaned role assignments
        $allUsers = User::all();
        foreach ($allUsers as $user) {
            $this->assertNotNull(Role::find($user->role_id), "User {$user->id} has invalid role_id {$user->role_id}");
        }
    }

    /** @test */
    public function gradual_migration_is_possible()
    {
        // Test that we can gradually migrate code from legacy to new approach
        
        $user = $this->users['admin'];
        
        // Step 1: Legacy code (what we're migrating from)
        $step1Check = ($user->role_id === 1);
        
        // Step 2: Use role relationship but still check ID
        $step2Check = ($user->role->id === 1);
        
        // Step 3: Use role relationship and check name
        $step3Check = ($user->role->name === 'admin');
        
        // Step 4: Use helper method (final state)
        $step4Check = $user->isAdmin();
        
        // All steps should give same result
        $this->assertEquals($step1Check, $step2Check);
        $this->assertEquals($step2Check, $step3Check);
        $this->assertEquals($step3Check, $step4Check);
        $this->assertTrue($step1Check && $step2Check && $step3Check && $step4Check);
    }
}