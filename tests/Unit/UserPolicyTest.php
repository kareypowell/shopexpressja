<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected $policy;
    protected $superAdmin;
    protected $admin;
    protected $customer;
    protected $purchaser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new UserPolicy();
        
        // Create or get existing roles
        $superAdminRole = Role::firstOrCreate(['name' => 'superadmin'], ['description' => 'Super Administrator']);
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);
        $customerRole = Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer']);
        $purchaserRole = Role::firstOrCreate(['name' => 'purchaser'], ['description' => 'Purchaser']);
        
        // Create users
        $this->superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->customer = User::factory()->create(['role_id' => $customerRole->id]);
        $this->purchaser = User::factory()->create(['role_id' => $purchaserRole->id]);
    }

    /** @test */
    public function superadmin_can_view_any_users()
    {
        $this->assertTrue($this->policy->viewAny($this->superAdmin));
    }

    /** @test */
    public function admin_can_view_any_users()
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    /** @test */
    public function customer_cannot_view_any_users()
    {
        $this->assertFalse($this->policy->viewAny($this->customer));
    }

    /** @test */
    public function purchaser_cannot_view_any_users()
    {
        $this->assertFalse($this->policy->viewAny($this->purchaser));
    }

    /** @test */
    public function superadmin_can_view_any_user()
    {
        $this->assertTrue($this->policy->view($this->superAdmin, $this->admin));
        $this->assertTrue($this->policy->view($this->superAdmin, $this->customer));
        $this->assertTrue($this->policy->view($this->superAdmin, $this->purchaser));
    }

    /** @test */
    public function admin_can_view_customers_and_own_profile()
    {
        $this->assertTrue($this->policy->view($this->admin, $this->customer));
        $this->assertTrue($this->policy->view($this->admin, $this->admin));
        $this->assertFalse($this->policy->view($this->admin, $this->superAdmin));
        $this->assertFalse($this->policy->view($this->admin, $this->purchaser));
    }

    /** @test */
    public function customer_can_only_view_own_profile()
    {
        $this->assertTrue($this->policy->view($this->customer, $this->customer));
        $this->assertFalse($this->policy->view($this->customer, $this->admin));
        $this->assertFalse($this->policy->view($this->customer, $this->superAdmin));
    }

    /** @test */
    public function superadmin_can_create_users()
    {
        $this->assertTrue($this->policy->create($this->superAdmin));
    }

    /** @test */
    public function admin_can_create_users()
    {
        $this->assertTrue($this->policy->create($this->admin));
    }

    /** @test */
    public function customer_cannot_create_users()
    {
        $this->assertFalse($this->policy->create($this->customer));
    }

    /** @test */
    public function superadmin_can_update_any_user()
    {
        $this->assertTrue($this->policy->update($this->superAdmin, $this->admin));
        $this->assertTrue($this->policy->update($this->superAdmin, $this->customer));
        $this->assertTrue($this->policy->update($this->superAdmin, $this->purchaser));
    }

    /** @test */
    public function admin_can_update_customers_and_own_profile()
    {
        $this->assertTrue($this->policy->update($this->admin, $this->customer));
        $this->assertTrue($this->policy->update($this->admin, $this->admin));
        $this->assertFalse($this->policy->update($this->admin, $this->superAdmin));
        $this->assertFalse($this->policy->update($this->admin, $this->purchaser));
    }

    /** @test */
    public function customer_can_only_update_own_profile()
    {
        $this->assertTrue($this->policy->update($this->customer, $this->customer));
        $this->assertFalse($this->policy->update($this->customer, $this->admin));
        $this->assertFalse($this->policy->update($this->customer, $this->superAdmin));
    }

    /** @test */
    public function superadmin_can_delete_any_user_except_themselves()
    {
        $this->assertTrue($this->policy->delete($this->superAdmin, $this->admin));
        $this->assertTrue($this->policy->delete($this->superAdmin, $this->customer));
        $this->assertFalse($this->policy->delete($this->superAdmin, $this->superAdmin));
    }

    /** @test */
    public function admin_can_delete_customers_only()
    {
        $this->assertTrue($this->policy->delete($this->admin, $this->customer));
        $this->assertFalse($this->policy->delete($this->admin, $this->admin));
        $this->assertFalse($this->policy->delete($this->admin, $this->superAdmin));
        $this->assertFalse($this->policy->delete($this->admin, $this->purchaser));
    }

    /** @test */
    public function customer_cannot_delete_users()
    {
        $this->assertFalse($this->policy->delete($this->customer, $this->customer));
        $this->assertFalse($this->policy->delete($this->customer, $this->admin));
    }

    /** @test */
    public function users_cannot_delete_themselves()
    {
        $this->assertFalse($this->policy->delete($this->superAdmin, $this->superAdmin));
        $this->assertFalse($this->policy->delete($this->admin, $this->admin));
        $this->assertFalse($this->policy->delete($this->customer, $this->customer));
    }

    /** @test */
    public function superadmin_can_restore_any_user()
    {
        $this->assertTrue($this->policy->restore($this->superAdmin, $this->admin));
        $this->assertTrue($this->policy->restore($this->superAdmin, $this->customer));
    }

    /** @test */
    public function admin_can_restore_customers_only()
    {
        $this->assertTrue($this->policy->restore($this->admin, $this->customer));
        $this->assertFalse($this->policy->restore($this->admin, $this->superAdmin));
        $this->assertFalse($this->policy->restore($this->admin, $this->purchaser));
    }

    /** @test */
    public function customer_cannot_restore_users()
    {
        $this->assertFalse($this->policy->restore($this->customer, $this->customer));
        $this->assertFalse($this->policy->restore($this->customer, $this->admin));
    }

    /** @test */
    public function superadmin_can_force_delete_any_user_except_themselves()
    {
        $this->assertTrue($this->policy->forceDelete($this->superAdmin, $this->admin));
        $this->assertTrue($this->policy->forceDelete($this->superAdmin, $this->customer));
        $this->assertFalse($this->policy->forceDelete($this->superAdmin, $this->superAdmin));
    }

    /** @test */
    public function admin_cannot_force_delete_users()
    {
        $this->assertFalse($this->policy->forceDelete($this->admin, $this->customer));
        $this->assertFalse($this->policy->forceDelete($this->admin, $this->admin));
    }

    /** @test */
    public function superadmin_can_change_any_role_except_own()
    {
        $this->assertTrue($this->policy->changeRole($this->superAdmin, $this->admin));
        $this->assertTrue($this->policy->changeRole($this->superAdmin, $this->customer));
        $this->assertFalse($this->policy->changeRole($this->superAdmin, $this->superAdmin));
    }

    /** @test */
    public function admin_can_change_customer_roles_only()
    {
        $this->assertTrue($this->policy->changeRole($this->admin, $this->customer));
        $this->assertFalse($this->policy->changeRole($this->admin, $this->admin));
        $this->assertFalse($this->policy->changeRole($this->admin, $this->superAdmin));
        $this->assertFalse($this->policy->changeRole($this->admin, $this->purchaser));
    }

    /** @test */
    public function customer_cannot_change_roles()
    {
        $this->assertFalse($this->policy->changeRole($this->customer, $this->customer));
        $this->assertFalse($this->policy->changeRole($this->customer, $this->admin));
    }

    /** @test */
    public function users_cannot_change_their_own_role()
    {
        $this->assertFalse($this->policy->changeRole($this->superAdmin, $this->superAdmin));
        $this->assertFalse($this->policy->changeRole($this->admin, $this->admin));
        $this->assertFalse($this->policy->changeRole($this->customer, $this->customer));
    }

    /** @test */
    public function superadmin_and_admin_can_manage_roles()
    {
        $this->assertTrue($this->policy->manageRoles($this->superAdmin));
        $this->assertTrue($this->policy->manageRoles($this->admin));
        $this->assertFalse($this->policy->manageRoles($this->customer));
        $this->assertFalse($this->policy->manageRoles($this->purchaser));
    }

    /** @test */
    public function superadmin_and_admin_can_view_statistics()
    {
        $this->assertTrue($this->policy->viewStatistics($this->superAdmin));
        $this->assertTrue($this->policy->viewStatistics($this->admin));
        $this->assertFalse($this->policy->viewStatistics($this->customer));
        $this->assertFalse($this->policy->viewStatistics($this->purchaser));
    }

    /** @test */
    public function superadmin_can_create_users_with_any_role()
    {
        $this->assertTrue($this->policy->createWithRole($this->superAdmin, 'superadmin'));
        $this->assertTrue($this->policy->createWithRole($this->superAdmin, 'admin'));
        $this->assertTrue($this->policy->createWithRole($this->superAdmin, 'customer'));
        $this->assertTrue($this->policy->createWithRole($this->superAdmin, 'purchaser'));
    }

    /** @test */
    public function admin_can_create_customers_only()
    {
        $this->assertTrue($this->policy->createWithRole($this->admin, 'customer'));
        $this->assertFalse($this->policy->createWithRole($this->admin, 'admin'));
        $this->assertFalse($this->policy->createWithRole($this->admin, 'superadmin'));
        $this->assertFalse($this->policy->createWithRole($this->admin, 'purchaser'));
    }

    /** @test */
    public function customer_cannot_create_users_with_any_role()
    {
        $this->assertFalse($this->policy->createWithRole($this->customer, 'customer'));
        $this->assertFalse($this->policy->createWithRole($this->customer, 'admin'));
        $this->assertFalse($this->policy->createWithRole($this->customer, 'superadmin'));
    }
}