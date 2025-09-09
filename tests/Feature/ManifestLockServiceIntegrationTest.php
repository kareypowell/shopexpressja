<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ManifestLockService;
use App\Models\Manifest;
use App\Models\ManifestAudit;
use App\Models\Package;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ManifestLockServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected ManifestLockService $service;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(ManifestLockService::class);
        
        // Create admin role and user
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
    }

    public function test_complete_manifest_locking_workflow()
    {
        // Create an open manifest with packages
        $manifest = Manifest::factory()->create(['is_open' => true]);
        
        // Create packages - some delivered, some not
        $package1 = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'status' => 'delivered'
        ]);
        $package2 = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'status' => 'processing'
        ]);

        // Should not auto-close because not all packages are delivered
        $this->assertFalse($this->service->autoCloseIfComplete($manifest));
        $manifest->refresh();
        $this->assertTrue($manifest->is_open);

        // Update second package to delivered
        $package2->update(['status' => 'delivered']);

        // Should auto-close now
        $this->assertTrue($this->service->autoCloseIfComplete($manifest));
        $manifest->refresh();
        $this->assertFalse($manifest->is_open);

        // Verify audit log was created
        $this->assertDatabaseHas('manifest_audits', [
            'manifest_id' => $manifest->id,
            'action' => 'auto_complete'
        ]);

        // Now test unlocking
        $this->actingAs($this->adminUser);
        $reason = 'Need to update package information after customer complaint';
        
        $result = $this->service->unlockManifest($manifest, $this->adminUser, $reason);
        
        $this->assertTrue($result['success']);
        $manifest->refresh();
        $this->assertTrue($manifest->is_open);

        // Verify unlock audit log
        $this->assertDatabaseHas('manifest_audits', [
            'manifest_id' => $manifest->id,
            'user_id' => $this->adminUser->id,
            'action' => 'unlocked',
            'reason' => $reason
        ]);

        // Verify we have both audit records
        $auditCount = ManifestAudit::where('manifest_id', $manifest->id)->count();
        $this->assertEquals(2, $auditCount);
    }

    public function test_manifest_lock_status_information()
    {
        $manifest = Manifest::factory()->create(['is_open' => true]);
        
        // Create packages with mixed statuses
        Package::factory()->count(2)->create([
            'manifest_id' => $manifest->id,
            'status' => 'delivered'
        ]);
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'status' => 'processing'
        ]);

        $status = $this->service->getManifestLockStatus($manifest);

        $this->assertTrue($status['is_open']);
        $this->assertEquals('Open', $status['status_label']);
        $this->assertTrue($status['can_be_edited']);
        $this->assertFalse($status['all_packages_delivered']);
        $this->assertEquals(3, $status['package_count']);
        $this->assertEquals(2, $status['delivered_package_count']);
    }

    public function test_authorization_integration()
    {
        $manifest = Manifest::factory()->create(['is_open' => true]);
        
        // Test canEdit with authorization
        $this->actingAs($this->adminUser);
        $this->assertTrue($this->service->canEdit($manifest, $this->adminUser));

        // Close manifest and test unlock authorization
        $manifest->update(['is_open' => false]);
        $this->assertTrue($this->adminUser->can('unlock', $manifest));
        
        // Test unlock with proper authorization
        $result = $this->service->unlockManifest($manifest, $this->adminUser, 'Valid reason for testing');
        $this->assertTrue($result['success']);
    }
}