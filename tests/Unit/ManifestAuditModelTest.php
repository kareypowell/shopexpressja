<?php

namespace Tests\Unit;

use App\Models\Manifest;
use App\Models\ManifestAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManifestAuditModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_audit_belongs_to_manifest()
    {
        $manifest = Manifest::factory()->create();
        $user = User::factory()->create();
        
        $audit = ManifestAudit::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Manifest::class, $audit->manifest);
        $this->assertEquals($manifest->id, $audit->manifest->id);
    }

    public function test_manifest_audit_belongs_to_user()
    {
        $manifest = Manifest::factory()->create();
        $user = User::factory()->create();
        
        $audit = ManifestAudit::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $audit->user);
        $this->assertEquals($user->id, $audit->user->id);
    }

    public function test_manifest_has_many_audits()
    {
        $manifest = Manifest::factory()->create();
        $user = User::factory()->create();
        
        ManifestAudit::factory()->count(3)->create([
            'manifest_id' => $manifest->id,
            'user_id' => $user->id,
        ]);

        $this->assertCount(3, $manifest->audits);
        $this->assertInstanceOf(ManifestAudit::class, $manifest->audits->first());
    }

    public function test_action_label_attribute()
    {
        $audit = ManifestAudit::factory()->make(['action' => 'closed']);
        $this->assertEquals('Closed', $audit->action_label);

        $audit = ManifestAudit::factory()->make(['action' => 'unlocked']);
        $this->assertEquals('Unlocked', $audit->action_label);

        $audit = ManifestAudit::factory()->make(['action' => 'auto_complete']);
        $this->assertEquals('Auto-closed (All Delivered)', $audit->action_label);

        $audit = ManifestAudit::factory()->make(['action' => 'custom_action']);
        $this->assertEquals('Custom_action', $audit->action_label);
    }

    public function test_for_manifest_scope()
    {
        $manifest1 = Manifest::factory()->create();
        $manifest2 = Manifest::factory()->create();
        $user = User::factory()->create();

        ManifestAudit::factory()->count(2)->create(['manifest_id' => $manifest1->id, 'user_id' => $user->id]);
        ManifestAudit::factory()->count(3)->create(['manifest_id' => $manifest2->id, 'user_id' => $user->id]);

        $manifest1Audits = ManifestAudit::forManifest($manifest1->id)->get();
        $manifest2Audits = ManifestAudit::forManifest($manifest2->id)->get();

        $this->assertCount(2, $manifest1Audits);
        $this->assertCount(3, $manifest2Audits);
    }

    public function test_by_user_scope()
    {
        $manifest = Manifest::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        ManifestAudit::factory()->count(2)->create(['manifest_id' => $manifest->id, 'user_id' => $user1->id]);
        ManifestAudit::factory()->count(3)->create(['manifest_id' => $manifest->id, 'user_id' => $user2->id]);

        $user1Audits = ManifestAudit::byUser($user1->id)->get();
        $user2Audits = ManifestAudit::byUser($user2->id)->get();

        $this->assertCount(2, $user1Audits);
        $this->assertCount(3, $user2Audits);
    }

    public function test_by_action_scope()
    {
        $manifest = Manifest::factory()->create();
        $user = User::factory()->create();

        ManifestAudit::factory()->count(2)->closed()->create(['manifest_id' => $manifest->id, 'user_id' => $user->id]);
        ManifestAudit::factory()->count(3)->unlocked()->create(['manifest_id' => $manifest->id, 'user_id' => $user->id]);

        $closedAudits = ManifestAudit::byAction('closed')->get();
        $unlockedAudits = ManifestAudit::byAction('unlocked')->get();

        $this->assertCount(2, $closedAudits);
        $this->assertCount(3, $unlockedAudits);
    }

    public function test_log_action_static_method()
    {
        $manifest = Manifest::factory()->create();
        $user = User::factory()->create();

        $audit = ManifestAudit::logAction(
            $manifest->id,
            $user->id,
            'unlocked',
            'Need to update package information'
        );

        $this->assertInstanceOf(ManifestAudit::class, $audit);
        $this->assertEquals($manifest->id, $audit->manifest_id);
        $this->assertEquals($user->id, $audit->user_id);
        $this->assertEquals('unlocked', $audit->action);
        $this->assertEquals('Need to update package information', $audit->reason);
        $this->assertNotNull($audit->performed_at);
    }

    public function test_get_manifest_audit_trail()
    {
        $manifest = Manifest::factory()->create();
        $user = User::factory()->create();

        ManifestAudit::factory()->count(3)->create([
            'manifest_id' => $manifest->id,
            'user_id' => $user->id,
        ]);

        $auditTrail = ManifestAudit::getManifestAuditTrail($manifest->id);

        $this->assertCount(3, $auditTrail);
        $this->assertTrue($auditTrail->first()->relationLoaded('user'));
    }

    public function test_get_manifest_action_summary()
    {
        $manifest = Manifest::factory()->create();
        $user = User::factory()->create();

        ManifestAudit::factory()->count(2)->closed()->create(['manifest_id' => $manifest->id, 'user_id' => $user->id]);
        ManifestAudit::factory()->count(1)->unlocked()->create(['manifest_id' => $manifest->id, 'user_id' => $user->id]);

        $summary = ManifestAudit::getManifestActionSummary($manifest->id);

        $this->assertEquals(3, $summary['total_actions']);
        $this->assertEquals(2, $summary['actions_by_type']['closed']);
        $this->assertEquals(1, $summary['actions_by_type']['unlocked']);
        $this->assertEquals(1, $summary['unique_users']);
        $this->assertInstanceOf(ManifestAudit::class, $summary['last_action']);
    }

    public function test_manifest_status_methods()
    {
        $openManifest = Manifest::factory()->create(['is_open' => true]);
        $closedManifest = Manifest::factory()->create(['is_open' => false]);

        $this->assertEquals('Open', $openManifest->status_label);
        $this->assertEquals('Closed', $closedManifest->status_label);

        $this->assertEquals('bg-green-100 text-green-800', $openManifest->status_badge_class);
        $this->assertEquals('bg-gray-100 text-gray-800', $closedManifest->status_badge_class);

        $this->assertTrue($openManifest->canBeEdited());
        $this->assertFalse($closedManifest->canBeEdited());
    }
}
