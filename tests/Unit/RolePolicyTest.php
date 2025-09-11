<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Policies\RolePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RolePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected $policy;
    protected $superAdmin;
    protected $admin;
    protected $customer;
    protected $role;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new RolePolicy();
        
        // Create or get existing roles
        $superAdminRole = Role::firstOrCreate(['name' => 'superadmin'], ['description' => 'Super Administrator']);
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);
        $customerRole = Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer']);
        
        // Create users
        $this->superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->customer = User::factory()->create(['role_id' => $customerRole->id]);
        
        // Create a test role
        $this->role = Role::create(['name' => 'test_role_' . uniqid(), 'description' => 'Test Role']);
    }

    /** @test */
    public function only_superadmin_can_view_any_roles()
    {
        $this->assertTrue($this->policy->viewAny($this->superAdmin));
        $this->assertFalse($this->policy->viewAny($this->admin));
        $this->assertFalse($this->policy->viewAny($this->customer));
    }

    /** @test */
    public function only_superadmin_can_view_specific_role()
    {
        $this->assertTrue($this->policy->view($this->superAdmin, $this->role));
        $this->assertFalse($this->policy->view($this->admin, $this->role));
        $this->assertFalse($this->policy->view($this->customer, $this->role));
    }

    /** @test */
    public function only_superadmin_can_create_roles()
    {
        $this->assertTrue($this->policy->create($this->superAdmin));
        $this->assertFalse($this->policy->create($this->admin));
        $this->assertFalse($this->policy->create($this->customer));
    }

    /** @test */
    public function only_superadmin_can_update_roles()
    {
        $this->assertTrue($this->policy->update($this->superAdmin, $this->role));
        $this->assertFalse($this->policy->update($this->admin, $this->role));
        $this->assertFalse($this->policy->update($this->customer, $this->role));
    }

    /** @test */
    public function superadmin_can_delete_deletable_roles()
    {
        // Since we can't easily mock the canBeDeleted method on the actual model,
        // we'll test with a role that should be deletable (custom role with no users)
        $this->assertTrue($this->policy->delete($this->superAdmin, $this->role));
        $this->assertFalse($this->policy->delete($this->admin, $this->role));
        $this->assertFalse($this->policy->delete($this->customer, $this->role));
    }

    /** @test */
    public function only_superadmin_can_manage_assignments()
    {
        $this->assertTrue($this->policy->manageAssignments($this->superAdmin));
        $this->assertFalse($this->policy->manageAssignments($this->admin));
        $this->assertFalse($this->policy->manageAssignments($this->customer));
    }

    /** @test */
    public function only_superadmin_can_view_audit_trail()
    {
        $this->assertTrue($this->policy->viewAuditTrail($this->superAdmin));
        $this->assertFalse($this->policy->viewAuditTrail($this->admin));
        $this->assertFalse($this->policy->viewAuditTrail($this->customer));
    }
}