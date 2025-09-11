<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Http\Livewire\Roles\Role as RoleComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class RoleManagementAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;
    protected $admin;
    protected $customer;
    protected $testRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create or get existing roles
        $superAdminRole = Role::firstOrCreate(['name' => 'superadmin'], ['description' => 'Super Administrator']);
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);
        $customerRole = Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer']);
        
        // Create users
        $this->superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->customer = User::factory()->create(['role_id' => $customerRole->id]);
        
        // Create a test role
        $this->testRole = Role::create(['name' => 'test_role_' . uniqid(), 'description' => 'Test Role']);
    }

    /** @test */
    public function superadmin_can_access_role_management()
    {
        $this->actingAs($this->superAdmin);
        
        $response = $this->get(route('roles.index'));
        $response->assertSuccessful();
        
        Livewire::test(RoleComponent::class)
            ->assertSuccessful();
    }

    /** @test */
    public function admin_cannot_access_role_management()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get(route('roles.index'));
        $response->assertStatus(403);
    }

    /** @test */
    public function customer_cannot_access_role_management()
    {
        $this->actingAs($this->customer);
        
        $response = $this->get(route('roles.index'));
        $response->assertStatus(403);
    }

    /** @test */
    public function superadmin_can_create_roles()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RoleComponent::class)
            ->call('showCreateModal')
            ->assertSet('showCreateModal', true)
            ->set('name', 'new_role')
            ->set('description', 'New Role Description')
            ->call('createRole')
            ->assertHasNoErrors();
            
        $this->assertDatabaseHas('roles', [
            'name' => 'new_role',
            'description' => 'New Role Description'
        ]);
    }

    /** @test */
    public function admin_cannot_create_roles()
    {
        $this->actingAs($this->admin);
        
        // This should fail at the component level due to authorization
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        
        Livewire::test(RoleComponent::class)
            ->call('showCreateModal');
    }

    /** @test */
    public function superadmin_can_update_roles()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RoleComponent::class)
            ->call('showEditModal', $this->testRole->id)
            ->assertSet('showEditModal', true)
            ->set('name', 'updated_role')
            ->set('description', 'Updated Description')
            ->call('updateRole')
            ->assertHasNoErrors();
            
        $this->assertDatabaseHas('roles', [
            'id' => $this->testRole->id,
            'name' => 'updated_role',
            'description' => 'Updated Description'
        ]);
    }

    /** @test */
    public function admin_cannot_update_roles()
    {
        $this->actingAs($this->admin);
        
        // This should fail at the component level due to authorization
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        
        Livewire::test(RoleComponent::class)
            ->call('showEditModal', $this->testRole->id);
    }

    /** @test */
    public function superadmin_can_delete_deletable_roles()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RoleComponent::class)
            ->call('showDeleteModal', $this->testRole->id)
            ->assertSet('showDeleteModal', true)
            ->call('deleteRole')
            ->assertHasNoErrors();
            
        $this->assertDatabaseMissing('roles', ['id' => $this->testRole->id]);
    }

    /** @test */
    public function admin_cannot_delete_roles()
    {
        $this->actingAs($this->admin);
        
        // This should fail at the component level due to authorization
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        
        Livewire::test(RoleComponent::class)
            ->call('showDeleteModal', $this->testRole->id);
    }

    /** @test */
    public function superadmin_can_view_audit_trail()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RoleComponent::class)
            ->call('showAuditModal')
            ->assertSet('showAuditModal', true)
            ->assertHasNoErrors();
    }

    /** @test */
    public function admin_cannot_view_audit_trail()
    {
        $this->actingAs($this->admin);
        
        // This should fail at the component level due to authorization
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        
        Livewire::test(RoleComponent::class)
            ->call('showAuditModal');
    }

    /** @test */
    public function superadmin_can_manage_assignments()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RoleComponent::class)
            ->call('showAssignmentModal')
            ->assertSet('showAssignmentModal', true)
            ->assertHasNoErrors();
    }

    /** @test */
    public function admin_cannot_manage_assignments()
    {
        $this->actingAs($this->admin);
        
        // This should fail at the component level due to authorization
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        
        Livewire::test(RoleComponent::class)
            ->call('showAssignmentModal');
    }

    /** @test */
    public function superadmin_cannot_delete_system_roles()
    {
        $this->actingAs($this->superAdmin);
        
        $systemRole = Role::where('name', 'superadmin')->first();
        
        Livewire::test(RoleComponent::class)
            ->call('showDeleteModal', $systemRole->id)
            ->call('deleteRole')
            ->assertHasErrors();
            
        $this->assertDatabaseHas('roles', ['id' => $systemRole->id]);
    }

    /** @test */
    public function superadmin_cannot_delete_roles_with_users()
    {
        $this->actingAs($this->superAdmin);
        
        $roleWithUsers = Role::where('name', 'customer')->first();
        
        Livewire::test(RoleComponent::class)
            ->call('showDeleteModal', $roleWithUsers->id)
            ->call('deleteRole')
            ->assertHasErrors();
            
        $this->assertDatabaseHas('roles', ['id' => $roleWithUsers->id]);
    }

    /** @test */
    public function role_management_routes_require_proper_authorization()
    {
        // Test role index route
        $response = $this->get(route('roles.index'));
        $response->assertRedirect(route('login'));
        
        $this->actingAs($this->admin);
        $response = $this->get(route('roles.index'));
        $response->assertStatus(403);
        
        $this->actingAs($this->superAdmin);
        $response = $this->get(route('roles.index'));
        $response->assertSuccessful();
    }

    /** @test */
    public function role_audit_trail_route_requires_proper_authorization()
    {
        // Test audit trail route
        $response = $this->get(route('roles.audit-trail'));
        $response->assertRedirect(route('login'));
        
        $this->actingAs($this->admin);
        $response = $this->get(route('roles.audit-trail'));
        $response->assertStatus(403);
        
        $this->actingAs($this->superAdmin);
        $response = $this->get(route('roles.audit-trail'));
        $response->assertSuccessful();
    }

    /** @test */
    public function role_assignments_route_requires_proper_authorization()
    {
        // Test assignments route
        $response = $this->get(route('roles.assignments'));
        $response->assertRedirect(route('login'));
        
        $this->actingAs($this->admin);
        $response = $this->get(route('roles.assignments'));
        $response->assertStatus(403);
        
        $this->actingAs($this->superAdmin);
        $response = $this->get(route('roles.assignments'));
        $response->assertSuccessful();
    }
}