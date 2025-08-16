<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Services\PackageDistributionService;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsolidatedPackageDistributionRealWorldTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_properly_updates_individual_packages_when_consolidated_package_is_distributed()
    {
        // Setup users
        $admin = User::factory()->create(['role_id' => 1]);
        $customer = User::factory()->create(['role_id' => 2]);
        $this->actingAs($admin);

        // Create consolidated package with READY status
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'ready',
        ]);

        // Create individual packages with READY status and fees
        $packages = Package::factory()->count(3)->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'user_id' => $customer->id,
            'status' => 'ready',
            'freight_price' => 100.00,
            'customs_duty' => 15.00,
            'storage_fee' => 10.00,
            'delivery_fee' => 20.00,
        ]);

        // Verify initial state - all packages should be READY
        $this->assertEquals('ready', $consolidatedPackage->fresh()->status);
        foreach ($packages as $package) {
            $this->assertEquals('ready', $package->fresh()->status);
        }

        // Distribute the consolidated package
        $distributionService = app(PackageDistributionService::class);
        $result = $distributionService->distributeConsolidatedPackages(
            $consolidatedPackage,
            500.00, // amount collected
            $admin,
            ['credit' => false, 'account' => false],
            ['notes' => 'Test consolidated distribution']
        );

        // Verify distribution was successful
        $this->assertTrue($result['success'], 'Distribution should be successful');

        // Verify consolidated package is now DELIVERED
        $consolidatedPackage->refresh();
        $this->assertEquals('delivered', $consolidatedPackage->status);

        // CRITICAL: Verify all individual packages are now DELIVERED
        foreach ($packages as $package) {
            $package->refresh();
            $this->assertEquals('delivered', $package->status, 
                "Package {$package->tracking_number} (ID: {$package->id}) should be DELIVERED but is {$package->status}");
        }

        // Verify that the packages are no longer showing as ready for pickup
        $readyPackages = Package::where('user_id', $customer->id)
            ->where('status', 'ready')
            ->get();
        
        $this->assertCount(0, $readyPackages, 
            'There should be no packages still showing as ready for pickup after distribution');

        // Verify that all packages are now delivered
        $deliveredPackages = Package::where('user_id', $customer->id)
            ->where('status', 'delivered')
            ->get();
        
        $this->assertCount(3, $deliveredPackages, 
            'All 3 packages should now be delivered');
    }
}