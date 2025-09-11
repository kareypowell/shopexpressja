<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;
use Tests\TestCase;

class RolePolicyTest extends TestCase
{
    protected $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new RolePolicy();
    }

    /** @test */
    public function only_superadmin_can_view_any_roles()
    {
        $superAdmin = $this->createMockUser('superadmin');
        $admin = $this->createMockUser('admin');
        $customer = $this->createMockUser('customer');
        
        $this->assertTrue($this->policy->viewAny($superAdmin));
        $this->assertFalse($this->policy->viewAny($admin));
        $this->assertFalse($this->policy->viewAny($customer));
    }

    /** @test */
    public function only_superadmin_can_view_specific_role()
    {
        $superAdmin = $this->createMockUser('superadmin');
        $admin = $this->createMockUser('admin');
        $customer = $this->createMockUser('customer');
        $role = new Role(['name' => 'test_role']);
        
        $this->assertTrue($this->policy->view($superAdmin, $role));
        $this->assertFalse($this->policy->view($admin, $role));
        $this->assertFalse($this->policy->view($customer, $role));
    }

    /** @test */
    public function only_superadmin_can_create_roles()
    {
        $superAdmin = $this->createMockUser('superadmin');
        $admin = $this->createMockUser('admin');
        $customer = $this->createMockUser('customer');
        
        $this->assertTrue($this->policy->create($superAdmin));
        $this->assertFalse($this->policy->create($admin));
        $this->assertFalse($this->policy->create($customer));
    }

    /** @test */
    public function only_superadmin_can_update_roles()
    {
        $superAdmin = $this->createMockUser('superadmin');
        $admin = $this->createMockUser('admin');
        $customer = $this->createMockUser('customer');
        $role = new Role(['name' => 'test_role']);
        
        $this->assertTrue($this->policy->update($superAdmin, $role));
        $this->assertFalse($this->policy->update($admin, $role));
        $this->assertFalse($this->policy->update($customer, $role));
    }

    /** @test */
    public function only_superadmin_can_manage_assignments()
    {
        $superAdmin = $this->createMockUser('superadmin');
        $admin = $this->createMockUser('admin');
        $customer = $this->createMockUser('customer');
        
        $this->assertTrue($this->policy->manageAssignments($superAdmin));
        $this->assertFalse($this->policy->manageAssignments($admin));
        $this->assertFalse($this->policy->manageAssignments($customer));
    }

    /** @test */
    public function only_superadmin_can_view_audit_trail()
    {
        $superAdmin = $this->createMockUser('superadmin');
        $admin = $this->createMockUser('admin');
        $customer = $this->createMockUser('customer');
        
        $this->assertTrue($this->policy->viewAuditTrail($superAdmin));
        $this->assertFalse($this->policy->viewAuditTrail($admin));
        $this->assertFalse($this->policy->viewAuditTrail($customer));
    }

    private function createMockUser($roleName)
    {
        $user = $this->createMock(User::class);
        
        // Mock the role helper methods
        $user->method('isSuperAdmin')->willReturn($roleName === 'superadmin');
        $user->method('isAdmin')->willReturn($roleName === 'admin');
        $user->method('isCustomer')->willReturn($roleName === 'customer');
        
        return $user;
    }
}