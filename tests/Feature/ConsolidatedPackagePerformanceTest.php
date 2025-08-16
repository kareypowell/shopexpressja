<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\Manifest;
use App\Models\Shipper;
use App\Models\Office;
use App\Enums\PackageStatus;
use App\Services\PackageConsolidationService;
use Database\Seeders\ConsolidatedPackageTestDataSeeder;
use Illuminate\Support\Facades\DB;

class ConsolidatedPackagePerformanceTest extends TestCase
{
    use RefreshDatabase;

    private PackageConsolidationService $consolidationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->consolidationService = app(PackageConsolidationService::class);
        
        // Seed base data
        $this->seed([
            \Database\Seeders\RolesTableSeeder::class,
            \Database\Seeders\OfficesTableSeeder::class,
            \Database\Seeders\ShippersTableSeeder::class,
            \Database\Seeders\ManifestsTableSeeder::class,
        ]);
    }

    /** @test */
    public function it_can_handle_large_volume_consolidations_efficiently()
    {
        // Create a customer with many packages
        $customer = User::factory()->create([
            'role_id' => Role::where('name', 'customer')->first()->id,
            'account_balance' => 5000.00,
        ]);

        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->first()->id,
        ]);

        $manifest = Manifest::first();
        $shipper = Shipper::first();
        $office = Office::first();

        // Create 50 packages for consolidation testing
        $packages = Package::factory()->count(50)->create([
            'user_id' => $customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'status' => PackageStatus::READY,
        ]);

        // Measure consolidation performance
        $startTime = microtime(true);
        $startQueries = DB::getQueryLog();
        DB::enableQueryLog();

        // Consolidate packages in batches of 10
        $packageBatches = $packages->chunk(10);
        $consolidatedPackages = [];

        foreach ($packageBatches as $batch) {
            $packageIds = $batch->pluck('id')->toArray();
            $consolidatedPackage = $this->consolidationService->consolidatePackages(
                $packageIds,
                $admin,
                ['notes' => 'Performance test batch consolidation']
            );
            $consolidatedPackages[] = $consolidatedPackage;
        }

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Performance assertions
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(5.0, $executionTime, 'Consolidation should complete within 5 seconds');

        // Verify all packages were consolidated
        $this->assertCount(5, $consolidatedPackages); // 50 packages / 10 per batch = 5 consolidations

        // Verify data integrity
        foreach ($consolidatedPackages as $consolidatedPackage) {
            $this->assertInstanceOf(ConsolidatedPackage::class, $consolidatedPackage);
            $this->assertEquals(10, $consolidatedPackage->total_quantity);
            $this->assertTrue($consolidatedPackage->is_active);
            
            // Verify all packages in consolidation
            $this->assertEquals(10, $consolidatedPackage->packages()->count());
            
            $consolidatedPackage->packages->each(function ($package) use ($customer) {
                $this->assertEquals($customer->id, $package->user_id);
                $this->assertTrue($package->is_consolidated);
                $this->assertEquals($consolidatedPackage->id, $package->consolidated_package_id);
            });
        }

        // Memory usage should be reasonable
        $memoryUsage = memory_get_peak_usage(true);
        $this->assertLessThan(128 * 1024 * 1024, $memoryUsage, 'Memory usage should be under 128MB');
    }

    /** @test */
    public function it_can_search_consolidated_packages_efficiently_with_large_dataset()
    {
        // Seed consolidated package test data
        $this->seed(ConsolidatedPackageTestDataSeeder::class);

        // Create additional consolidated packages for search testing
        $customer = User::factory()->create([
            'role_id' => Role::where('name', 'customer')->first()->id,
        ]);

        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->first()->id,
        ]);

        // Create 20 consolidated packages with 5 packages each
        for ($i = 1; $i <= 20; $i++) {
            $packages = Package::factory()->count(5)->create([
                'user_id' => $customer->id,
                'manifest_id' => Manifest::first()->id,
                'shipper_id' => Shipper::first()->id,
                'office_id' => Office::first()->id,
                'status' => PackageStatus::READY,
                'tracking_number' => "SEARCH-TEST-{$i}-" . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
            ]);

            $packageIds = $packages->pluck('id')->toArray();
            $this->consolidationService->consolidatePackages(
                $packageIds,
                $admin,
                ['notes' => "Search test consolidation {$i}"]
            );
        }

        // Test search performance
        DB::enableQueryLog();
        $startTime = microtime(true);

        // Search by tracking number
        $searchTerm = 'SEARCH-TEST-1';
        $searchResults = Package::where('user_id', $customer->id)
            ->where(function ($query) use ($searchTerm) {
                $query->where('tracking_number', 'like', "%{$searchTerm}%")
                      ->orWhereHas('consolidatedPackage', function ($subQuery) use ($searchTerm) {
                          $subQuery->whereHas('packages', function ($packageQuery) use ($searchTerm) {
                              $packageQuery->where('tracking_number', 'like', "%{$searchTerm}%");
                          });
                      });
            })
            ->with(['consolidatedPackage.packages'])
            ->get();

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Performance assertions
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(1.0, $executionTime, 'Search should complete within 1 second');

        // Verify search results
        $this->assertGreaterThan(0, $searchResults->count());
        
        // Verify found packages contain search term
        $searchResults->each(function ($package) use ($searchTerm) {
            $this->assertStringContainsString($searchTerm, $package->tracking_number);
        });
    }

    /** @test */
    public function it_can_handle_concurrent_consolidation_operations()
    {
        // Create multiple customers with packages
        $customers = User::factory()->count(5)->create([
            'role_id' => Role::where('name', 'customer')->first()->id,
        ]);

        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->first()->id,
        ]);

        $manifest = Manifest::first();
        $shipper = Shipper::first();
        $office = Office::first();

        // Create packages for each customer
        $customerPackages = [];
        foreach ($customers as $customer) {
            $packages = Package::factory()->count(10)->create([
                'user_id' => $customer->id,
                'manifest_id' => $manifest->id,
                'shipper_id' => $shipper->id,
                'office_id' => $office->id,
                'status' => PackageStatus::READY,
            ]);
            $customerPackages[$customer->id] = $packages;
        }

        // Simulate concurrent consolidations
        DB::enableQueryLog();
        $startTime = microtime(true);

        $consolidatedPackages = [];
        foreach ($customerPackages as $customerId => $packages) {
            // Split packages into 2 consolidations per customer
            $batch1 = $packages->take(5);
            $batch2 = $packages->skip(5)->take(5);

            $consolidation1 = $this->consolidationService->consolidatePackages(
                $batch1->pluck('id')->toArray(),
                $admin,
                ['notes' => "Concurrent test - Customer {$customerId} - Batch 1"]
            );

            $consolidation2 = $this->consolidationService->consolidatePackages(
                $batch2->pluck('id')->toArray(),
                $admin,
                ['notes' => "Concurrent test - Customer {$customerId} - Batch 2"]
            );

            $consolidatedPackages[] = $consolidation1;
            $consolidatedPackages[] = $consolidation2;
        }

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Performance assertions
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(3.0, $executionTime, 'Concurrent consolidations should complete within 3 seconds');

        // Verify all consolidations were created
        $this->assertCount(10, $consolidatedPackages); // 5 customers × 2 consolidations each

        // Verify data integrity
        foreach ($consolidatedPackages as $consolidatedPackage) {
            $this->assertInstanceOf(ConsolidatedPackage::class, $consolidatedPackage);
            $this->assertEquals(5, $consolidatedPackage->total_quantity);
            $this->assertTrue($consolidatedPackage->is_active);
            
            // Verify packages belong to correct customer
            $consolidatedPackage->packages->each(function ($package) use ($consolidatedPackage) {
                $this->assertEquals($consolidatedPackage->customer_id, $package->user_id);
                $this->assertTrue($package->is_consolidated);
            });
        }

        // Verify no duplicate consolidations
        $trackingNumbers = collect($consolidatedPackages)->pluck('consolidated_tracking_number')->toArray();
        $this->assertEquals(10, count(array_unique($trackingNumbers)));
    }

    /** @test */
    public function it_maintains_performance_with_large_consolidation_history()
    {
        // Seed test data
        $this->seed(ConsolidatedPackageTestDataSeeder::class);

        // Get a customer with existing consolidations
        $customer = User::where('email', 'highvolume.consolidation@test.com')->first();
        $this->assertNotNull($customer);

        // Get consolidated packages for this customer
        $consolidatedPackages = ConsolidatedPackage::where('customer_id', $customer->id)->get();
        $this->assertGreaterThan(0, $consolidatedPackages->count());

        // Measure performance of loading consolidation data with history
        DB::enableQueryLog();
        $startTime = microtime(true);

        // Load consolidated packages with all relationships
        $consolidatedPackagesWithRelations = ConsolidatedPackage::where('customer_id', $customer->id)
            ->with([
                'packages',
                'customer',
                'createdBy',
                'history.performedBy'
            ])
            ->get();

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Performance assertions
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(0.5, $executionTime, 'Loading consolidated packages with relations should be fast');

        // Verify data was loaded correctly
        $consolidatedPackagesWithRelations->each(function ($consolidatedPackage) {
            $this->assertNotNull($consolidatedPackage->customer);
            $this->assertNotNull($consolidatedPackage->createdBy);
            $this->assertGreaterThan(0, $consolidatedPackage->packages->count());
            
            // Verify calculated totals match
            $calculatedWeight = $consolidatedPackage->packages->sum('weight');
            $this->assertEquals($calculatedWeight, $consolidatedPackage->total_weight);
        });
    }

    /** @test */
    public function it_can_handle_bulk_status_updates_efficiently()
    {
        // Create consolidated packages in different statuses
        $customer = User::factory()->create([
            'role_id' => Role::where('name', 'customer')->first()->id,
        ]);

        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->first()->id,
        ]);

        // Create 10 consolidated packages in READY status
        $consolidatedPackages = [];
        for ($i = 1; $i <= 10; $i++) {
            $packages = Package::factory()->count(3)->create([
                'user_id' => $customer->id,
                'manifest_id' => Manifest::first()->id,
                'shipper_id' => Shipper::first()->id,
                'office_id' => Office::first()->id,
                'status' => PackageStatus::READY,
            ]);

            $packageIds = $packages->pluck('id')->toArray();
            $consolidatedPackage = $this->consolidationService->consolidatePackages(
                $packageIds,
                $admin,
                ['notes' => "Bulk status update test {$i}"]
            );
            $consolidatedPackages[] = $consolidatedPackage;
        }

        // Measure bulk status update performance
        DB::enableQueryLog();
        $startTime = microtime(true);

        // Update all consolidated packages to DELIVERED status
        foreach ($consolidatedPackages as $consolidatedPackage) {
            $this->consolidationService->updateConsolidatedStatus(
                $consolidatedPackage,
                PackageStatus::DELIVERED,
                $admin
            );
        }

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Performance assertions
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(2.0, $executionTime, 'Bulk status updates should complete within 2 seconds');

        // Verify all packages were updated
        foreach ($consolidatedPackages as $consolidatedPackage) {
            $consolidatedPackage->refresh();
            $this->assertEquals(PackageStatus::DELIVERED, $consolidatedPackage->status);
            
            // Verify individual packages were also updated
            $consolidatedPackage->packages->each(function ($package) {
                $this->assertEquals(PackageStatus::DELIVERED, $package->status);
            });
        }

        // Verify history records were created
        $historyCount = \App\Models\ConsolidationHistory::where('action', 'status_changed')->count();
        $this->assertEquals(10, $historyCount);
    }
}