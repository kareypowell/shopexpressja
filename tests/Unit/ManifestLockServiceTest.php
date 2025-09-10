<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ManifestLockService;
use App\Models\Manifest;
use App\Models\ManifestAudit;
use App\Models\Package;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class ManifestLockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ManifestLockService $service;
    protected User $adminUser;
    protected User $customerUser;
    protected Manifest $manifest;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(ManifestLockService::class);
        
        // Create roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $customerRole = Role::factory()->create(['name' => 'customer']);
        
        // Create users (ensure we have a user with ID 1 for system operations)
        $this->adminUser = User::factory()->create(['id' => 1, 'role_id' => $adminRole->id]);
        $this->customerUser = User::factory()->create(['role_id' => $customerRole->id]);
        
        // Create manifest
        $this->manifest = Manifest::factory()->create(['is_open' => true]);
    }

    public function test_can_edit_returns_true_for_open_manifest_with_permission()
    {
        $this->actingAs($this->adminUser);
        
        $result = $this->service->canEdit($this->manifest, $this->adminUser);
        
        $this->assertTrue($result);
    }

    public function test_can_edit_returns_false_for_closed_manifest()
    {
        $this->manifest->update(['is_open' => false]);
        $this->actingAs($this->adminUser);
        
        $result = $this->service->canEdit($this->manifest, $this->adminUser);
        
        $this->assertFalse($result);
    }

    public function test_auto_close_if_complete_closes_manifest_when_all_packages_delivered()
    {
        // Create packages with delivered status
        Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'status' => 'delivered'
        ]);

        $result = $this->service->autoCloseIfComplete($this->manifest);

        $this->assertTrue($result);
        $this->manifest->refresh();
        $this->assertFalse($this->manifest->is_open);
        
        // Check audit log was created
        $this->assertDatabaseHas('manifest_audits', [
            'manifest_id' => $this->manifest->id,
            'action' => 'auto_complete'
        ]);
    }

    public function test_auto_close_if_complete_does_not_close_when_packages_not_all_delivered()
    {
        // Create packages with mixed statuses
        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'status' => 'delivered'
        ]);
        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'status' => 'processing'
        ]);

        $result = $this->service->autoCloseIfComplete($this->manifest);

        $this->assertFalse($result);
        $this->manifest->refresh();
        $this->assertTrue($this->manifest->is_open);
    }

    public function test_auto_close_if_complete_does_not_close_manifest_with_no_packages()
    {
        $result = $this->service->autoCloseIfComplete($this->manifest);

        $this->assertFalse($result);
        $this->manifest->refresh();
        $this->assertTrue($this->manifest->is_open);
    }

    public function test_auto_close_if_complete_returns_false_for_already_closed_manifest()
    {
        $this->manifest->update(['is_open' => false]);
        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'status' => 'delivered'
        ]);

        $result = $this->service->autoCloseIfComplete($this->manifest);

        $this->assertFalse($result);
    }

    public function test_unlock_manifest_successfully_unlocks_with_valid_reason()
    {
        $this->manifest->update(['is_open' => false]);
        $this->actingAs($this->adminUser);
        
        $reason = 'Need to update package information due to customer request';
        $result = $this->service->unlockManifest($this->manifest, $this->adminUser, $reason);

        $this->assertTrue($result['success']);
        $this->assertEquals('Manifest unlocked successfully.', $result['message']);
        
        $this->manifest->refresh();
        $this->assertTrue($this->manifest->is_open);
        
        // Check audit log was created
        $this->assertDatabaseHas('manifest_audits', [
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->adminUser->id,
            'action' => 'unlocked',
            'reason' => $reason
        ]);
    }

    public function test_unlock_manifest_fails_with_empty_reason()
    {
        $this->manifest->update(['is_open' => false]);
        $this->actingAs($this->adminUser);
        
        $result = $this->service->unlockManifest($this->manifest, $this->adminUser, '');

        $this->assertFalse($result['success']);
        $this->assertEquals('A reason is required to unlock the manifest.', $result['message']);
        
        $this->manifest->refresh();
        $this->assertFalse($this->manifest->is_open);
    }

    public function test_unlock_manifest_fails_with_short_reason()
    {
        $this->manifest->update(['is_open' => false]);
        $this->actingAs($this->adminUser);
        
        $result = $this->service->unlockManifest($this->manifest, $this->adminUser, 'short');

        $this->assertFalse($result['success']);
        $this->assertEquals('Reason must be at least 10 characters long.', $result['message']);
        
        $this->manifest->refresh();
        $this->assertFalse($this->manifest->is_open);
    }

    public function test_unlock_manifest_fails_with_too_long_reason()
    {
        $this->manifest->update(['is_open' => false]);
        $this->actingAs($this->adminUser);
        
        $longReason = str_repeat('a', 501);
        $result = $this->service->unlockManifest($this->manifest, $this->adminUser, $longReason);

        $this->assertFalse($result['success']);
        $this->assertEquals('Reason cannot exceed 500 characters.', $result['message']);
        
        $this->manifest->refresh();
        $this->assertFalse($this->manifest->is_open);
    }

    public function test_unlock_manifest_fails_for_already_open_manifest()
    {
        $this->actingAs($this->adminUser);
        
        $reason = 'Valid reason for unlocking';
        $result = $this->service->unlockManifest($this->manifest, $this->adminUser, $reason);

        $this->assertFalse($result['success']);
        $this->assertEquals('Manifest is already open.', $result['message']);
    }

    public function test_get_manifest_lock_status_returns_correct_information()
    {
        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'status' => 'delivered'
        ]);
        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'status' => 'processing'
        ]);

        $status = $this->service->getManifestLockStatus($this->manifest);

        $this->assertTrue($status['is_open']);
        $this->assertEquals('Open', $status['status_label']);
        $this->assertTrue($status['can_be_edited']);
        $this->assertFalse($status['all_packages_delivered']);
        $this->assertEquals(3, $status['package_count']);
        $this->assertEquals(2, $status['delivered_package_count']);
    }

    public function test_is_eligible_for_auto_closure_returns_true_when_conditions_met()
    {
        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'status' => 'delivered'
        ]);

        $result = $this->service->isEligibleForAutoClosure($this->manifest);

        $this->assertTrue($result);
    }

    public function test_is_eligible_for_auto_closure_returns_false_when_manifest_closed()
    {
        $this->manifest->update(['is_open' => false]);
        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'status' => 'delivered'
        ]);

        $result = $this->service->isEligibleForAutoClosure($this->manifest);

        $this->assertFalse($result);
    }

    public function test_validate_unlock_reason_validates_correctly()
    {
        // Valid reason
        $result = $this->service->validateUnlockReason('This is a valid reason for unlocking');
        $this->assertTrue($result['valid']);

        // Empty reason
        $result = $this->service->validateUnlockReason('');
        $this->assertFalse($result['valid']);

        // Short reason
        $result = $this->service->validateUnlockReason('short');
        $this->assertFalse($result['valid']);

        // Long reason
        $result = $this->service->validateUnlockReason(str_repeat('a', 501));
        $this->assertFalse($result['valid']);
    }

    public function test_get_recent_audit_activity_returns_audit_records()
    {
        // Create some audit records
        ManifestAudit::factory()->count(5)->create([
            'manifest_id' => $this->manifest->id
        ]);

        $audits = $this->service->getRecentAuditActivity($this->manifest, 3);

        $this->assertCount(3, $audits);
        $this->assertTrue($audits->first()->user()->exists());
    }
}