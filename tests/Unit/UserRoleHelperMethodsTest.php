<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class UserRoleHelperMethodsTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdminRole;
    protected $adminRole;
    protected $customerRole;
    protected $purchaserRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $this->superAdminRole = Role::factory()->create(['name' => 'superadmin']);
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->customerRole = Role::factory()->create(['name' => 'customer']);
        $this->purchaserRole = Role::factory()->create(['name' => 'purchaser']);
    }

    /** @test */
    public function it_caches_role_to_avoid_repeated_database_queries()
    {
        $user = User::factory()->create(['role_id' => $this->adminRole->id]);
        
        // First call should load the role
        $this->assertTrue($user->isAdmin());
        
        // Subsequent calls should use cached role
        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isCustomer());
        
        // Verify role is cached
        $this->assertNotNull($user->getCachedRole());
        $this->assertEquals('admin', $user->getCachedRole()->name);
    }

    /** @test */
    public function it_clears_role_cache_when_requested()
    {
        $user = User::factory()->create(['role_id' => $this->adminRole->id]);
        
        // Load role into cache
        $user->isAdmin();
        $this->assertNotNull($user->getCachedRole());
        
        // Clear cache
        $user->clearRoleCache();
        
        // Cache should be cleared but role should still work
        $this->assertTrue($user->isAdmin());
    }

    /** @test */
    public function has_role_is_case_insensitive()
    {
        $user = User::factory()->create(['role_id' => $this->adminRole->id]);
        
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('ADMIN'));
        $this->assertTrue($user->hasRole('Admin'));
        $this->assertTrue($user->hasRole('aDmIn'));
    }

    /** @test */
    public function has_any_role_returns_true_when_user_has_one_of_the_roles()
    {
        $adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
        $customerUser = User::factory()->create(['role_id' => $this->customerRole->id]);
        
        $this->assertTrue($adminUser->hasAnyRole(['admin', 'customer']));
        $this->assertTrue($adminUser->hasAnyRole(['superadmin', 'admin']));
        $this->assertFalse($adminUser->hasAnyRole(['customer', 'purchaser']));
        
        $this->assertTrue($customerUser->hasAnyRole(['admin', 'customer']));
        $this->assertFalse($customerUser->hasAnyRole(['admin', 'superadmin']));
    }

    /** @test */
    public function has_any_role_is_case_insensitive()
    {
        $user = User::factory()->create(['role_id' => $this->adminRole->id]);
        
        $this->assertTrue($user->hasAnyRole(['ADMIN', 'customer']));
        $this->assertTrue($user->hasAnyRole(['Admin', 'Customer']));
        $this->assertTrue($user->hasAnyRole(['aDmIn', 'CuStOmEr']));
    }

    /** @test */
    public function has_any_role_returns_false_when_user_has_none_of_the_roles()
    {
        $adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
        
        $this->assertFalse($adminUser->hasAnyRole(['customer', 'purchaser']));
        $this->assertFalse($adminUser->hasAnyRole([]));
    }

    /** @test */
    public function has_any_role_returns_false_for_empty_array()
    {
        $user = User::factory()->create(['role_id' => $this->adminRole->id]);
        
        $this->assertFalse($user->hasAnyRole([]));
    }

    /** @test */
    public function has_all_roles_returns_true_only_for_single_matching_role()
    {
        $adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
        
        // Should return true for single matching role
        $this->assertTrue($adminUser->hasAllRoles(['admin']));
        
        // Should return false for single non-matching role
        $this->assertFalse($adminUser->hasAllRoles(['customer']));
        
        // Should return false for multiple roles (since user can only have one)
        $this->assertFalse($adminUser->hasAllRoles(['admin', 'customer']));
        $this->assertFalse($adminUser->hasAllRoles(['admin', 'superadmin']));
    }

    /** @test */
    public function has_all_roles_is_case_insensitive()
    {
        $user = User::factory()->create(['role_id' => $this->adminRole->id]);
        
        $this->assertTrue($user->hasAllRoles(['ADMIN']));
        $this->assertTrue($user->hasAllRoles(['Admin']));
        $this->assertTrue($user->hasAllRoles(['aDmIn']));
    }

    /** @test */
    public function has_all_roles_returns_false_for_empty_array()
    {
        $user = User::factory()->create(['role_id' => $this->adminRole->id]);
        
        $this->assertFalse($user->hasAllRoles([]));
    }

    /** @test */
    public function has_all_roles_returns_false_for_multiple_roles()
    {
        $user = User::factory()->create(['role_id' => $this->adminRole->id]);
        
        // Should return false for multiple roles since user can only have one
        $this->assertFalse($user->hasAllRoles(['admin', 'customer']));
        $this->assertFalse($user->hasAllRoles(['admin', 'superadmin', 'customer']));
    }

    /** @test */
    public function role_specific_methods_work_correctly()
    {
        $superAdminUser = User::factory()->create(['role_id' => $this->superAdminRole->id]);
        $adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
        $customerUser = User::factory()->create(['role_id' => $this->customerRole->id]);
        $purchaserUser = User::factory()->create(['role_id' => $this->purchaserRole->id]);
        
        // Test isSuperAdmin
        $this->assertTrue($superAdminUser->isSuperAdmin());
        $this->assertFalse($adminUser->isSuperAdmin());
        $this->assertFalse($customerUser->isSuperAdmin());
        $this->assertFalse($purchaserUser->isSuperAdmin());
        
        // Test isAdmin
        $this->assertFalse($superAdminUser->isAdmin());
        $this->assertTrue($adminUser->isAdmin());
        $this->assertFalse($customerUser->isAdmin());
        $this->assertFalse($purchaserUser->isAdmin());
        
        // Test isCustomer
        $this->assertFalse($superAdminUser->isCustomer());
        $this->assertFalse($adminUser->isCustomer());
        $this->assertTrue($customerUser->isCustomer());
        $this->assertFalse($purchaserUser->isCustomer());
        
        // Test isPurchaser
        $this->assertFalse($superAdminUser->isPurchaser());
        $this->assertFalse($adminUser->isPurchaser());
        $this->assertFalse($customerUser->isPurchaser());
        $this->assertTrue($purchaserUser->isPurchaser());
    }

    /** @test */
    public function can_manage_users_returns_true_for_admin_and_superadmin()
    {
        $superAdminUser = User::factory()->create(['role_id' => $this->superAdminRole->id]);
        $adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
        $customerUser = User::factory()->create(['role_id' => $this->customerRole->id]);
        $purchaserUser = User::factory()->create(['role_id' => $this->purchaserRole->id]);
        
        $this->assertTrue($superAdminUser->canManageUsers());
        $this->assertTrue($adminUser->canManageUsers());
        $this->assertFalse($customerUser->canManageUsers());
        $this->assertFalse($purchaserUser->canManageUsers());
    }

    /** @test */
    public function can_manage_roles_returns_true_only_for_superadmin()
    {
        $superAdminUser = User::factory()->create(['role_id' => $this->superAdminRole->id]);
        $adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
        $customerUser = User::factory()->create(['role_id' => $this->customerRole->id]);
        $purchaserUser = User::factory()->create(['role_id' => $this->purchaserRole->id]);
        
        $this->assertTrue($superAdminUser->canManageRoles());
        $this->assertFalse($adminUser->canManageRoles());
        $this->assertFalse($customerUser->canManageRoles());
        $this->assertFalse($purchaserUser->canManageRoles());
    }

    /** @test */
    public function can_access_admin_panel_returns_true_for_admin_and_superadmin()
    {
        $superAdminUser = User::factory()->create(['role_id' => $this->superAdminRole->id]);
        $adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
        $customerUser = User::factory()->create(['role_id' => $this->customerRole->id]);
        $purchaserUser = User::factory()->create(['role_id' => $this->purchaserRole->id]);
        
        $this->assertTrue($superAdminUser->canAccessAdminPanel());
        $this->assertTrue($adminUser->canAccessAdminPanel());
        $this->assertFalse($customerUser->canAccessAdminPanel());
        $this->assertFalse($purchaserUser->canAccessAdminPanel());
    }

    /** @test */
    public function get_role_name_returns_correct_role_name()
    {
        $adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
        $customerUser = User::factory()->create(['role_id' => $this->customerRole->id]);
        $superAdminUser = User::factory()->create(['role_id' => $this->superAdminRole->id]);
        $purchaserUser = User::factory()->create(['role_id' => $this->purchaserRole->id]);
        
        $this->assertEquals('admin', $adminUser->getRoleName());
        $this->assertEquals('customer', $customerUser->getRoleName());
        $this->assertEquals('superadmin', $superAdminUser->getRoleName());
        $this->assertEquals('purchaser', $purchaserUser->getRoleName());
    }

    /** @test */
    public function get_role_description_returns_correct_description()
    {
        $role = Role::factory()->create([
            'name' => 'test_role',
            'description' => 'Test role description'
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);
        
        $adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
        
        $this->assertEquals('Test role description', $user->getRoleDescription());
        $this->assertEquals($this->adminRole->description, $adminUser->getRoleDescription());
    }

    /** @test */
    public function role_caching_works_across_multiple_method_calls()
    {
        $user = User::factory()->create(['role_id' => $this->adminRole->id]);
        
        // Multiple calls should use cached role
        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isCustomer());
        $this->assertTrue($user->canManageUsers());
        $this->assertFalse($user->canManageRoles());
        $this->assertTrue($user->canAccessAdminPanel());
        $this->assertEquals('admin', $user->getRoleName());
        
        // Verify role is still cached
        $this->assertNotNull($user->getCachedRole());
        $this->assertEquals('admin', $user->getCachedRole()->name);
    }

    /** @test */
    public function role_methods_handle_deleted_role_gracefully()
    {
        $role = Role::factory()->create(['name' => 'test_role']);
        $user = User::factory()->create(['role_id' => $role->id]);
        
        // Delete the role to simulate missing role relationship
        $role->delete();
        
        // Clear the relationship cache
        $user->clearRoleCache();
        
        $this->assertFalse($user->hasRole('admin'));
        $this->assertFalse($user->hasAnyRole(['admin', 'customer']));
        $this->assertFalse($user->hasAllRoles(['admin']));
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isCustomer());
        $this->assertFalse($user->isSuperAdmin());
        $this->assertFalse($user->isPurchaser());
        $this->assertFalse($user->canManageUsers());
        $this->assertFalse($user->canManageRoles());
        $this->assertFalse($user->canAccessAdminPanel());
        $this->assertNull($user->getRoleName());
        $this->assertNull($user->getRoleDescription());
    }
}