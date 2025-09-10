<?php

namespace Tests\Feature;

use App\Http\Livewire\Manifests\ManifestLockStatus;
use App\Models\Manifest;
use App\Models\ManifestAudit;
use App\Models\User;
use App\Models\Role;
use App\Services\ManifestLockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManifestLockStatusComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles manually
        Role::create(['id' => 1, 'name' => 'admin', 'description' => 'Administrator']);
        Role::create(['id' => 2, 'name' => 'superadmin', 'description' => 'Super Administrator']);
        Role::create(['id' => 3, 'name' => 'customer', 'description' => 'Customer']);
    }

    private function createAdminUser(): User
    {
        $adminRole = Role::where('name', 'admin')->first();
        return User::factory()->create(['role_id' => $adminRole->id]);
    }

    private function createCustomerUser(): User
    {
        $customerRole = Role::where('name', 'customer')->first();
        return User::factory()->create(['role_id' => $customerRole->id]);
    }

    /** @test */
    public function it_displays_open_manifest_status_correctly()
    {
        $user = $this->createAdminUser();
        $manifest = Manifest::factory()->create(['is_open' => true]);

        $this->actingAs($user);

        Livewire::test(ManifestLockStatus::class, ['manifest' => $manifest])
            ->assertSee('Open')
            ->assertSee('Editing Enabled')
            ->assertDontSee('Unlock Manifest');
    }

    /** @test */
    public function it_displays_closed_manifest_status_correctly()
    {
        $user = $this->createAdminUser();
        $manifest = Manifest::factory()->create(['is_open' => false]);

        $this->actingAs($user);

        Livewire::test(ManifestLockStatus::class, ['manifest' => $manifest])
            ->assertSee('Closed')
            ->assertSee('Locked - View Only')
            ->assertSee('Unlock Manifest');
    }

    /** @test */
    public function it_shows_unlock_modal_when_button_clicked()
    {
        $user = $this->createAdminUser();
        $manifest = Manifest::factory()->create(['is_open' => false]);

        $this->actingAs($user);

        Livewire::test(ManifestLockStatus::class, ['manifest' => $manifest])
            ->call('showUnlockModal')
            ->assertSet('showUnlockModal', true)
            ->assertSee('Reason for Unlocking');
    }

    /** @test */
    public function it_validates_unlock_reason_is_required()
    {
        $user = $this->createAdminUser();
        $manifest = Manifest::factory()->create(['is_open' => false]);

        $this->actingAs($user);

        Livewire::test(ManifestLockStatus::class, ['manifest' => $manifest])
            ->set('showUnlockModal', true)
            ->set('unlockReason', '')
            ->call('unlockManifest')
            ->assertHasErrors(['unlockReason' => 'required']);
    }

    /** @test */
    public function it_validates_unlock_reason_minimum_length()
    {
        $user = $this->createAdminUser();
        $manifest = Manifest::factory()->create(['is_open' => false]);

        $this->actingAs($user);

        Livewire::test(ManifestLockStatus::class, ['manifest' => $manifest])
            ->set('showUnlockModal', true)
            ->set('unlockReason', 'short')
            ->call('unlockManifest')
            ->assertHasErrors(['unlockReason' => 'min']);
    }

    /** @test */
    public function it_validates_unlock_reason_maximum_length()
    {
        $user = $this->createAdminUser();
        $manifest = Manifest::factory()->create(['is_open' => false]);

        $this->actingAs($user);

        $longReason = str_repeat('a', 501);

        Livewire::test(ManifestLockStatus::class, ['manifest' => $manifest])
            ->set('showUnlockModal', true)
            ->set('unlockReason', $longReason)
            ->call('unlockManifest')
            ->assertHasErrors(['unlockReason' => 'max']);
    }

    /** @test */
    public function it_successfully_unlocks_manifest_with_valid_reason()
    {
        $user = $this->createAdminUser();
        $manifest = Manifest::factory()->create(['is_open' => false]);

        $this->actingAs($user);

        $validReason = 'Need to update package information due to customer request';

        $component = Livewire::test(ManifestLockStatus::class, ['manifest' => $manifest])
            ->set('showUnlockModal', true)
            ->set('unlockReason', $validReason)
            ->call('unlockManifest');
            
        $component->assertSet('showUnlockModal', false)
            ->assertSet('unlockReason', '')
            ->assertEmitted('manifestUnlocked');

        // Verify manifest was unlocked
        $this->assertTrue($manifest->fresh()->is_open);

        // Verify audit record was created
        $this->assertDatabaseHas('manifest_audits', [
            'manifest_id' => $manifest->id,
            'user_id' => $user->id,
            'action' => 'unlocked',
            'reason' => $validReason,
        ]);
    }

    /** @test */
    public function it_prevents_unauthorized_users_from_unlocking()
    {
        $user = $this->createCustomerUser();
        $manifest = Manifest::factory()->create(['is_open' => false]);

        $this->actingAs($user);

        Livewire::test(ManifestLockStatus::class, ['manifest' => $manifest])
            ->call('showUnlockModal')
            ->assertHasErrors(['unlock']);
    }

    /** @test */
    public function it_cancels_unlock_modal_correctly()
    {
        $user = $this->createAdminUser();
        $manifest = Manifest::factory()->create(['is_open' => false]);

        $this->actingAs($user);

        Livewire::test(ManifestLockStatus::class, ['manifest' => $manifest])
            ->set('showUnlockModal', true)
            ->set('unlockReason', 'Some reason')
            ->call('cancelUnlock')
            ->assertSet('showUnlockModal', false)
            ->assertSet('unlockReason', '');
    }

    /** @test */
    public function it_validates_unlock_reason_on_input_change()
    {
        $user = $this->createAdminUser();
        $manifest = Manifest::factory()->create(['is_open' => false]);

        $this->actingAs($user);

        Livewire::test(ManifestLockStatus::class, ['manifest' => $manifest])
            ->set('unlockReason', 'short')
            ->assertHasErrors(['unlockReason' => 'min'])
            ->set('unlockReason', 'This is a valid reason that meets the minimum length requirement')
            ->assertHasNoErrors(['unlockReason']);
    }

    /** @test */
    public function it_does_not_show_unlock_button_for_unauthorized_users()
    {
        $user = $this->createCustomerUser();
        $manifest = Manifest::factory()->create(['is_open' => false]);

        $this->actingAs($user);

        Livewire::test(ManifestLockStatus::class, ['manifest' => $manifest])
            ->assertDontSee('Unlock Manifest');
    }

    /** @test */
    public function it_refreshes_manifest_after_successful_unlock()
    {
        $user = $this->createAdminUser();
        $manifest = Manifest::factory()->create(['is_open' => false]);

        $this->actingAs($user);

        $component = Livewire::test(ManifestLockStatus::class, ['manifest' => $manifest])
            ->set('showUnlockModal', true)
            ->set('unlockReason', 'Valid reason for unlocking manifest')
            ->call('unlockManifest');

        // Verify the component's manifest property reflects the updated state
        $this->assertTrue($component->get('manifest')->is_open);
    }
}