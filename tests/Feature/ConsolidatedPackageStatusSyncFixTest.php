<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsolidatedPackageStatusSyncFixTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_allows_delivered_status_when_syncing_from_consolidated_package()
    {
        $user = User::factory()->create(['role_id' => 1]);
        $customer = User::factory()->create(['role_id' => 2]);
        $this->actingAs($user);

        // Create consolidated package
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'ready',
        ]);

        // Create individual packages
        $packages = Package::factory()->count(2)->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'user_id' => $customer->id,
            'status' => 'ready',
        ]);

        // Verify initial status
        foreach ($packages as $package) {
            $this->assertEquals('ready', $package->fresh()->status);
        }

        // Call syncPackageStatuses to DELIVERED
        $consolidatedPackage->syncPackageStatuses('delivered', $user);

        // Verify consolidated package status
        $this->assertEquals('delivered', $consolidatedPackage->fresh()->status);

        // Verify individual packages are updated to DELIVERED
        foreach ($packages as $package) {
            $package->refresh();
            $this->assertEquals('delivered', $package->status, 
                "Package {$package->id} should be delivered but is {$package->status}");
        }
    }

    /** @test */
    public function it_blocks_manual_delivered_status_updates()
    {
        $user = User::factory()->create(['role_id' => 1]);
        $customer = User::factory()->create(['role_id' => 2]);
        $this->actingAs($user);

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => 'ready',
        ]);

        $packageStatusService = app(\App\Services\PackageStatusService::class);

        // Try manual update to DELIVERED (should be blocked)
        $result = $packageStatusService->updateStatus(
            $package,
            PackageStatus::DELIVERED(),
            $user,
            'Manual update attempt',
            false, // allowDeliveredStatus = false
            false  // fromConsolidatedUpdate = false
        );

        // Should be blocked
        $this->assertFalse($result);
        $this->assertEquals('ready', $package->fresh()->status);
    }
}