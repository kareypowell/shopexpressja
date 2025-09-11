<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Package;
use App\Models\Manifest;
use App\Models\ConsolidatedPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $roles;
    protected $users;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Get existing roles or create them if they don't exist
        $this->roles = [
            'superadmin' => Role::firstOrCreate(['name' => 'superadmin'], ['description' => 'Super Administrator']),
            'admin' => Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']),
            'customer' => Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer']),
            'purchaser' => Role::firstOrCreate(['name' => 'purchaser'], ['description' => 'Purchaser']),
        ];

        // Create test users for each role
        $this->users = [
            'superadmin' => User::factory()->create(['role_id' => $this->roles['superadmin']->id]),
            'admin' => User::factory()->create(['role_id' => $this->roles['admin']->id]),
            'customer' => User::factory()->create(['role_id' => $this->roles['customer']->id]),
            'purchaser' => User::factory()->create(['role_id' => $this->roles['purchaser']->id]),
        ];
    }

    /** @test */
    public function all_role_helper_methods_work_correctly()
    {
        // Test each user type
        foreach ($this->users as $roleType => $user) {
            // Test basic role methods
            $this->assertEquals($roleType === 'superadmin', $user->isSuperAdmin(), "isSuperAdmin failed for {$roleType}");
            $this->assertEquals($roleType === 'admin', $user->isAdmin(), "isAdmin failed for {$roleType}");
            $this->assertEquals($roleType === 'customer', $user->isCustomer(), "isCustomer failed for {$roleType}");
            $this->assertEquals($roleType === 'purchaser', $user->isPurchaser(), "isPurchaser failed for {$roleType}");
            
            // Test hasRole method
            $this->assertTrue($user->hasRole($roleType), "hasRole failed for {$roleType}");
            
            // Test permission methods
            $canManageUsers = in_array($roleType, ['admin', 'superadmin']);
            $this->assertEquals($canManageUsers, $user->canManageUsers(), "canManageUsers failed for {$roleType}");
            
            $canManageRoles = ($roleType === 'superadmin');
            $this->assertEquals($canManageRoles, $user->canManageRoles(), "canManageRoles failed for {$roleType}");
        }
    }

    /** @test */
    public function user_management_permissions_work_correctly()
    {
        // SuperAdmin should be able to access all user management features
        $this->actingAs($this->users['superadmin']);
        $this->assertTrue(auth()->user()->can('viewAny', User::class));
        $this->assertTrue(auth()->user()->can('create', User::class));

        // Admin should be able to access user management but not role management
        $this->actingAs($this->users['admin']);
        $this->assertTrue(auth()->user()->can('viewAny', User::class));
        $this->assertTrue(auth()->user()->can('create', User::class));

        // Customer should not be able to access user management
        $this->actingAs($this->users['customer']);
        $this->assertFalse(auth()->user()->can('viewAny', User::class));
        $this->assertFalse(auth()->user()->can('create', User::class));

        // Purchaser should not be able to access user management
        $this->actingAs($this->users['purchaser']);
        $this->assertFalse(auth()->user()->can('viewAny', User::class));
        $this->assertFalse(auth()->user()->can('create', User::class));
    }

    /** @test */
    public function role_management_permissions_work_correctly()
    {
        // Only SuperAdmin should be able to manage roles
        $this->actingAs($this->users['superadmin']);
        $this->assertTrue(auth()->user()->can('viewAny', Role::class));
        $this->assertTrue(auth()->user()->can('create', Role::class));

        // Admin should not be able to manage roles
        $this->actingAs($this->users['admin']);
        $this->assertFalse(auth()->user()->can('viewAny', Role::class));
        $this->assertFalse(auth()->user()->can('create', Role::class));

        // Customer should not be able to manage roles
        $this->actingAs($this->users['customer']);
        $this->assertFalse(auth()->user()->can('viewAny', Role::class));
        $this->assertFalse(auth()->user()->can('create', Role::class));

        // Purchaser should not be able to manage roles
        $this->actingAs($this->users['purchaser']);
        $this->assertFalse(auth()->user()->can('viewAny', Role::class));
        $this->assertFalse(auth()->user()->can('create', Role::class));
    }

    /** @test */
    public function package_management_permissions_work_correctly()
    {
        $customerPackage = Package::factory()->create(['user_id' => $this->users['customer']->id]);
        $otherCustomerPackage = Package::factory()->create(['user_id' => User::factory()->create(['role_id' => $this->roles['customer']->id])->id]);

        // SuperAdmin should be able to manage all packages
        $this->actingAs($this->users['superadmin']);
        $this->assertTrue(auth()->user()->can('view', $customerPackage));
        $this->assertTrue(auth()->user()->can('update', $customerPackage));
        $this->assertTrue(auth()->user()->can('view', $otherCustomerPackage));

        // Admin should be able to manage all packages
        $this->actingAs($this->users['admin']);
        $this->assertTrue(auth()->user()->can('view', $customerPackage));
        $this->assertTrue(auth()->user()->can('update', $customerPackage));
        $this->assertTrue(auth()->user()->can('view', $otherCustomerPackage));

        // Customer should only be able to view their own packages
        $this->actingAs($this->users['customer']);
        $this->assertTrue(auth()->user()->can('view', $customerPackage));
        $this->assertFalse(auth()->user()->can('update', $customerPackage));
        $this->assertFalse(auth()->user()->can('view', $otherCustomerPackage));

        // Purchaser should be able to manage packages
        $this->actingAs($this->users['purchaser']);
        $this->assertTrue(auth()->user()->can('view', $customerPackage));
        $this->assertTrue(auth()->user()->can('update', $customerPackage));
    }

    /** @test */
    public function manifest_management_permissions_work_correctly()
    {
        $manifest = Manifest::factory()->create();

        // SuperAdmin should be able to manage manifests
        $this->actingAs($this->users['superadmin']);
        $this->assertTrue(auth()->user()->can('viewAny', Manifest::class));
        $this->assertTrue(auth()->user()->can('create', Manifest::class));
        $this->assertTrue(auth()->user()->can('update', $manifest));

        // Admin should be able to manage manifests
        $this->actingAs($this->users['admin']);
        $this->assertTrue(auth()->user()->can('viewAny', Manifest::class));
        $this->assertTrue(auth()->user()->can('create', Manifest::class));
        $this->assertTrue(auth()->user()->can('update', $manifest));

        // Customer should not be able to manage manifests
        $this->actingAs($this->users['customer']);
        $this->assertFalse(auth()->user()->can('viewAny', Manifest::class));
        $this->assertFalse(auth()->user()->can('create', Manifest::class));
        $this->assertFalse(auth()->user()->can('update', $manifest));

        // Purchaser should be able to view but not create/update manifests
        $this->actingAs($this->users['purchaser']);
        $this->assertTrue(auth()->user()->can('viewAny', Manifest::class));
        $this->assertFalse(auth()->user()->can('create', Manifest::class));
        $this->assertFalse(auth()->user()->can('update', $manifest));
    }

    /** @test */
    public function consolidated_package_permissions_work_correctly()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create(['customer_id' => $this->users['customer']->id]);
        $otherConsolidatedPackage = ConsolidatedPackage::factory()->create(['customer_id' => User::factory()->create(['role_id' => $this->roles['customer']->id])->id]);

        // SuperAdmin should be able to manage all consolidated packages
        $this->actingAs($this->users['superadmin']);
        $this->assertTrue(auth()->user()->can('view', $consolidatedPackage));
        $this->assertTrue(auth()->user()->can('update', $consolidatedPackage));
        $this->assertTrue(auth()->user()->can('view', $otherConsolidatedPackage));

        // Admin should be able to manage all consolidated packages
        $this->actingAs($this->users['admin']);
        $this->assertTrue(auth()->user()->can('view', $consolidatedPackage));
        $this->assertTrue(auth()->user()->can('update', $consolidatedPackage));
        $this->assertTrue(auth()->user()->can('view', $otherConsolidatedPackage));

        // Customer should only be able to view their own consolidated packages
        $this->actingAs($this->users['customer']);
        $this->assertTrue(auth()->user()->can('view', $consolidatedPackage));
        $this->assertFalse(auth()->user()->can('update', $consolidatedPackage));
        $this->assertFalse(auth()->user()->can('view', $otherConsolidatedPackage));

        // Purchaser should be able to manage consolidated packages
        $this->actingAs($this->users['purchaser']);
        $this->assertTrue(auth()->user()->can('view', $consolidatedPackage));
        $this->assertTrue(auth()->user()->can('update', $consolidatedPackage));
    }

    /** @test */
    public function route_access_permissions_work_correctly()
    {
        $routes = [
            // User management routes
            '/admin/users' => ['superadmin', 'admin'],
            '/admin/users/create' => ['superadmin', 'admin'],
            
            // Role management routes
            '/admin/roles' => ['superadmin'],
            
            // Dashboard routes (all authenticated users)
            '/dashboard' => ['superadmin', 'admin', 'customer', 'purchaser'],
        ];

        foreach ($routes as $route => $allowedRoles) {
            foreach ($this->users as $roleType => $user) {
                $this->actingAs($user);
                
                $response = $this->get($route);
                
                if (in_array($roleType, $allowedRoles)) {
                    $this->assertNotEquals(403, $response->getStatusCode(), 
                        "Route {$route} should be accessible to {$roleType}");
                } else {
                    $this->assertEquals(403, $response->getStatusCode(), 
                        "Route {$route} should NOT be accessible to {$roleType}");
                }
            }
        }
    }

    /** @test */
    public function role_based_scopes_return_correct_users()
    {
        // Test individual role scopes
        $superAdmins = User::superAdmins()->get();
        $this->assertCount(1, $superAdmins);
        $this->assertTrue($superAdmins->first()->isSuperAdmin());

        $admins = User::admins()->get();
        $this->assertCount(1, $admins);
        $this->assertTrue($admins->first()->isAdmin());

        $customers = User::customerUsers()->get();
        $this->assertCount(1, $customers);
        $this->assertTrue($customers->first()->isCustomer());

        $purchasers = User::purchasers()->get();
        $this->assertCount(1, $purchasers);
        $this->assertTrue($purchasers->first()->isPurchaser());

        // Test multi-role scopes
        $adminOrSuperAdmin = User::withAnyRole(['admin', 'superadmin'])->get();
        $this->assertCount(2, $adminOrSuperAdmin);

        $nonCustomers = User::withoutRole('customer')->get();
        $this->assertCount(3, $nonCustomers);
        $this->assertFalse($nonCustomers->contains($this->users['customer']));
    }

    /** @test */
    public function case_insensitive_role_checking_works()
    {
        $user = $this->users['customer'];

        // Test different case variations
        $this->assertTrue($user->hasRole('CUSTOMER'));
        $this->assertTrue($user->hasRole('Customer'));
        $this->assertTrue($user->hasRole('customer'));
        $this->assertTrue($user->hasRole('CuStOmEr'));

        // Test with hasAnyRole
        $this->assertTrue($user->hasAnyRole(['CUSTOMER', 'ADMIN']));
        $this->assertTrue($user->hasAnyRole(['Customer', 'Admin']));
        $this->assertFalse($user->hasAnyRole(['ADMIN', 'SUPERADMIN']));
    }

    /** @test */
    public function role_caching_works_correctly()
    {
        $user = $this->users['customer'];

        // First call should set cache
        $this->assertTrue($user->isCustomer());
        
        // Subsequent calls should use cache
        $this->assertTrue($user->hasRole('customer'));
        $this->assertFalse($user->isAdmin());

        // Verify cache is working by checking the cached role
        $this->assertNotNull($user->roleCache);
        $this->assertEquals('customer', $user->roleCache->name);
    }

    /** @test */
    public function permission_boundaries_are_enforced()
    {
        // Test that customers cannot access admin features
        $customer = $this->users['customer'];
        $this->assertFalse($customer->canManageUsers());
        $this->assertFalse($customer->canManageRoles());

        // Test that admins cannot manage roles
        $admin = $this->users['admin'];
        $this->assertTrue($admin->canManageUsers());
        $this->assertFalse($admin->canManageRoles());

        // Test that only superadmins can manage roles
        $superAdmin = $this->users['superadmin'];
        $this->assertTrue($superAdmin->canManageUsers());
        $this->assertTrue($superAdmin->canManageRoles());

        // Test that purchasers have limited permissions
        $purchaser = $this->users['purchaser'];
        $this->assertFalse($purchaser->canManageUsers());
        $this->assertFalse($purchaser->canManageRoles());
    }

    /** @test */
    public function role_hierarchy_is_respected()
    {
        // SuperAdmin should have highest privileges
        $superAdmin = $this->users['superadmin'];
        $this->assertTrue($superAdmin->canManageUsers());
        $this->assertTrue($superAdmin->canManageRoles());

        // Admin should have user management but not role management
        $admin = $this->users['admin'];
        $this->assertTrue($admin->canManageUsers());
        $this->assertFalse($admin->canManageRoles());

        // Purchaser should have package management but not user/role management
        $purchaser = $this->users['purchaser'];
        $this->assertFalse($purchaser->canManageUsers());
        $this->assertFalse($purchaser->canManageRoles());

        // Customer should have minimal privileges
        $customer = $this->users['customer'];
        $this->assertFalse($customer->canManageUsers());
        $this->assertFalse($customer->canManageRoles());
    }

    /** @test */
    public function all_system_roles_exist_and_are_functional()
    {
        $requiredRoles = ['superadmin', 'admin', 'customer', 'purchaser'];
        
        foreach ($requiredRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            $this->assertNotNull($role, "Required role '{$roleName}' does not exist");
            
            // Test that users can be assigned to this role
            $user = User::factory()->create(['role_id' => $role->id]);
            $this->assertTrue($user->hasRole($roleName));
        }
    }

    /** @test */
    public function role_based_navigation_visibility_works()
    {
        // This test would need to be expanded based on actual navigation implementation
        // For now, we test the underlying permission logic
        
        foreach ($this->users as $roleType => $user) {
            $this->actingAs($user);
            
            // Test user management visibility
            $canSeeUserManagement = in_array($roleType, ['admin', 'superadmin']);
            $this->assertEquals($canSeeUserManagement, auth()->user()->can('viewAny', User::class));
            
            // Test role management visibility
            $canSeeRoleManagement = ($roleType === 'superadmin');
            $this->assertEquals($canSeeRoleManagement, auth()->user()->can('viewAny', Role::class));
        }
    }
}