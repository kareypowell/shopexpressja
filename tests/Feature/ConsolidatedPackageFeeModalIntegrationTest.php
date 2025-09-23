<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Http\Livewire\Manifests\PackageWorkflow;
use App\Enums\PackageStatus;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsolidatedPackageFeeModalIntegrationTest extends TestCase
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
    public function it_completes_full_consolidated_fee_update_workflow_without_errors()
    {
        $packages = $this->consolidatedPackage->packages;
        
        // Prepare fee data
        $consolidatedPackagesNeedingFees = [];
        foreach ($packages as $package) {
            $consolidatedPackagesNeedingFees[] = [
                'id' => $package->id,
                'tracking_number' => $package->tracking_number,
                'description' => $package->description,
                'clearance_fee' => 25.00,
                'storage_fee' => 10.00,
                'delivery_fee' => 20.00,
                'needs_fees' => true,
            ];
        }

        // Test the complete workflow
        $component = Livewire::test(PackageWorkflow::class)
            // Step 1: Trigger status update to READY (should show modal)
            ->call('updateConsolidatedPackageStatus', $this->consolidatedPackage->id, PackageStatus::READY)
            ->assertSet('showConsolidatedFeeModal', true)
            ->assertSet('feeConsolidatedPackageId', $this->consolidatedPackage->id)
            
            // Step 2: Set fee data
            ->set('consolidatedPackagesNeedingFees', $consolidatedPackagesNeedingFees)
            
            // Step 3: Process fee update (should complete without errors)
            ->call('processConsolidatedFeeUpdate')
            ->assertSet('showConsolidatedFeeModal', false);

        // Verify database changes
        foreach ($packages as $package) {
            $package->refresh();
            $this->assertEquals(25.00, $package->clearance_fee);
            $this->assertEquals(10.00, $package->storage_fee);
            $this->assertEquals(20.00, $package->delivery_fee);
        }

        // Verify consolidated package status
        $this->consolidatedPackage->refresh();
        $this->assertEquals(PackageStatus::READY, $this->consolidatedPackage->status);
    }

    /** @test */
    public function it_handles_fee_update_errors_gracefully()
    {
        // Test with invalid consolidated package ID
        $component = Livewire::test(PackageWorkflow::class)
            ->set('showConsolidatedFeeModal', true)
            ->set('feeConsolidatedPackageId', 99999) // Non-existent ID
            ->set('consolidatedPackagesNeedingFees', [])
            ->call('processConsolidatedFeeUpdate')
            ->assertSet('showConsolidatedFeeModal', false); // Should close modal even on error

        // Original consolidated package should remain unchanged
        $this->consolidatedPackage->refresh();
        $this->assertEquals(PackageStatus::PROCESSING, $this->consolidatedPackage->status);
    }

    /** @test */
    public function it_emits_package_status_updated_event_after_successful_fee_update()
    {
        $packages = $this->consolidatedPackage->packages;
        
        $consolidatedPackagesNeedingFees = [];
        foreach ($packages as $package) {
            $consolidatedPackagesNeedingFees[] = [
                'id' => $package->id,
                'tracking_number' => $package->tracking_number,
                'description' => $package->description,
                'clearance_fee' => 15.00,
                'storage_fee' => 8.00,
                'delivery_fee' => 12.00,
                'needs_fees' => true,
            ];
        }

        // Test that the event is emitted
        Livewire::test(PackageWorkflow::class)
            ->set('showConsolidatedFeeModal', true)
            ->set('feeConsolidatedPackageId', $this->consolidatedPackage->id)
            ->set('consolidatedPackagesNeedingFees', $consolidatedPackagesNeedingFees)
            ->call('processConsolidatedFeeUpdate')
            ->assertEmitted('packageStatusUpdated');
    }
}