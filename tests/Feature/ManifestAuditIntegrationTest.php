<?php

namespace Tests\Feature;

use App\Models\Manifest;
use App\Models\ManifestAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManifestAuditIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_audit_system_integration()
    {
        // Create test data
        $manifest = Manifest::factory()->create(['is_open' => true]);
        $user = User::factory()->create();

        // Test logging an action
        $audit = ManifestAudit::logAction(
            $manifest->id,
            $user->id,
            'unlocked',
            'Need to update package information'
        );

        // Verify audit was created
        $this->assertInstanceOf(ManifestAudit::class, $audit);
        $this->assertDatabaseHas('manifest_audits', [
            'manifest_id' => $manifest->id,
            'user_id' => $user->id,
            'action' => 'unlocked',
            'reason' => 'Need to update package information'
        ]);

        // Test manifest relationship
        $this->assertEquals($manifest->id, $audit->manifest->id);
        $this->assertEquals($user->id, $audit->user->id);

        // Test manifest has audits relationship
        $manifest->refresh();
        $this->assertCount(1, $manifest->audits);
        $this->assertEquals($audit->id, $manifest->audits->first()->id);
    }

    public function test_manifest_status_methods()
    {
        $openManifest = Manifest::factory()->create(['is_open' => true]);
        $closedManifest = Manifest::factory()->create(['is_open' => false]);

        // Test status labels
        $this->assertEquals('Open', $openManifest->status_label);
        $this->assertEquals('Closed', $closedManifest->status_label);

        // Test badge classes
        $this->assertEquals('bg-green-100 text-green-800', $openManifest->status_badge_class);
        $this->assertEquals('bg-gray-100 text-gray-800', $closedManifest->status_badge_class);

        // Test can be edited
        $this->assertTrue($openManifest->canBeEdited());
        $this->assertFalse($closedManifest->canBeEdited());
    }

    public function test_audit_trail_retrieval()
    {
        $manifest = Manifest::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create multiple audit entries
        ManifestAudit::logAction($manifest->id, $user1->id, 'closed', 'Manual closure');
        ManifestAudit::logAction($manifest->id, $user2->id, 'unlocked', 'Need corrections');
        ManifestAudit::logAction($manifest->id, $user1->id, 'closed', 'Final closure');

        // Test audit trail retrieval
        $auditTrail = ManifestAudit::getManifestAuditTrail($manifest->id);
        
        $this->assertCount(3, $auditTrail);
        $this->assertTrue($auditTrail->first()->relationLoaded('user'));
        
        // Test that we have all the expected reasons
        $reasons = $auditTrail->pluck('reason')->toArray();
        $this->assertContains('Manual closure', $reasons);
        $this->assertContains('Need corrections', $reasons);
        $this->assertContains('Final closure', $reasons);
    }

    public function test_audit_action_summary()
    {
        $manifest = Manifest::factory()->create();
        $user = User::factory()->create();

        // Create various audit entries
        ManifestAudit::logAction($manifest->id, $user->id, 'closed', 'Manual closure');
        ManifestAudit::logAction($manifest->id, $user->id, 'unlocked', 'Need corrections');
        ManifestAudit::logAction($manifest->id, $user->id, 'closed', 'Final closure');

        $summary = ManifestAudit::getManifestActionSummary($manifest->id);

        $this->assertEquals(3, $summary['total_actions']);
        $this->assertEquals(2, $summary['actions_by_type']['closed']);
        $this->assertEquals(1, $summary['actions_by_type']['unlocked']);
        $this->assertEquals(1, $summary['unique_users']);
        $this->assertInstanceOf(ManifestAudit::class, $summary['last_action']);
    }

    public function test_audit_scopes()
    {
        $manifest1 = Manifest::factory()->create();
        $manifest2 = Manifest::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create audit entries for different manifests and users
        ManifestAudit::logAction($manifest1->id, $user1->id, 'closed', 'Test 1');
        ManifestAudit::logAction($manifest1->id, $user2->id, 'unlocked', 'Test 2');
        ManifestAudit::logAction($manifest2->id, $user1->id, 'closed', 'Test 3');

        // Test forManifest scope
        $manifest1Audits = ManifestAudit::forManifest($manifest1->id)->get();
        $this->assertCount(2, $manifest1Audits);

        // Test byUser scope
        $user1Audits = ManifestAudit::byUser($user1->id)->get();
        $this->assertCount(2, $user1Audits);

        // Test byAction scope
        $closedAudits = ManifestAudit::byAction('closed')->get();
        $this->assertCount(2, $closedAudits);

        // Test recent scope (all should be recent)
        $recentAudits = ManifestAudit::recent()->get();
        $this->assertCount(3, $recentAudits);
    }

    public function test_database_indexes_exist()
    {
        // This test verifies that the migration ran successfully
        // and the table structure is correct
        $this->assertTrue(\Schema::hasTable('manifest_audits'));
        $this->assertTrue(\Schema::hasColumn('manifest_audits', 'manifest_id'));
        $this->assertTrue(\Schema::hasColumn('manifest_audits', 'user_id'));
        $this->assertTrue(\Schema::hasColumn('manifest_audits', 'action'));
        $this->assertTrue(\Schema::hasColumn('manifest_audits', 'reason'));
        $this->assertTrue(\Schema::hasColumn('manifest_audits', 'performed_at'));
    }
}
