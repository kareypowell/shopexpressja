<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Manifest;
use App\Models\Package;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Http\Livewire\Manifests\IndividualPackagesTab;

class ManifestLockFeeUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Manifest $openManifest;
    private Manifest $closedManifest;
    private Package $package;

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

        // Create a package in the closed manifest
        $this->package = Package::factory()->create([
            'manifest_id' => $this->closedManifest->id,
            'clearance_fee' => 0,
            'storage_fee' => 0,
            'delivery_fee' => 0
        ]);
    }

    /** @test */
    public function it_prevents_showing_fee_entry_modal_on_closed_manifest()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->closedManifest])
            ->call('showFeeEntryModal', $this->package->id)
            ->assertDispatchedBrowserEvent('toastr:error')
            ->assertSet('showFeeModal', false);
    }

    /** @test */
    public function it_prevents_processing_fee_update_on_closed_manifest()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->closedManifest])
            ->set('feePackageId', $this->package->id)
            ->set('customsDuty', 50.00)
            ->set('storageFee', 25.00)
            ->set('deliveryFee', 15.00)
            ->call('processFeeUpdate')
            ->assertDispatchedBrowserEvent('toastr:error');

        // Verify the package fees were not updated
        $this->package->refresh();
        $this->assertEquals(0, $this->package->clearance_fee);
        $this->assertEquals(0, $this->package->storage_fee);
        $this->assertEquals(0, $this->package->delivery_fee);
    }

    /** @test */
    public function it_allows_fee_updates_on_open_manifest()
    {
        $openPackage = Package::factory()->create([
            'manifest_id' => $this->openManifest->id,
            'clearance_fee' => 0,
            'storage_fee' => 0,
            'delivery_fee' => 0
        ]);

        $this->actingAs($this->adminUser);

        Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->openManifest])
            ->call('showFeeEntryModal', $openPackage->id)
            ->assertSet('showFeeModal', true)
            ->assertSet('feePackageId', $openPackage->id);
    }

    /** @test */
    public function it_uses_read_only_template_for_closed_manifest()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->closedManifest]);
        
        // The component should use the read-only template when manifest is closed
        $this->assertStringContainsString('read-only-package-display', $component->payload['effects']['html']);
    }

    /** @test */
    public function it_uses_editable_template_for_open_manifest()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->openManifest]);
        
        // The component should use the editable template when manifest is open
        $this->assertStringContainsString('individual-packages-tab', $component->payload['effects']['html']);
    }
}