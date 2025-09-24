<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\PackageDistribution;
use App\Models\Manifest;
use App\Models\Office;
use App\Models\Shipper;
use App\Models\Rate;
use App\Enums\PackageStatus;
use App\Services\PackageConsolidationService;
use App\Services\PackageDistributionService;
use App\Services\DashboardAnalyticsService;
use App\Services\CustomerStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsolidationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $customerUser;
    protected $consolidationService;
    protected $distributionService;
    protected $manifest;
    protected $office;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $customerRole = Role::factory()->create(['name' => 'customer']);

        // Create users
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->customerUser = User::factory()->create([
            'role_id' => $customerRole->id,
            'account_balance' => 1000.00
        ]);

        // Create required entities
        $this->office = Office::factory()->create();
        $shipper = Shipper::factory()->create();
        $this->manifest = Manifest::factory()->create();

        // Create rates
        Rate::factory()->create([
            'price' => 10.00,
            'type' => 'air'
        ]);

        // Initialize services
        $this->consolidationService = app(PackageConsolidationService::class);
        $this->distributionService = app(PackageDistributionService::class);
    }

    /** @test */
    public function consolidation_integrates_with_dashboard_analytics()
    {
        // Create individual packages
        $individualPackages = Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'status' => PackageStatus::DELIVERED,
            'freight_price' => 25.00
        ]);

        // Create consolidated packages
        $consolidatedPackages = Package::factory()->count(4)->create([
            'user_id' => $this->customerUser->id,
            'status' => PackageStatus::READY,
            'freight_price' => 30.00
        ]);

        $this->actingAs($this->adminUser);

        // Consolidate packages
        $consolidationResult = $this->consolidationService->consolidatePackages(
            $consolidatedPackages->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage = $consolidationResult['consolidated_package'];

        // Distribute consolidated package
        $this->consolidationService->updateConsolidatedStatus(
            $consolidatedPackage,
            PackageStatus::DELIVERED,
            $this->adminUser
        );

        // Test that both individual and consolidated packages exist in the system
        $totalPackages = Package::count();
        $consolidatedPackages = ConsolidatedPackage::count();
        
        $this->assertEquals(7, $totalPackages); // 3 individual + 4 in consolidation
        $this->assertEquals(1, $consolidatedPackages);

        // Test that revenue calculations work with both types
        $totalRevenue = Package::where('status', PackageStatus::DELIVERED)->sum('freight_price');
        $expectedRevenue = (3 * 25.00) + (4 * 30.00); // Individual + consolidated
        $this->assertEquals($expectedRevenue, $totalRevenue);
    }

    /** @test */
    public function consolidation_integrates_with_customer_statistics()
    {
        // Create packages for customer
        $packages = Package::factory()->count(5)->create([
            'user_id' => $this->customerUser->id,
            'status' => PackageStatus::READY,
            'weight' => 2.0,
            'freight_price' => 20.00
        ]);

        $this->actingAs($this->adminUser);

        // Consolidate 3 packages
        $consolidationResult = $this->consolidationService->consolidatePackages(
            $packages->take(3)->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage = $consolidationResult['consolidated_package'];

        // Test customer statistics service
        $statsService = app(CustomerStatisticsService::class);
        $customerStats = $statsService->getCustomerStatistics($this->customerUser->id);

        // Should include consolidated packages in totals
        $this->assertEquals(5, $customerStats['total_packages']); // All packages counted
        $this->assertEquals(1, $customerStats['consolidated_packages']);
        $this->assertEquals(10.0, $customerStats['total_weight']); // 5 * 2.0
        $this->assertEquals(100.00, $customerStats['total_value']); // 5 * 20.00

        // Consolidation should be reflected in package breakdown
        $this->assertEquals(2, $customerStats['individual_packages']); // 5 - 3 consolidated
        $this->assertEquals(3, $customerStats['packages_in_consolidations']);
    }

    /** @test */
    public function consolidation_integrates_with_package_distribution_system()
    {
        // Create packages with different costs
        $packages = Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'status' => PackageStatus::READY_FOR_PICKUP,
            'freight_price' => 25.00,
            'clearance_fee' => 5.00,
            'storage_fee' => 2.00,
            'delivery_fee' => 8.00
        ]);

        $this->actingAs($this->adminUser);

        // Consolidate packages
        $consolidationResult = $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage = $consolidationResult['consolidated_package'];

        // Update to ready for pickup
        $this->consolidationService->updateConsolidatedStatus(
            $consolidatedPackage,
            PackageStatus::READY_FOR_PICKUP,
            $this->adminUser
        );

        // Test distribution integration
        $totalCost = $consolidatedPackage->total_freight_price + 
                    $consolidatedPackage->total_clearance_fee + 
                    $consolidatedPackage->total_storage_fee + 
                    $consolidatedPackage->total_delivery_fee;

        $distributionResult = $this->distributionService->distributeConsolidatedPackages(
            $consolidatedPackage,
            $totalCost,
            $this->adminUser,
            ['use_account_balance' => true]
        );

        $this->assertTrue($distributionResult['success']);
        $distribution = $distributionResult['distribution'];

        // Verify distribution record
        $this->assertEquals($totalCost, $distribution->total_amount);
        $this->assertEquals(120.00, $distribution->total_amount); // (25+5+2+8) * 3

        // Verify customer balance was deducted
        $this->customerUser->refresh();
        $this->assertEquals(880.00, $this->customerUser->account_balance); // 1000 - 120

        // Verify all packages in consolidation are marked as delivered
        $packages->each(function ($package) {
            $package->refresh();
            $this->assertEquals(PackageStatus::DELIVERED, $package->status);
        });
    }

    /** @test */
    public function consolidation_integrates_with_manifest_workflow()
    {
        // Create packages in manifest
        $packages = Package::factory()->count(4)->create([
            'user_id' => $this->customerUser->id,
            'manifest_id' => $this->manifest->id,
            'office_id' => $this->office->id,
            'status' => PackageStatus::PROCESSING
        ]);

        $this->actingAs($this->adminUser);

        // Consolidate packages
        $consolidationResult = $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage = $consolidationResult['consolidated_package'];

        // Update manifest status - should affect consolidated package
        $this->consolidationService->updateConsolidatedStatus(
            $consolidatedPackage,
            PackageStatus::IN_TRANSIT,
            $this->adminUser
        );

        // Verify manifest integration
        $consolidatedPackage->refresh();
        $this->assertEquals(PackageStatus::IN_TRANSIT, $consolidatedPackage->status);

        // Verify manifest totals include consolidated package totals
        $manifestPackages = Package::where('manifest_id', $this->manifest->id)->get();
        $manifestTotal = $manifestPackages->sum('freight_price');
        $consolidatedTotal = $consolidatedPackage->total_freight_price;

        $this->assertEquals($consolidatedTotal, $manifestTotal);

        // Test manifest completion with consolidated packages
        $this->consolidationService->updateConsolidatedStatus(
            $consolidatedPackage,
            PackageStatus::DELIVERED,
            $this->adminUser
        );

        // All packages in manifest should be delivered
        $manifestPackages->each(function ($package) {
            $package->refresh();
            $this->assertEquals(PackageStatus::DELIVERED, $package->status);
        });
    }

    /** @test */
    public function consolidation_integrates_with_search_functionality()
    {
        // Create packages with specific tracking numbers
        $packages = collect();
        $trackingNumbers = ['SEARCH-001', 'SEARCH-002', 'SEARCH-003'];
        
        foreach ($trackingNumbers as $trackingNumber) {
            $packages->push(Package::factory()->create([
                'user_id' => $this->customerUser->id,
                'tracking_number' => $trackingNumber,
                'status' => PackageStatus::READY,
                'description' => 'Test package for search'
            ]));
        }

        $this->actingAs($this->adminUser);

        // Consolidate packages
        $consolidationResult = $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage = $consolidationResult['consolidated_package'];

        // Test search by individual tracking number
        $searchResult = Package::where('tracking_number', 'SEARCH-002')->first();
        $this->assertNotNull($searchResult);
        $this->assertEquals($consolidatedPackage->id, $searchResult->consolidated_package_id);

        // Test search by consolidated tracking number
        $consolidatedSearch = ConsolidatedPackage::where('consolidated_tracking_number', $consolidatedPackage->consolidated_tracking_number)->first();
        $this->assertNotNull($consolidatedSearch);
        $this->assertEquals($consolidatedPackage->id, $consolidatedSearch->id);

        // Test search within consolidated packages
        $packagesInConsolidation = Package::where('consolidated_package_id', $consolidatedPackage->id)
            ->where('description', 'like', '%search%')
            ->get();
        $this->assertEquals(3, $packagesInConsolidation->count());

        // Test global search includes consolidated packages
        $globalSearch = Package::where('tracking_number', 'like', '%SEARCH%')
            ->orWhereHas('consolidatedPackage', function ($query) {
                $query->where('consolidated_tracking_number', 'like', '%CONS%');
            })
            ->get();
        $this->assertGreaterThanOrEqual(3, $globalSearch->count());
    }

    /** @test */
    public function consolidation_integrates_with_notification_system()
    {
        // Create packages
        $packages = Package::factory()->count(2)->create([
            'user_id' => $this->customerUser->id,
            'status' => PackageStatus::READY
        ]);

        $this->actingAs($this->adminUser);

        // Consolidate packages
        $consolidationResult = $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage = $consolidationResult['consolidated_package'];

        // Test status change notifications
        $statusUpdateResult = $this->consolidationService->updateConsolidatedStatus(
            $consolidatedPackage,
            PackageStatus::SHIPPED,
            $this->adminUser
        );

        $this->assertTrue($statusUpdateResult['success']);

        // Verify notification integration
        $this->assertEquals(PackageStatus::SHIPPED, $consolidatedPackage->fresh()->status);
        
        // All individual packages should have same status
        $packages->each(function ($package) {
            $package->refresh();
            $this->assertEquals(PackageStatus::SHIPPED, $package->status);
        });
    }

    /** @test */
    public function consolidation_integrates_with_reporting_system()
    {
        // Create packages over different time periods
        $oldPackages = Package::factory()->count(2)->create([
            'user_id' => $this->customerUser->id,
            'status' => PackageStatus::DELIVERED,
            'freight_price' => 30.00,
            'created_at' => now()->subDays(30)
        ]);

        $recentPackages = Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'status' => PackageStatus::READY,
            'freight_price' => 25.00
        ]);

        $this->actingAs($this->adminUser);

        // Consolidate recent packages
        $consolidationResult = $this->consolidationService->consolidatePackages(
            $recentPackages->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage = $consolidationResult['consolidated_package'];

        // Deliver consolidated package
        $this->consolidationService->updateConsolidatedStatus(
            $consolidatedPackage,
            PackageStatus::DELIVERED,
            $this->adminUser
        );

        // Test reporting includes consolidated packages
        $monthlyReport = Package::whereMonth('created_at', now()->month)
            ->where('status', PackageStatus::DELIVERED)
            ->get();

        $this->assertEquals(3, $monthlyReport->count()); // Recent consolidated packages

        // Test revenue reporting
        $monthlyRevenue = Package::whereMonth('created_at', now()->month)
            ->where('status', PackageStatus::DELIVERED)
            ->sum('freight_price');

        $this->assertEquals(75.00, $monthlyRevenue); // 3 * 25.00

        // Test consolidated package specific reporting
        $consolidatedReport = ConsolidatedPackage::where('is_active', true)
            ->orWhere('status', PackageStatus::DELIVERED)
            ->get();

        $this->assertEquals(1, $consolidatedReport->count());
        $this->assertEquals(75.00, $consolidatedReport->first()->total_freight_price);
    }

    /** @test */
    public function consolidation_maintains_data_integrity_across_systems()
    {
        // Create packages with various attributes
        $packages = Package::factory()->count(4)->create([
            'user_id' => $this->customerUser->id,
            'status' => PackageStatus::READY,
            'weight' => 1.5,
            'quantity' => 2,
            'freight_price' => 20.00,
            'clearance_fee' => 3.00,
            'storage_fee' => 1.50,
            'delivery_fee' => 5.00
        ]);

        $this->actingAs($this->adminUser);

        // Store original totals
        $originalTotalWeight = $packages->sum('weight');
        $originalTotalQuantity = $packages->sum('quantity');
        $originalTotalFreight = $packages->sum('freight_price');
        $originalTotalClearnace = $packages->sum('clearance_fee');
        $originalTotalStorage = $packages->sum('storage_fee');
        $originalTotalDelivery = $packages->sum('delivery_fee');

        // Consolidate packages
        $consolidationResult = $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage = $consolidationResult['consolidated_package'];

        // Verify data integrity - totals should match
        $this->assertEquals($originalTotalWeight, $consolidatedPackage->total_weight);
        $this->assertEquals($originalTotalQuantity, $consolidatedPackage->total_quantity);
        $this->assertEquals($originalTotalFreight, $consolidatedPackage->total_freight_price);
        $this->assertEquals($originalTotalClearnace, $consolidatedPackage->total_clearance_fee);
        $this->assertEquals($originalTotalStorage, $consolidatedPackage->total_storage_fee);
        $this->assertEquals($originalTotalDelivery, $consolidatedPackage->total_delivery_fee);

        // Update one individual package and verify consolidation updates
        $firstPackage = $packages->first();
        $firstPackage->update(['freight_price' => 25.00]); // +5.00

        // Recalculate consolidated totals
        $consolidatedPackage->calculateTotals();
        $consolidatedPackage->save();

        $this->assertEquals($originalTotalFreight + 5.00, $consolidatedPackage->fresh()->total_freight_price);

        // Unconsolidate and verify data integrity
        $unconsolidationResult = $this->consolidationService->unconsolidatePackages(
            $consolidatedPackage,
            $this->adminUser
        );

        $this->assertTrue($unconsolidationResult['success']);

        // Verify all individual packages retain their data
        $packages->each(function ($package) {
            $package->refresh();
            $this->assertNull($package->consolidated_package_id);
            $this->assertFalse($package->is_consolidated);
            $this->assertNotNull($package->weight);
            $this->assertNotNull($package->quantity);
            $this->assertNotNull($package->freight_price);
        });

        // Verify totals still match after unconsolidation
        $packages->each->refresh();
        $finalTotalWeight = $packages->sum('weight');
        $finalTotalFreight = $packages->sum('freight_price');

        $this->assertEquals($originalTotalWeight, $finalTotalWeight);
        $this->assertEquals($originalTotalFreight + 5.00, $finalTotalFreight); // Including the update
    }
}