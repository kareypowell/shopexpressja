<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\ConsolidatedPackage;
use App\Policies\ConsolidatedPackagePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsolidatedPackagePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected $policy;
    protected $adminUser;
    protected $customerUser;
    protected $otherCustomerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ConsolidatedPackagePolicy();

        // Create roles using the seeder structure
        $adminRole = Role::create(['id' => 2, 'name' => 'admin', 'description' => 'Administrator']);
        $customerRole = Role::create(['id' => 3, 'name' => 'customer', 'description' => 'Customer']);

        // Create users
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->customerUser = User::factory()->create(['role_id' => $customerRole->id]);
        $this->otherCustomerUser = User::factory()->create(['role_id' => $customerRole->id]);
    }

    /** @test */
    public function admin_can_view_any_consolidated_package()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id
        ]);

        $this->assertTrue($this->policy->view($this->adminUser, $consolidatedPackage));
    }

    /** @test */
    public function customer_can_view_their_own_consolidated_package()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id
        ]);

        $this->assertTrue($this->policy->view($this->customerUser, $consolidatedPackage));
    }

    /** @test */
    public function customer_cannot_view_other_customers_consolidated_package()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->otherCustomerUser->id
        ]);

        $this->assertFalse($this->policy->view($this->customerUser, $consolidatedPackage));
    }

    /** @test */
    public function only_admin_can_create_consolidated_packages()
    {
        $this->assertTrue($this->policy->create($this->adminUser));
        $this->assertFalse($this->policy->create($this->customerUser));
    }

    /** @test */
    public function only_admin_can_update_consolidated_packages()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id
        ]);

        $this->assertTrue($this->policy->update($this->adminUser, $consolidatedPackage));
        $this->assertFalse($this->policy->update($this->customerUser, $consolidatedPackage));
    }

    /** @test */
    public function only_admin_can_delete_consolidated_packages()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id
        ]);

        $this->assertTrue($this->policy->delete($this->adminUser, $consolidatedPackage));
        $this->assertFalse($this->policy->delete($this->customerUser, $consolidatedPackage));
    }

    /** @test */
    public function only_admin_can_unconsolidate_packages()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id
        ]);

        $this->assertTrue($this->policy->unconsolidate($this->adminUser, $consolidatedPackage));
        $this->assertFalse($this->policy->unconsolidate($this->customerUser, $consolidatedPackage));
    }

    /** @test */
    public function admin_can_view_any_consolidation_history()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id
        ]);

        $this->assertTrue($this->policy->viewHistory($this->adminUser, $consolidatedPackage));
    }

    /** @test */
    public function customer_can_view_their_own_consolidation_history()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id
        ]);

        $this->assertTrue($this->policy->viewHistory($this->customerUser, $consolidatedPackage));
    }

    /** @test */
    public function customer_cannot_view_other_customers_consolidation_history()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->otherCustomerUser->id
        ]);

        $this->assertFalse($this->policy->viewHistory($this->customerUser, $consolidatedPackage));
    }

    /** @test */
    public function admin_can_export_any_audit_trail()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id
        ]);

        $this->assertTrue($this->policy->exportAuditTrail($this->adminUser, $consolidatedPackage));
    }

    /** @test */
    public function customer_can_export_their_own_audit_trail()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id
        ]);

        $this->assertTrue($this->policy->exportAuditTrail($this->customerUser, $consolidatedPackage));
    }

    /** @test */
    public function customer_cannot_export_other_customers_audit_trail()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->otherCustomerUser->id
        ]);

        $this->assertFalse($this->policy->exportAuditTrail($this->customerUser, $consolidatedPackage));
    }

    /** @test */
    public function policy_handles_users_without_roles_gracefully()
    {
        // Create a role with no permissions and a user with that role
        $noPermissionRole = Role::create(['id' => 98, 'name' => 'no_permission', 'description' => 'No Permission Role']);
        $userWithoutRole = User::factory()->create(['role_id' => $noPermissionRole->id]);

        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id
        ]);

        // User without role should not have any permissions
        $this->assertFalse($this->policy->view($userWithoutRole, $consolidatedPackage));
        $this->assertFalse($this->policy->create($userWithoutRole));
        $this->assertFalse($this->policy->update($userWithoutRole, $consolidatedPackage));
        $this->assertFalse($this->policy->delete($userWithoutRole, $consolidatedPackage));
        $this->assertFalse($this->policy->unconsolidate($userWithoutRole, $consolidatedPackage));
        $this->assertFalse($this->policy->viewHistory($userWithoutRole, $consolidatedPackage));
        $this->assertFalse($this->policy->exportAuditTrail($userWithoutRole, $consolidatedPackage));
    }

    /** @test */
    public function policy_handles_invalid_role_ids_gracefully()
    {
        // Create another role with no permissions and a user with that role
        $invalidRole = Role::create(['id' => 97, 'name' => 'invalid_role', 'description' => 'Invalid Role']);
        $userWithInvalidRole = User::factory()->create(['role_id' => $invalidRole->id]);

        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id
        ]);

        // User with invalid role should not have any permissions
        $this->assertFalse($this->policy->view($userWithInvalidRole, $consolidatedPackage));
        $this->assertFalse($this->policy->create($userWithInvalidRole));
        $this->assertFalse($this->policy->update($userWithInvalidRole, $consolidatedPackage));
        $this->assertFalse($this->policy->delete($userWithInvalidRole, $consolidatedPackage));
        $this->assertFalse($this->policy->unconsolidate($userWithInvalidRole, $consolidatedPackage));
        $this->assertFalse($this->policy->viewHistory($userWithInvalidRole, $consolidatedPackage));
        $this->assertFalse($this->policy->exportAuditTrail($userWithInvalidRole, $consolidatedPackage));
    }
}