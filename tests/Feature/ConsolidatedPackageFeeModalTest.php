<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Http\Livewire\Manifests\PackageWorkflow;
use App\Http\Livewire\Manifests\Packages\ManifestPackage;
use App\Enums\PackageStatus;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsolidatedPackageFeeModalTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $consolidatedPackage;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        // Create a consolidated package with individual packages
        $this->consolidatedPackage = ConsolidatedPackage::factory()->create([
            'status' => PackageStatus::PROCESSING,
        ]);
        
        // Create individual packages in the consolidation
        Package::factory()->count(2)->create([
            'consolidated_package_id' => $this->consolidatedPackage->id,
            'user_id' => $this->user->id,
            'status' => PackageStatus::PROCESSING,
            'clearance_fee' => 0,
            'storage_fee' => 0,
            'delivery_fee' => 0,
        ]);
    }

    /** @test */
    public function it_shows_consolidated_fee_modal_when_updating_status_to_ready_in_package_workflow()
    {
        $component = Livewire::test(PackageWorkflow::class)
            ->call('updateConsolidatedPackageStatus', $this->consolidatedPackage->id, PackageStatus::READY)
            ->assertSet('showConsolidatedFeeModal', true)
            ->assertSet('feeConsolidatedPackageId', $this->consolidatedPackage->id);
            
        $this->assertNotNull($component->get('feeConsolidatedPackage'));
        $this->assertCount(2, $component->get('consolidatedPackagesNeedingFees'));
    }

    /** @test */
    public function it_shows_consolidated_fee_modal_when_updating_status_to_ready_in_manifest_package()
    {
        $component = Livewire::test(ManifestPackage::class)
            ->call('updateConsolidatedPackageStatus', $this->consolidatedPackage->id, PackageStatus::READY)
            ->assertSet('showConsolidatedFeeModal', true)
            ->assertSet('feeConsolidatedPackageId', $this->consolidatedPackage->id);
            
        $this->assertNotNull($component->get('feeConsolidatedPackage'));
        $this->assertCount(2, $component->get('consolidatedPackagesNeedingFees'));
    }

    /** @test */
    public function it_can_close_consolidated_fee_modal()
    {
        Livewire::test(PackageWorkflow::class)
            ->set('showConsolidatedFeeModal', true)
            ->set('feeConsolidatedPackageId', $this->consolidatedPackage->id)
            ->call('closeConsolidatedFeeModal')
            ->assertSet('showConsolidatedFeeModal', false)
            ->assertSet('feeConsolidatedPackageId', null)
            ->assertSet('feeConsolidatedPackage', null)
            ->assertSet('consolidatedPackagesNeedingFees', []);
    }

    /** @test */
    public function it_processes_consolidated_fee_update_and_sets_status_to_ready()
    {
        $packages = $this->consolidatedPackage->packages;
        
        $consolidatedPackagesNeedingFees = [];
        foreach ($packages as $package) {
            $consolidatedPackagesNeedingFees[] = [
                'id' => $package->id,
                'tracking_number' => $package->tracking_number,
                'description' => $package->description,
                'clearance_fee' => 10.00,
                'storage_fee' => 5.00,
                'delivery_fee' => 15.00,
                'needs_fees' => true,
            ];
        }

        Livewire::test(PackageWorkflow::class)
            ->set('showConsolidatedFeeModal', true)
            ->set('feeConsolidatedPackageId', $this->consolidatedPackage->id)
            ->set('consolidatedPackagesNeedingFees', $consolidatedPackagesNeedingFees)
            ->call('processConsolidatedFeeUpdate')
            ->assertSet('showConsolidatedFeeModal', false);

        // Verify that the individual packages have been updated with fees
        foreach ($packages as $package) {
            $package->refresh();
            $this->assertEquals(10.00, $package->clearance_fee);
            $this->assertEquals(5.00, $package->storage_fee);
            $this->assertEquals(15.00, $package->delivery_fee);
        }

        // Verify that the consolidated package status has been updated to READY
        $this->consolidatedPackage->refresh();
        $this->assertEquals(PackageStatus::READY, $this->consolidatedPackage->status);
    }

    /** @test */
    public function it_identifies_packages_that_need_fee_entry()
    {
        // Create a package with missing fees
        $packageNeedingFees = Package::factory()->create([
            'consolidated_package_id' => $this->consolidatedPackage->id,
            'user_id' => $this->user->id,
            'clearance_fee' => 0,
            'storage_fee' => 0,
            'delivery_fee' => 0,
        ]);

        // Create a package with all fees set
        $packageWithFees = Package::factory()->create([
            'consolidated_package_id' => $this->consolidatedPackage->id,
            'user_id' => $this->user->id,
            'clearance_fee' => 10.00,
            'storage_fee' => 5.00,
            'delivery_fee' => 15.00,
        ]);

        Livewire::test(PackageWorkflow::class)
            ->call('showConsolidatedFeeEntryModal', $this->consolidatedPackage->id)
            ->assertSet('showConsolidatedFeeModal', true);

        // The component should identify which packages need fees
        $component = Livewire::test(PackageWorkflow::class);
        $component->call('showConsolidatedFeeEntryModal', $this->consolidatedPackage->id);
        
        $consolidatedPackagesNeedingFees = $component->get('consolidatedPackagesNeedingFees');
        
        // Find the packages in the array
        $needingFeesPackage = collect($consolidatedPackagesNeedingFees)->firstWhere('id', $packageNeedingFees->id);
        $withFeesPackage = collect($consolidatedPackagesNeedingFees)->firstWhere('id', $packageWithFees->id);
        
        $this->assertTrue($needingFeesPackage['needs_fees']);
        $this->assertFalse($withFeesPackage['needs_fees']);
    }
}