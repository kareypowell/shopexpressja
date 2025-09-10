<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Manifest;
use App\Models\ManifestAudit;
use App\Models\Role;
use App\Services\ManifestLockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Http\Livewire\Manifests\ManifestLockStatus;

class ManifestLockFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Manifest $openManifest;
    private Manifest $closedManifest;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin role and user
        $adminRole = Role::create([
            'name' => 'admin',
            'description' => 'Administrator role'
        ]);
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);

        // Create open and closed manifests
        $this->openManifest = Manifest::factory()->create(['is_open' => true]);
        $this->closedManifest = Manifest::factory()->create(['is_open' => false]);
    }

    /** @test */
    public function it_can_lock_an_open_manifest()
    {
        $this->actingAs($this->adminUser);

        $lockService = app(ManifestLockService::class);
        $result = $lockService->lockManifest(
            $this->openManifest,
            $this->adminUser,
            'Completed all package processing and ready to finalize'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('Manifest locked successfully.', $result['message']);

        // Verify manifest is now closed
        $this->openManifest->refresh();
        $this->assertFalse($this->openManifest->is_open);

        // Verify audit record was created
        $this->assertDatabaseHas('manifest_audits', [
            'manifest_id' => $this->openManifest->id,
            'user_id' => $this->adminUser->id,
            'action' => 'closed',
            'reason' => 'Completed all package processing and ready to finalize'
        ]);
    }

    /** @test */
    public function it_prevents_locking_already_closed_manifest()
    {
        $this->actingAs($this->adminUser);

        $lockService = app(ManifestLockService::class);
        $result = $lockService->lockManifest(
            $this->closedManifest,
            $this->adminUser,
            'Trying to lock already closed manifest'
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('Manifest is already closed.', $result['message']);
    }

    /** @test */
    public function it_validates_lock_reason_length()
    {
        $this->actingAs($this->adminUser);

        $lockService = app(ManifestLockService::class);
        
        // Test too short reason
        $result = $lockService->lockManifest(
            $this->openManifest,
            $this->adminUser,
            'Short'
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('Reason must be at least 10 characters long.', $result['message']);

        // Test empty reason
        $result = $lockService->lockManifest(
            $this->openManifest,
            $this->adminUser,
            ''
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('A reason is required to close the manifest.', $result['message']);
    }

    /** @test */
    public function it_shows_lock_button_for_open_manifest()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ManifestLockStatus::class, ['manifest' => $this->openManifest])
            ->assertSee('Lock Manifest')
            ->assertSee('Editing Enabled');
    }

    /** @test */
    public function it_shows_unlock_button_for_closed_manifest()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ManifestLockStatus::class, ['manifest' => $this->closedManifest])
            ->assertSee('Unlock Manifest')
            ->assertSee('Locked - View Only');
    }

    /** @test */
    public function it_can_show_lock_modal()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ManifestLockStatus::class, ['manifest' => $this->openManifest])
            ->call('showLockModal')
            ->assertSet('showLockModal', true);
    }

    /** @test */
    public function it_can_lock_manifest_through_component()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ManifestLockStatus::class, ['manifest' => $this->openManifest])
            ->set('lockReason', 'All packages processed and manifest ready for finalization')
            ->call('lockManifest')
            ->assertSet('showLockModal', false)
            ->assertEmitted('manifestLocked');

        // Verify manifest is now closed
        $this->openManifest->refresh();
        $this->assertFalse($this->openManifest->is_open);
    }

    /** @test */
    public function it_validates_lock_reason_in_component()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ManifestLockStatus::class, ['manifest' => $this->openManifest])
            ->set('lockReason', 'Short')
            ->call('lockManifest')
            ->assertHasErrors(['lockReason']);
    }

    /** @test */
    public function it_can_cancel_lock_modal()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ManifestLockStatus::class, ['manifest' => $this->openManifest])
            ->call('showLockModal')
            ->assertSet('showLockModal', true)
            ->call('cancelLock')
            ->assertSet('showLockModal', false)
            ->assertSet('lockReason', '');
    }

    /** @test */
    public function it_creates_audit_trail_for_lock_operations()
    {
        $this->actingAs($this->adminUser);

        $lockService = app(ManifestLockService::class);
        $lockService->lockManifest(
            $this->openManifest,
            $this->adminUser,
            'Manifest processing completed successfully'
        );

        $audit = ManifestAudit::where('manifest_id', $this->openManifest->id)
            ->where('action', 'closed')
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals($this->adminUser->id, $audit->user_id);
        $this->assertEquals('Manifest processing completed successfully', $audit->reason);
        $this->assertNotNull($audit->performed_at);
    }

    /** @test */
    public function it_prevents_unauthorized_users_from_locking()
    {
        // Create a customer user without edit permissions
        $customerRole = Role::create([
            'name' => 'customer',
            'description' => 'Customer role'
        ]);
        $customerUser = User::factory()->create(['role_id' => $customerRole->id]);

        $this->actingAs($customerUser);

        $lockService = app(ManifestLockService::class);
        $result = $lockService->lockManifest(
            $this->openManifest,
            $customerUser,
            'Trying to lock without permission'
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('You do not have permission to close this manifest.', $result['message']);
    }
}