<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Manifest;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

class ManifestPolicyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $superAdminUser;
    protected $customerUser;
    protected $openManifest;
    protected $closedManifest;

    protected function setUp(): void
    {
        parent::setUp();
        
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

    public function test_gate_allows_admin_to_edit_open_manifest()
    {
        $this->actingAs($this->adminUser);
        
        $this->assertTrue(Gate::allows('edit', $this->openManifest));
    }

    public function test_gate_denies_admin_editing_closed_manifest()
    {
        $this->actingAs($this->adminUser);
        
        $this->assertFalse(Gate::allows('edit', $this->closedManifest));
    }

    public function test_gate_allows_superadmin_to_edit_open_manifest()
    {
        $this->actingAs($this->superAdminUser);
        
        $this->assertTrue(Gate::allows('edit', $this->openManifest));
    }

    public function test_gate_denies_superadmin_editing_closed_manifest()
    {
        $this->actingAs($this->superAdminUser);
        
        $this->assertFalse(Gate::allows('edit', $this->closedManifest));
    }

    public function test_gate_denies_customer_editing_any_manifest()
    {
        $this->actingAs($this->customerUser);
        
        $this->assertFalse(Gate::allows('edit', $this->openManifest));
        $this->assertFalse(Gate::allows('edit', $this->closedManifest));
    }

    public function test_gate_allows_admin_to_unlock_closed_manifest()
    {
        $this->actingAs($this->adminUser);
        
        $this->assertTrue(Gate::allows('unlock', $this->closedManifest));
    }

    public function test_gate_denies_admin_unlocking_open_manifest()
    {
        $this->actingAs($this->adminUser);
        
        $this->assertFalse(Gate::allows('unlock', $this->openManifest));
    }

    public function test_gate_allows_superadmin_to_unlock_closed_manifest()
    {
        $this->actingAs($this->superAdminUser);
        
        $this->assertTrue(Gate::allows('unlock', $this->closedManifest));
    }

    public function test_gate_denies_superadmin_unlocking_open_manifest()
    {
        $this->actingAs($this->superAdminUser);
        
        $this->assertFalse(Gate::allows('unlock', $this->openManifest));
    }

    public function test_gate_denies_customer_unlocking_any_manifest()
    {
        $this->actingAs($this->customerUser);
        
        $this->assertFalse(Gate::allows('unlock', $this->openManifest));
        $this->assertFalse(Gate::allows('unlock', $this->closedManifest));
    }

    public function test_gate_allows_admin_to_view_audit()
    {
        $this->actingAs($this->adminUser);
        
        $this->assertTrue(Gate::allows('viewAudit', $this->openManifest));
        $this->assertTrue(Gate::allows('viewAudit', $this->closedManifest));
    }

    public function test_gate_allows_superadmin_to_view_audit()
    {
        $this->actingAs($this->superAdminUser);
        
        $this->assertTrue(Gate::allows('viewAudit', $this->openManifest));
        $this->assertTrue(Gate::allows('viewAudit', $this->closedManifest));
    }

    public function test_gate_denies_customer_viewing_audit()
    {
        $this->actingAs($this->customerUser);
        
        $this->assertFalse(Gate::allows('viewAudit', $this->openManifest));
        $this->assertFalse(Gate::allows('viewAudit', $this->closedManifest));
    }

    public function test_policy_integration_with_manifest_state_changes()
    {
        $this->actingAs($this->adminUser);
        
        // Initially open manifest should allow editing
        $this->assertTrue(Gate::allows('edit', $this->openManifest));
        $this->assertFalse(Gate::allows('unlock', $this->openManifest));
        
        // Close the manifest
        $this->openManifest->update(['is_open' => false]);
        $this->openManifest->refresh();
        
        // Now it should not allow editing but should allow unlocking
        $this->assertFalse(Gate::allows('edit', $this->openManifest));
        $this->assertTrue(Gate::allows('unlock', $this->openManifest));
        
        // Open it again
        $this->openManifest->update(['is_open' => true]);
        $this->openManifest->refresh();
        
        // Should allow editing again but not unlocking
        $this->assertTrue(Gate::allows('edit', $this->openManifest));
        $this->assertFalse(Gate::allows('unlock', $this->openManifest));
    }

    public function test_policy_respects_user_role_hierarchy()
    {
        // Test with closed manifest that requires unlock permission
        
        // SuperAdmin should have unlock permission
        $this->actingAs($this->superAdminUser);
        $this->assertTrue(Gate::allows('unlock', $this->closedManifest));
        $this->assertTrue(Gate::allows('viewAudit', $this->closedManifest));
        
        // Admin should have unlock permission
        $this->actingAs($this->adminUser);
        $this->assertTrue(Gate::allows('unlock', $this->closedManifest));
        $this->assertTrue(Gate::allows('viewAudit', $this->closedManifest));
        
        // Customer should not have any permissions
        $this->actingAs($this->customerUser);
        $this->assertFalse(Gate::allows('unlock', $this->closedManifest));
        $this->assertFalse(Gate::allows('viewAudit', $this->closedManifest));
        $this->assertFalse(Gate::allows('edit', $this->openManifest));
    }
}