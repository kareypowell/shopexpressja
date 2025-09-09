<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Manifest;
use App\Models\Role;
use App\Policies\ManifestPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ManifestPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected $policy;
    protected $adminUser;
    protected $superAdminUser;
    protected $customerUser;
    protected $openManifest;
    protected $closedManifest;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new ManifestPolicy();
        
        // Create roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $superAdminRole = Role::factory()->create(['name' => 'superadmin']);
        $customerRole = Role::factory()->create(['name' => 'customer']);
        
        // Create users with different roles
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->superAdminUser = User::factory()->create(['role_id' => $superAdminRole->id]);
        $this->customerUser = User::factory()->create(['role_id' => $customerRole->id]);
        
        // Create manifests for testing
        $this->openManifest = Manifest::factory()->create(['is_open' => true]);
        $this->closedManifest = Manifest::factory()->create(['is_open' => false]);
    }

    public function test_admin_can_view_any_manifests()
    {
        $this->assertTrue($this->policy->viewAny($this->adminUser));
    }

    public function test_superadmin_can_view_any_manifests()
    {
        $this->assertTrue($this->policy->viewAny($this->superAdminUser));
    }

    public function test_customer_cannot_view_any_manifests()
    {
        $this->assertFalse($this->policy->viewAny($this->customerUser));
    }

    public function test_admin_can_view_manifest()
    {
        $this->assertTrue($this->policy->view($this->adminUser, $this->openManifest));
        $this->assertTrue($this->policy->view($this->adminUser, $this->closedManifest));
    }

    public function test_superadmin_can_view_manifest()
    {
        $this->assertTrue($this->policy->view($this->superAdminUser, $this->openManifest));
        $this->assertTrue($this->policy->view($this->superAdminUser, $this->closedManifest));
    }

    public function test_customer_cannot_view_manifest()
    {
        $this->assertFalse($this->policy->view($this->customerUser, $this->openManifest));
        $this->assertFalse($this->policy->view($this->customerUser, $this->closedManifest));
    }

    public function test_admin_can_create_manifest()
    {
        $this->assertTrue($this->policy->create($this->adminUser));
    }

    public function test_superadmin_can_create_manifest()
    {
        $this->assertTrue($this->policy->create($this->superAdminUser));
    }

    public function test_customer_cannot_create_manifest()
    {
        $this->assertFalse($this->policy->create($this->customerUser));
    }

    public function test_admin_can_update_manifest()
    {
        $this->assertTrue($this->policy->update($this->adminUser, $this->openManifest));
        $this->assertTrue($this->policy->update($this->adminUser, $this->closedManifest));
    }

    public function test_superadmin_can_update_manifest()
    {
        $this->assertTrue($this->policy->update($this->superAdminUser, $this->openManifest));
        $this->assertTrue($this->policy->update($this->superAdminUser, $this->closedManifest));
    }

    public function test_customer_cannot_update_manifest()
    {
        $this->assertFalse($this->policy->update($this->customerUser, $this->openManifest));
        $this->assertFalse($this->policy->update($this->customerUser, $this->closedManifest));
    }

    // Test the edit method - key requirement for manifest locking
    public function test_admin_can_edit_open_manifest()
    {
        $this->assertTrue($this->policy->edit($this->adminUser, $this->openManifest));
    }

    public function test_admin_cannot_edit_closed_manifest()
    {
        $this->assertFalse($this->policy->edit($this->adminUser, $this->closedManifest));
    }

    public function test_superadmin_can_edit_open_manifest()
    {
        $this->assertTrue($this->policy->edit($this->superAdminUser, $this->openManifest));
    }

    public function test_superadmin_cannot_edit_closed_manifest()
    {
        $this->assertFalse($this->policy->edit($this->superAdminUser, $this->closedManifest));
    }

    public function test_customer_cannot_edit_any_manifest()
    {
        $this->assertFalse($this->policy->edit($this->customerUser, $this->openManifest));
        $this->assertFalse($this->policy->edit($this->customerUser, $this->closedManifest));
    }

    // Test the unlock method - key requirement for manifest locking
    public function test_admin_can_unlock_closed_manifest()
    {
        $this->assertTrue($this->policy->unlock($this->adminUser, $this->closedManifest));
    }

    public function test_admin_cannot_unlock_open_manifest()
    {
        $this->assertFalse($this->policy->unlock($this->adminUser, $this->openManifest));
    }

    public function test_superadmin_can_unlock_closed_manifest()
    {
        $this->assertTrue($this->policy->unlock($this->superAdminUser, $this->closedManifest));
    }

    public function test_superadmin_cannot_unlock_open_manifest()
    {
        $this->assertFalse($this->policy->unlock($this->superAdminUser, $this->openManifest));
    }

    public function test_customer_cannot_unlock_any_manifest()
    {
        $this->assertFalse($this->policy->unlock($this->customerUser, $this->openManifest));
        $this->assertFalse($this->policy->unlock($this->customerUser, $this->closedManifest));
    }

    // Test the viewAudit method - key requirement for audit trail access
    public function test_admin_can_view_audit()
    {
        $this->assertTrue($this->policy->viewAudit($this->adminUser, $this->openManifest));
        $this->assertTrue($this->policy->viewAudit($this->adminUser, $this->closedManifest));
    }

    public function test_superadmin_can_view_audit()
    {
        $this->assertTrue($this->policy->viewAudit($this->superAdminUser, $this->openManifest));
        $this->assertTrue($this->policy->viewAudit($this->superAdminUser, $this->closedManifest));
    }

    public function test_customer_cannot_view_audit()
    {
        $this->assertFalse($this->policy->viewAudit($this->customerUser, $this->openManifest));
        $this->assertFalse($this->policy->viewAudit($this->customerUser, $this->closedManifest));
    }

    public function test_admin_can_delete_manifest()
    {
        $this->assertTrue($this->policy->delete($this->adminUser, $this->openManifest));
        $this->assertTrue($this->policy->delete($this->adminUser, $this->closedManifest));
    }

    public function test_superadmin_can_delete_manifest()
    {
        $this->assertTrue($this->policy->delete($this->superAdminUser, $this->openManifest));
        $this->assertTrue($this->policy->delete($this->superAdminUser, $this->closedManifest));
    }

    public function test_customer_cannot_delete_manifest()
    {
        $this->assertFalse($this->policy->delete($this->customerUser, $this->openManifest));
        $this->assertFalse($this->policy->delete($this->customerUser, $this->closedManifest));
    }
}