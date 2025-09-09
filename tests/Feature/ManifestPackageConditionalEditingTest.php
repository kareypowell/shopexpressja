<?php

namespace Tests\Feature;

use App\Http\Livewire\Manifests\Packages\ManifestPackage;
use App\Models\Manifest;
use App\Models\Package;
use App\Models\User;
use App\Models\Role;
use App\Services\ManifestLockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManifestPackageConditionalEditingTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $manifest;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role and user
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);

        // Create a manifest
        $this->manifest = Manifest::factory()->create(['is_open' => true]);
    }

    /** @test */
    public function can_add_package_when_manifest_is_open()
    {
        $this->actingAs($this->admin);

        Livewire::test(ManifestPackage::class, ['manifest' => $this->manifest])
            ->call('create')
            ->assertSet('isOpen', true);
    }

    /** @test */
    public function cannot_add_package_when_manifest_is_closed()
    {
        $this->manifest->update(['is_open' => false]);
        $this->actingAs($this->admin);

        Livewire::test(ManifestPackage::class, ['manifest' => $this->manifest])
            ->call('create')
            ->assertSet('isOpen', false)
            ->assertDispatchedBrowserEvent('toastr:error', [
                'message' => 'Cannot add packages to a closed manifest.',
            ]);
    }

    /** @test */
    public function cannot_show_bulk_status_update_when_manifest_is_closed()
    {
        $this->manifest->update(['is_open' => false]);
        $package = Package::factory()->create(['manifest_id' => $this->manifest->id]);
        $this->actingAs($this->admin);

        Livewire::test(ManifestPackage::class, ['manifest' => $this->manifest])
            ->set('selectedPackages', [$package->id])
            ->call('showBulkStatusUpdate')
            ->assertDispatchedBrowserEvent('toastr:error', [
                'message' => 'Cannot update package status on a closed manifest.',
            ]);
    }

    /** @test */
    public function cannot_show_consolidation_modal_when_manifest_is_closed()
    {
        $this->manifest->update(['is_open' => false]);
        $package1 = Package::factory()->create(['manifest_id' => $this->manifest->id]);
        $package2 = Package::factory()->create(['manifest_id' => $this->manifest->id]);
        $this->actingAs($this->admin);

        Livewire::test(ManifestPackage::class, ['manifest' => $this->manifest])
            ->set('selectedPackages', [$package1->id, $package2->id])
            ->call('showConsolidationModal')
            ->assertDispatchedBrowserEvent('toastr:error', [
                'message' => 'Cannot consolidate packages on a closed manifest.',
            ]);
    }

    /** @test */
    public function can_edit_manifest_returns_correct_value_based_on_lock_status()
    {
        $this->actingAs($this->admin);

        // Test with open manifest
        $component = Livewire::test(ManifestPackage::class, ['manifest' => $this->manifest]);
        $this->assertTrue($component->get('canEditManifest'));

        // Test with closed manifest
        $this->manifest->update(['is_open' => false]);
        $component = Livewire::test(ManifestPackage::class, ['manifest' => $this->manifest]);
        $this->assertFalse($component->get('canEditManifest'));
    }

    /** @test */
    public function refreshes_manifest_data_when_unlocked()
    {
        $this->manifest->update(['is_open' => false]);
        $this->actingAs($this->admin);

        $component = Livewire::test(ManifestPackage::class, ['manifest' => $this->manifest]);
        $this->assertFalse($component->get('canEditManifest'));

        // Simulate manifest unlock
        $this->manifest->update(['is_open' => true]);
        
        $component->call('refreshManifestData');
        $this->assertTrue($component->get('canEditManifest'));
    }

    /** @test */
    public function render_method_passes_can_edit_flag_to_view()
    {
        $this->actingAs($this->admin);

        // Test with open manifest
        Livewire::test(ManifestPackage::class, ['manifest' => $this->manifest])
            ->assertViewHas('canEdit', true);

        // Test with closed manifest
        $this->manifest->update(['is_open' => false]);
        Livewire::test(ManifestPackage::class, ['manifest' => $this->manifest])
            ->assertViewHas('canEdit', false);
    }
}