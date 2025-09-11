<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\RoleChangeAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Livewire\Livewire;
use App\Http\Livewire\Users\UserCreate;
use App\Http\Livewire\Users\UserManagement;
use App\Http\Livewire\Users\UserEdit;
use App\Http\Livewire\Roles\Role as RoleComponent;

class RoleManagementIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $superAdminUser;
    protected $adminUser;
    protected $customerUser;
    protected $purchaserUser;
    protected $roles;

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

        // Create test users
        $this->superAdminUser = User::factory()->create(['role_id' => $this->roles['superadmin']->id]);
        $this->adminUser = User::factory()->create(['role_id' => $this->roles['admin']->id]);
        $this->customerUser = User::factory()->create(['role_id' => $this->roles['customer']->id]);
        $this->purchaserUser = User::factory()->create(['role_id' => $this->roles['purchaser']->id]);
    }

    /** @test */
    public function superadmin_can_access_all_management_features()
    {
        $this->actingAs($this->superAdminUser);

        // Can access user management
        $response = $this->get('/admin/users');
        $response->assertStatus(200);

        // Can access user creation
        $response = $this->get('/admin/users/create');
        $response->assertStatus(200);

        // Can access role management
        $response = $this->get('/admin/roles');
        $response->assertStatus(200);

        // Can create users with any role
        Livewire::test(UserCreate::class)
            ->set('firstName', 'Test')
            ->set('lastName', 'User')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('selectedRole', 'admin')
            ->call('createUser')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role_id' => $this->roles['admin']->id
        ]);
    }

    /** @test */
    public function admin_can_manage_users_but_not_roles()
    {
        $this->actingAs($this->adminUser);

        // Can access user management
        $response = $this->get('/admin/users');
        $response->assertStatus(200);

        // Can access user creation
        $response = $this->get('/admin/users/create');
        $response->assertStatus(200);

        // Cannot access role management
        $response = $this->get('/admin/roles');
        $response->assertStatus(403);

        // Can create users but not superadmins
        Livewire::test(UserCreate::class)
            ->set('firstName', 'Test')
            ->set('lastName', 'Customer')
            ->set('email', 'customer@example.com')
            ->set('password', 'password123')
            ->set('selectedRole', 'customer')
            ->call('createUser')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'customer@example.com',
            'role_id' => $this->roles['customer']->id
        ]);
    }

    /** @test */
    public function customer_cannot_access_management_features()
    {
        $this->actingAs($this->customerUser);

        // Cannot access user management
        $response = $this->get('/admin/users');
        $response->assertStatus(403);

        // Cannot access user creation
        $response = $this->get('/admin/users/create');
        $response->assertStatus(403);

        // Cannot access role management
        $response = $this->get('/admin/roles');
        $response->assertStatus(403);
    }

    /** @test */
    public function purchaser_cannot_access_management_features()
    {
        $this->actingAs($this->purchaserUser);

        // Cannot access user management
        $response = $this->get('/admin/users');
        $response->assertStatus(403);

        // Cannot access user creation
        $response = $this->get('/admin/users/create');
        $response->assertStatus(403);

        // Cannot access role management
        $response = $this->get('/admin/roles');
        $response->assertStatus(403);
    }

    /** @test */
    public function role_helper_methods_work_correctly()
    {
        // Test superadmin methods
        $this->assertTrue($this->superAdminUser->isSuperAdmin());
        $this->assertFalse($this->superAdminUser->isAdmin());
        $this->assertFalse($this->superAdminUser->isCustomer());
        $this->assertFalse($this->superAdminUser->isPurchaser());
        $this->assertTrue($this->superAdminUser->hasRole('superadmin'));
        $this->assertTrue($this->superAdminUser->canManageUsers());
        $this->assertTrue($this->superAdminUser->canManageRoles());

        // Test admin methods
        $this->assertFalse($this->adminUser->isSuperAdmin());
        $this->assertTrue($this->adminUser->isAdmin());
        $this->assertFalse($this->adminUser->isCustomer());
        $this->assertFalse($this->adminUser->isPurchaser());
        $this->assertTrue($this->adminUser->hasRole('admin'));
        $this->assertTrue($this->adminUser->canManageUsers());
        $this->assertFalse($this->adminUser->canManageRoles());

        // Test customer methods
        $this->assertFalse($this->customerUser->isSuperAdmin());
        $this->assertFalse($this->customerUser->isAdmin());
        $this->assertTrue($this->customerUser->isCustomer());
        $this->assertFalse($this->customerUser->isPurchaser());
        $this->assertTrue($this->customerUser->hasRole('customer'));
        $this->assertFalse($this->customerUser->canManageUsers());
        $this->assertFalse($this->customerUser->canManageRoles());

        // Test purchaser methods
        $this->assertFalse($this->purchaserUser->isSuperAdmin());
        $this->assertFalse($this->purchaserUser->isAdmin());
        $this->assertFalse($this->purchaserUser->isCustomer());
        $this->assertTrue($this->purchaserUser->isPurchaser());
        $this->assertTrue($this->purchaserUser->hasRole('purchaser'));
        $this->assertFalse($this->purchaserUser->canManageUsers());
        $this->assertFalse($this->purchaserUser->canManageRoles());
    }

    /** @test */
    public function multi_role_checking_methods_work_correctly()
    {
        // Test hasAnyRole
        $this->assertTrue($this->superAdminUser->hasAnyRole(['admin', 'superadmin']));
        $this->assertTrue($this->adminUser->hasAnyRole(['admin', 'superadmin']));
        $this->assertFalse($this->customerUser->hasAnyRole(['admin', 'superadmin']));
        $this->assertFalse($this->purchaserUser->hasAnyRole(['admin', 'superadmin']));

        // Test with customer and purchaser roles
        $this->assertFalse($this->superAdminUser->hasAnyRole(['customer', 'purchaser']));
        $this->assertFalse($this->adminUser->hasAnyRole(['customer', 'purchaser']));
        $this->assertTrue($this->customerUser->hasAnyRole(['customer', 'purchaser']));
        $this->assertTrue($this->purchaserUser->hasAnyRole(['customer', 'purchaser']));

        // Test hasAllRoles (single role system, so only works with single role arrays)
        $this->assertTrue($this->superAdminUser->hasAllRoles(['superadmin']));
        $this->assertFalse($this->superAdminUser->hasAllRoles(['admin']));
        $this->assertFalse($this->superAdminUser->hasAllRoles(['admin', 'superadmin'])); // Can't have multiple roles
    }

    /** @test */
    public function query_scopes_work_correctly()
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

        // Test withRole scope
        $adminUsers = User::withRole('admin')->get();
        $this->assertCount(1, $adminUsers);
        $this->assertTrue($adminUsers->first()->isAdmin());

        // Test withAnyRole scope
        $adminOrSuperAdmin = User::withAnyRole(['admin', 'superadmin'])->get();
        $this->assertCount(2, $adminOrSuperAdmin);

        // Test withoutRole scope
        $nonCustomers = User::withoutRole('customer')->get();
        $this->assertCount(3, $nonCustomers); // superadmin, admin, purchaser
        $this->assertFalse($nonCustomers->contains($this->customerUser));
    }

    /** @test */
    public function role_change_audit_logging_works()
    {
        $this->actingAs($this->superAdminUser);

        $testUser = User::factory()->create(['role_id' => $this->roles['customer']->id]);
        $oldRoleId = $testUser->role_id;

        // Change user role through UserEdit component
        Livewire::test(UserEdit::class, ['user' => $testUser])
            ->set('newRole', 'admin')
            ->set('roleChangeReason', 'Promotion to admin role')
            ->call('changeRole')
            ->assertHasNoErrors();

        // Verify role change
        $testUser->refresh();
        $this->assertEquals($this->roles['admin']->id, $testUser->role_id);

        // Verify audit log entry
        $this->assertDatabaseHas('role_change_audits', [
            'user_id' => $testUser->id,
            'changed_by_user_id' => $this->superAdminUser->id,
            'old_role_id' => $oldRoleId,
            'new_role_id' => $this->roles['admin']->id,
            'reason' => 'Promotion to admin role'
        ]);

        $auditEntry = RoleChangeAudit::where('user_id', $testUser->id)->first();
        $this->assertNotNull($auditEntry->ip_address);
        $this->assertNotNull($auditEntry->user_agent);
    }

    /** @test */
    public function role_management_component_works_correctly()
    {
        $this->actingAs($this->superAdminUser);

        // Test role creation
        Livewire::test(RoleComponent::class)
            ->set('newRoleName', 'manager')
            ->set('newRoleDescription', 'Manager Role')
            ->call('createRole')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', [
            'name' => 'manager',
            'description' => 'Manager Role'
        ]);

        // Test role editing
        $managerRole = Role::where('name', 'manager')->first();
        Livewire::test(RoleComponent::class)
            ->set('editingRoleId', $managerRole->id)
            ->set('editingRoleName', 'senior_manager')
            ->set('editingRoleDescription', 'Senior Manager Role')
            ->call('updateRole')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', [
            'id' => $managerRole->id,
            'name' => 'senior_manager',
            'description' => 'Senior Manager Role'
        ]);

        // Test role deletion (should work for custom roles)
        Livewire::test(RoleComponent::class)
            ->call('deleteRole', $managerRole->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('roles', [
            'id' => $managerRole->id
        ]);
    }

    /** @test */
    public function system_roles_cannot_be_deleted()
    {
        $this->actingAs($this->superAdminUser);

        // Try to delete system roles - should fail
        foreach (['superadmin', 'admin', 'customer', 'purchaser'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            
            Livewire::test(RoleComponent::class)
                ->call('deleteRole', $role->id)
                ->assertHasErrors();

            // Verify role still exists
            $this->assertDatabaseHas('roles', [
                'id' => $role->id,
                'name' => $roleName
            ]);
        }
    }

    /** @test */
    public function roles_with_assigned_users_cannot_be_deleted()
    {
        $this->actingAs($this->superAdminUser);

        // Create a custom role and assign a user to it
        $customRole = Role::create(['name' => 'test_role', 'description' => 'Test Role']);
        $testUser = User::factory()->create(['role_id' => $customRole->id]);

        // Try to delete role with assigned users - should fail
        Livewire::test(RoleComponent::class)
            ->call('deleteRole', $customRole->id)
            ->assertHasErrors();

        // Verify role still exists
        $this->assertDatabaseHas('roles', [
            'id' => $customRole->id,
            'name' => 'test_role'
        ]);

        // Remove user assignment and try again - should succeed
        $testUser->delete();
        
        Livewire::test(RoleComponent::class)
            ->call('deleteRole', $customRole->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('roles', [
            'id' => $customRole->id
        ]);
    }

    /** @test */
    public function user_creation_with_different_roles_works()
    {
        $this->actingAs($this->superAdminUser);

        $testCases = [
            ['role' => 'customer', 'requiresProfile' => true],
            ['role' => 'admin', 'requiresProfile' => false],
            ['role' => 'purchaser', 'requiresProfile' => false],
            ['role' => 'superadmin', 'requiresProfile' => false],
        ];

        foreach ($testCases as $index => $testCase) {
            $email = "test{$index}@example.com";
            
            $component = Livewire::test(UserCreate::class)
                ->set('firstName', 'Test')
                ->set('lastName', 'User')
                ->set('email', $email)
                ->set('password', 'password123')
                ->set('selectedRole', $testCase['role']);

            if ($testCase['requiresProfile']) {
                $component
                    ->set('taxNumber', 'TAX123')
                    ->set('telephoneNumber', '555-1234')
                    ->set('parish', 'Test Parish')
                    ->set('streetAddress', '123 Test St')
                    ->set('cityTown', 'Test City')
                    ->set('country', 'Test Country');
            }

            $component->call('createUser')->assertHasNoErrors();

            // Verify user was created with correct role
            $user = User::where('email', $email)->first();
            $this->assertNotNull($user);
            $this->assertEquals($this->roles[$testCase['role']]->id, $user->role_id);
            
            if ($testCase['requiresProfile']) {
                $this->assertNotNull($user->profile);
                $this->assertEquals('TAX123', $user->profile->tax_number);
            }
        }
    }

    /** @test */
    public function admin_cannot_create_superadmin_users()
    {
        $this->actingAs($this->adminUser);

        // Admin should not be able to create superadmin users
        Livewire::test(UserCreate::class)
            ->set('firstName', 'Test')
            ->set('lastName', 'SuperAdmin')
            ->set('email', 'testsuperadmin@example.com')
            ->set('password', 'password123')
            ->set('selectedRole', 'superadmin')
            ->call('createUser')
            ->assertHasErrors();

        // Verify user was not created
        $this->assertDatabaseMissing('users', [
            'email' => 'testsuperadmin@example.com'
        ]);
    }

    /** @test */
    public function role_caching_works_correctly()
    {
        $user = $this->customerUser;

        // First call should query database
        $this->assertTrue($user->isCustomer());
        
        // Subsequent calls should use cache
        $this->assertTrue($user->hasRole('customer'));
        $this->assertFalse($user->isAdmin());

        // Change role and verify cache is cleared
        $user->role_id = $this->roles['admin']->id;
        $user->save();
        $user->refresh();

        // Should reflect new role
        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isCustomer());
    }

    /** @test */
    public function navigation_visibility_is_role_based()
    {
        // Test superadmin sees all navigation items
        $this->actingAs($this->superAdminUser);
        $response = $this->get('/dashboard');
        $response->assertSee('User Management');
        $response->assertSee('Role Management');

        // Test admin sees user management but not role management
        $this->actingAs($this->adminUser);
        $response = $this->get('/dashboard');
        $response->assertSee('User Management');
        $response->assertDontSee('Role Management');

        // Test customer sees neither
        $this->actingAs($this->customerUser);
        $response = $this->get('/dashboard');
        $response->assertDontSee('User Management');
        $response->assertDontSee('Role Management');
    }

    /** @test */
    public function backward_compatibility_is_maintained()
    {
        // Test that existing code using role IDs still works
        $this->assertEquals(3, $this->customerUser->role_id); // Assuming customer role has ID 3
        
        // Test that old-style role checking still works alongside new methods
        $customerRole = Role::where('name', 'customer')->first();
        $this->assertEquals($customerRole->id, $this->customerUser->role_id);
        
        // Both old and new approaches should give same result
        $oldStyleCheck = ($this->customerUser->role_id === $customerRole->id);
        $newStyleCheck = $this->customerUser->hasRole('customer');
        $this->assertEquals($oldStyleCheck, $newStyleCheck);
    }

    /** @test */
    public function case_insensitive_role_checking_works()
    {
        // Test case insensitive role checking
        $this->assertTrue($this->customerUser->hasRole('CUSTOMER'));
        $this->assertTrue($this->customerUser->hasRole('Customer'));
        $this->assertTrue($this->customerUser->hasRole('customer'));
        $this->assertTrue($this->customerUser->hasRole('CuStOmEr'));

        $this->assertTrue($this->adminUser->hasRole('ADMIN'));
        $this->assertTrue($this->adminUser->hasRole('Admin'));
        $this->assertTrue($this->adminUser->hasRole('admin'));

        // Test with hasAnyRole
        $this->assertTrue($this->adminUser->hasAnyRole(['ADMIN', 'SUPERADMIN']));
        $this->assertTrue($this->adminUser->hasAnyRole(['Admin', 'SuperAdmin']));
    }
}