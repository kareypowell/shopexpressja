<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Policies\CustomerPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected CustomerPolicy $policy;
    protected User $superadmin;
    protected User $admin;
    protected User $customer;
    protected User $anotherCustomer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new CustomerPolicy();

        // Create roles
        $superadminRole = Role::factory()->create(['name' => 'superadmin']);
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $customerRole = Role::factory()->create(['name' => 'customer']);

        // Create users with different roles
        $this->superadmin = User::factory()->create(['role_id' => $superadminRole->id]);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->customer = User::factory()->create(['role_id' => $customerRole->id]);
        $this->anotherCustomer = User::factory()->create(['role_id' => $customerRole->id]);
    }

    /** @test */
    public function superadmin_can_view_any_customers()
    {
        $this->assertTrue($this->policy->viewAny($this->superadmin));
    }

    /** @test */
    public function admin_can_view_any_customers()
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    /** @test */
    public function customer_cannot_view_any_customers()
    {
        $this->assertFalse($this->policy->viewAny($this->customer));
    }

    /** @test */
    public function superadmin_can_view_any_customer()
    {
        $this->assertTrue($this->policy->view($this->superadmin, $this->customer));
    }

    /** @test */
    public function admin_can_view_any_customer()
    {
        $this->assertTrue($this->policy->view($this->admin, $this->customer));
    }

    /** @test */
    public function customer_can_view_own_profile()
    {
        $this->assertTrue($this->policy->view($this->customer, $this->customer));
    }

    /** @test */
    public function customer_cannot_view_other_customer_profile()
    {
        $this->assertFalse($this->policy->view($this->customer, $this->anotherCustomer));
    }

    /** @test */
    public function cannot_view_non_customer_user()
    {
        $this->assertFalse($this->policy->view($this->superadmin, $this->admin));
        $this->assertFalse($this->policy->view($this->admin, $this->superadmin));
    }

    /** @test */
    public function superadmin_can_create_customers()
    {
        $this->assertTrue($this->policy->create($this->superadmin));
    }

    /** @test */
    public function admin_can_create_customers()
    {
        $this->assertTrue($this->policy->create($this->admin));
    }

    /** @test */
    public function customer_cannot_create_customers()
    {
        $this->assertFalse($this->policy->create($this->customer));
    }

    /** @test */
    public function superadmin_can_update_any_customer()
    {
        $this->assertTrue($this->policy->update($this->superadmin, $this->customer));
    }

    /** @test */
    public function admin_can_update_any_customer()
    {
        $this->assertTrue($this->policy->update($this->admin, $this->customer));
    }

    /** @test */
    public function customer_can_update_own_profile()
    {
        $this->assertTrue($this->policy->update($this->customer, $this->customer));
    }

    /** @test */
    public function customer_cannot_update_other_customer_profile()
    {
        $this->assertFalse($this->policy->update($this->customer, $this->anotherCustomer));
    }

    /** @test */
    public function cannot_update_non_customer_user()
    {
        $this->assertFalse($this->policy->update($this->superadmin, $this->admin));
        $this->assertFalse($this->policy->update($this->admin, $this->superadmin));
    }

    /** @test */
    public function superadmin_can_delete_customers()
    {
        $this->assertTrue($this->policy->delete($this->superadmin, $this->customer));
    }

    /** @test */
    public function admin_can_delete_customers()
    {
        $this->assertTrue($this->policy->delete($this->admin, $this->customer));
    }

    /** @test */
    public function customer_cannot_delete_customers()
    {
        $this->assertFalse($this->policy->delete($this->customer, $this->anotherCustomer));
    }

    /** @test */
    public function users_cannot_delete_themselves()
    {
        $this->assertFalse($this->policy->delete($this->customer, $this->customer));
        $this->assertFalse($this->policy->delete($this->admin, $this->admin));
        $this->assertFalse($this->policy->delete($this->superadmin, $this->superadmin));
    }

    /** @test */
    public function cannot_delete_non_customer_user()
    {
        $this->assertFalse($this->policy->delete($this->superadmin, $this->admin));
    }

    /** @test */
    public function superadmin_cannot_be_deleted()
    {
        $this->assertFalse($this->policy->delete($this->admin, $this->superadmin));
    }

    /** @test */
    public function superadmin_can_restore_customers()
    {
        $this->assertTrue($this->policy->restore($this->superadmin, $this->customer));
    }

    /** @test */
    public function admin_can_restore_customers()
    {
        $this->assertTrue($this->policy->restore($this->admin, $this->customer));
    }

    /** @test */
    public function customer_cannot_restore_customers()
    {
        $this->assertFalse($this->policy->restore($this->customer, $this->anotherCustomer));
    }

    /** @test */
    public function cannot_restore_non_customer_user()
    {
        $this->assertFalse($this->policy->restore($this->superadmin, $this->admin));
    }

    /** @test */
    public function superadmin_can_view_customer_financials()
    {
        $this->assertTrue($this->policy->viewFinancials($this->superadmin, $this->customer));
    }

    /** @test */
    public function admin_can_view_customer_financials()
    {
        $this->assertTrue($this->policy->viewFinancials($this->admin, $this->customer));
    }

    /** @test */
    public function customer_can_view_own_financials()
    {
        $this->assertTrue($this->policy->viewFinancials($this->customer, $this->customer));
    }

    /** @test */
    public function customer_cannot_view_other_customer_financials()
    {
        $this->assertFalse($this->policy->viewFinancials($this->customer, $this->anotherCustomer));
    }

    /** @test */
    public function cannot_view_financials_of_non_customer()
    {
        $this->assertFalse($this->policy->viewFinancials($this->superadmin, $this->admin));
    }

    /** @test */
    public function superadmin_can_view_customer_packages()
    {
        $this->assertTrue($this->policy->viewPackages($this->superadmin, $this->customer));
    }

    /** @test */
    public function admin_can_view_customer_packages()
    {
        $this->assertTrue($this->policy->viewPackages($this->admin, $this->customer));
    }

    /** @test */
    public function customer_can_view_own_packages()
    {
        $this->assertTrue($this->policy->viewPackages($this->customer, $this->customer));
    }

    /** @test */
    public function customer_cannot_view_other_customer_packages()
    {
        $this->assertFalse($this->policy->viewPackages($this->customer, $this->anotherCustomer));
    }

    /** @test */
    public function cannot_view_packages_of_non_customer()
    {
        $this->assertFalse($this->policy->viewPackages($this->superadmin, $this->admin));
    }

    /** @test */
    public function superadmin_can_perform_bulk_operations()
    {
        $this->assertTrue($this->policy->bulkOperations($this->superadmin));
    }

    /** @test */
    public function admin_can_perform_bulk_operations()
    {
        $this->assertTrue($this->policy->bulkOperations($this->admin));
    }

    /** @test */
    public function customer_cannot_perform_bulk_operations()
    {
        $this->assertFalse($this->policy->bulkOperations($this->customer));
    }

    /** @test */
    public function superadmin_can_export_customer_data()
    {
        $this->assertTrue($this->policy->export($this->superadmin));
    }

    /** @test */
    public function admin_can_export_customer_data()
    {
        $this->assertTrue($this->policy->export($this->admin));
    }

    /** @test */
    public function customer_cannot_export_customer_data()
    {
        $this->assertFalse($this->policy->export($this->customer));
    }

    /** @test */
    public function superadmin_can_send_emails_to_customers()
    {
        $this->assertTrue($this->policy->sendEmail($this->superadmin));
    }

    /** @test */
    public function admin_can_send_emails_to_customers()
    {
        $this->assertTrue($this->policy->sendEmail($this->admin));
    }

    /** @test */
    public function customer_cannot_send_emails_to_customers()
    {
        $this->assertFalse($this->policy->sendEmail($this->customer));
    }

    /** @test */
    public function superadmin_can_view_deleted_customers()
    {
        $this->assertTrue($this->policy->viewDeleted($this->superadmin));
    }

    /** @test */
    public function admin_can_view_deleted_customers()
    {
        $this->assertTrue($this->policy->viewDeleted($this->admin));
    }

    /** @test */
    public function customer_cannot_view_deleted_customers()
    {
        $this->assertFalse($this->policy->viewDeleted($this->customer));
    }

    /** @test */
    public function only_superadmin_can_force_delete_customers()
    {
        $this->assertTrue($this->policy->forceDelete($this->superadmin, $this->customer));
        $this->assertFalse($this->policy->forceDelete($this->admin, $this->customer));
        $this->assertFalse($this->policy->forceDelete($this->customer, $this->anotherCustomer));
    }

    /** @test */
    public function users_cannot_force_delete_themselves()
    {
        $this->assertFalse($this->policy->forceDelete($this->superadmin, $this->superadmin));
        $this->assertFalse($this->policy->forceDelete($this->customer, $this->customer));
    }

    /** @test */
    public function cannot_force_delete_non_customer_user()
    {
        $this->assertFalse($this->policy->forceDelete($this->superadmin, $this->admin));
    }
}