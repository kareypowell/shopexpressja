<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\Manifest;
use App\Models\Office;
use App\Models\Shipper;
use App\Enums\PackageStatus;
use App\Services\PackageConsolidationService;
use App\Services\PackageDistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ConsolidationLoadPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $consolidationService;
    protected $distributionService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $customerRole = Role::factory()->create(['name' => 'customer']);

        // Create admin user
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);

        // Initialize services
        $this->consolidationService = app(PackageConsolidationService::class);
        $this->distributionService = app(PackageDistributionService::class);
    }

    /** @test */
    public function consolidation_performance_with_large_package_sets()
    {
        // Create customer with many packages
        $customer = User::factory()->create([
            'role_id' => Role::where('name', 'customer')->first()->id,
            'account_balance' => 10000.00
        ]);

        $office = Office::factory()->create();
        $shipper = Shipper::factory()->create();
        $manifest = Manifest::factory()->create([
            'office_id' => $office->id,
            'shipper_id' => $shipper->id
        ]);

        // Test with different package counts
        $packageCounts = [10, 25, 50, 100];

        foreach ($packageCounts as $packageCount) {
            // Create packages
            $packages = Package::factory()->count($packageCount)->create([
                'user_id' => $customer->id,
                'manifest_id' => $manifest->id,
                'office_id' => $office->id,
                'status' => PackageStatus::READY,
                'weight' => 2.0,
                'freight_price' => 25.00
            ]);

            $this->actingAs($this->adminUser);

            // Measure consolidation time
            $startTime = microtime(true);

            $result = $this->consolidationService->consolidatePackages(
                $packages->pluck('id')->toArray(),
                $this->adminUser,
                ['notes' => "Performance test with {$packageCount} packages"]
            );

            $consolidationTime = microtime(true) - $startTime;

            $this->assertTrue($result['success']);
            $this->assertLessThan(5.0, $consolidationTime, 
                "Consolidation of {$packageCount} packages took {$consolidationTime} seconds, which exceeds the 5-second threshold");

            $consolidatedPackage = $result['consolidated_package'];

            // Verify totals are calculated correctly
            $this->assertEquals($packageCount * 2.0, $consolidatedPackage->total_weight);
            $this->assertEquals($packageCount * 25.00, $consolidatedPackage->total_freight_price);

            // Test status update performance
            $startTime = microtime(true);

            $statusResult = $this->consolidationService->updateConsolidatedStatus(
                $consolidatedPackage,
                PackageStatus::SHIPPED,
                $this->adminUser
            );

            $statusUpdateTime = microtime(true) - $startTime;

            $this->assertTrue($statusResult['success']);
            $this->assertLessThan(2.0, $statusUpdateTime,
                "Status update for {$packageCount} packages took {$statusUpdateTime} seconds, which exceeds the 2-second threshold");

            // Test distribution performance
            $startTime = microtime(true);

            $distributionResult = $this->distributionService->distributeConsolidatedPackages(
                $consolidatedPackage,
                $consolidatedPackage->total_freight_price,
                $this->adminUser
            );

            $distributionTime = microtime(true) - $startTime;

            $this->assertTrue($distributionResult['success']);
            $this->assertLessThan(3.0, $distributionTime,
                "Distribution of {$packageCount} packages took {$distributionTime} seconds, which exceeds the 3-second threshold");

            Log::info("Performance test completed for {$packageCount} packages", [
                'consolidation_time' => $consolidationTime,
                'status_update_time' => $statusUpdateTime,
                'distribution_time' => $distributionTime
            ]);

            // Clean up for next iteration
            ConsolidatedPackage::truncate();
            Package::truncate();
        }
    }

    /** @test */
    public function concurrent_consolidation_operations_performance()
    {
        $customerCount = 10;
        $packagesPerCustomer = 5;
        $customers = [];

        // Create customers and packages
        for ($i = 0; $i < $customerCount; $i++) {
            $customer = User::factory()->create([
                'role_id' => Role::where('name', 'customer')->first()->id,
                'account_balance' => 1000.00
            ]);
            $customers[] = $customer;

            Package::factory()->count($packagesPerCustomer)->create([
                'user_id' => $customer->id,
                'status' => PackageStatus::READY,
                'weight' => 1.5,
                'freight_price' => 20.00
            ]);
        }

        $this->actingAs($this->adminUser);

        // Measure concurrent consolidation time
        $startTime = microtime(true);

        $consolidatedPackages = [];
        foreach ($customers as $customer) {
            $packages = Package::where('user_id', $customer->id)->get();
            
            $result = $this->consolidationService->consolidatePackages(
                $packages->pluck('id')->toArray(),
                $this->adminUser,
                ['notes' => "Concurrent test for customer {$customer->id}"]
            );

            $this->assertTrue($result['success']);
            $consolidatedPackages[] = $result['consolidated_package'];
        }

        $totalTime = microtime(true) - $startTime;

        $this->assertLessThan(15.0, $totalTime,
            "Concurrent consolidation of {$customerCount} sets took {$totalTime} seconds, which exceeds the 15-second threshold");

        // Verify no data corruption occurred
        $totalConsolidatedPackages = ConsolidatedPackage::count();
        $this->assertEquals($customerCount, $totalConsolidatedPackages);

        $totalPackagesInConsolidations = Package::where('is_consolidated', true)->count();
        $this->assertEquals($customerCount * $packagesPerCustomer, $totalPackagesInConsolidations);

        Log::info('Concurrent consolidation test completed', [
            'customer_count' => $customerCount,
            'packages_per_customer' => $packagesPerCustomer,
            'total_time' => $totalTime,
            'average_time_per_consolidation' => $totalTime / $customerCount
        ]);
    }

    /** @test */
    public function database_query_performance_under_load()
    {
        // Create large dataset
        $customerCount = 20;
        $packagesPerCustomer = 10;

        for ($i = 0; $i < $customerCount; $i++) {
            $customer = User::factory()->create([
                'role_id' => Role::where('name', 'customer')->first()->id
            ]);

            Package::factory()->count($packagesPerCustomer)->create([
                'user_id' => $customer->id,
                'status' => PackageStatus::READY
            ]);
        }

        // Create some consolidated packages
        $consolidatedCount = 5;
        for ($i = 0; $i < $consolidatedCount; $i++) {
            $customer = User::skip($i)->first();
            $packages = Package::where('user_id', $customer->id)->take(5)->get();

            $this->consolidationService->consolidatePackages(
                $packages->pluck('id')->toArray(),
                $this->adminUser
            );
        }

        // Test query performance
        DB::enableQueryLog();

        $startTime = microtime(true);

        // Test consolidated package queries
        $consolidatedPackages = ConsolidatedPackage::with(['packages', 'customer'])
            ->where('is_active', true)
            ->get();

        $queryTime = microtime(true) - $startTime;
        $queryCount = count(DB::getQueryLog());

        $this->assertLessThan(1.0, $queryTime, 
            "Consolidated package query took {$queryTime} seconds, which exceeds the 1-second threshold");
        $this->assertLessThan(10, $queryCount,
            "Query executed {$queryCount} times, which exceeds the 10-query threshold");

        DB::disableQueryLog();

        // Test search performance
        DB::enableQueryLog();
        $startTime = microtime(true);

        $searchResults = Package::where('tracking_number', 'like', '%TEST%')
            ->orWhereHas('consolidatedPackage', function ($query) {
                $query->where('consolidated_tracking_number', 'like', '%CONS%');
            })
            ->with('consolidatedPackage')
            ->get();

        $searchTime = microtime(true) - $startTime;
        $searchQueryCount = count(DB::getQueryLog());

        $this->assertLessThan(2.0, $searchTime,
            "Search query took {$searchTime} seconds, which exceeds the 2-second threshold");

        DB::disableQueryLog();

        Log::info('Database performance test completed', [
            'consolidated_query_time' => $queryTime,
            'consolidated_query_count' => $queryCount,
            'search_time' => $searchTime,
            'search_query_count' => $searchQueryCount
        ]);
    }

    /** @test */
    public function memory_usage_during_large_operations()
    {
        $initialMemory = memory_get_usage(true);

        // Create large dataset
        $packageCount = 100;
        $customer = User::factory()->create([
            'role_id' => Role::where('name', 'customer')->first()->id,
            'account_balance' => 5000.00
        ]);

        $packages = Package::factory()->count($packageCount)->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'weight' => 2.5,
            'freight_price' => 30.00
        ]);

        $afterCreationMemory = memory_get_usage(true);

        $this->actingAs($this->adminUser);

        // Consolidate packages
        $result = $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->adminUser
        );

        $afterConsolidationMemory = memory_get_usage(true);

        $this->assertTrue($result['success']);

        $consolidatedPackage = $result['consolidated_package'];

        // Distribute packages
        $this->distributionService->distributeConsolidatedPackages(
            $consolidatedPackage,
            $consolidatedPackage->total_freight_price,
            $this->adminUser
        );

        $finalMemory = memory_get_usage(true);

        // Memory usage assertions
        $creationMemoryIncrease = $afterCreationMemory - $initialMemory;
        $consolidationMemoryIncrease = $afterConsolidationMemory - $afterCreationMemory;
        $distributionMemoryIncrease = $finalMemory - $afterConsolidationMemory;

        $this->assertLessThan(50 * 1024 * 1024, $creationMemoryIncrease, // 50MB
            "Package creation used {$creationMemoryIncrease} bytes, which exceeds 50MB");
        $this->assertLessThan(20 * 1024 * 1024, $consolidationMemoryIncrease, // 20MB
            "Consolidation used {$consolidationMemoryIncrease} bytes, which exceeds 20MB");
        $this->assertLessThan(10 * 1024 * 1024, $distributionMemoryIncrease, // 10MB
            "Distribution used {$distributionMemoryIncrease} bytes, which exceeds 10MB");

        Log::info('Memory usage test completed', [
            'package_count' => $packageCount,
            'creation_memory_mb' => round($creationMemoryIncrease / 1024 / 1024, 2),
            'consolidation_memory_mb' => round($consolidationMemoryIncrease / 1024 / 1024, 2),
            'distribution_memory_mb' => round($distributionMemoryIncrease / 1024 / 1024, 2),
            'total_memory_mb' => round($finalMemory / 1024 / 1024, 2)
        ]);
    }

    /** @test */
    public function consolidation_history_performance_with_large_datasets()
    {
        // Create customer with many consolidations
        $customer = User::factory()->create([
            'role_id' => Role::where('name', 'customer')->first()->id
        ]);

        $consolidationCount = 20;
        $consolidatedPackages = [];

        // Create multiple consolidations with history
        for ($i = 0; $i < $consolidationCount; $i++) {
            $packages = Package::factory()->count(5)->create([
                'user_id' => $customer->id,
                'status' => PackageStatus::READY
            ]);

            $result = $this->consolidationService->consolidatePackages(
                $packages->pluck('id')->toArray(),
                $this->adminUser,
                ['notes' => "History test consolidation {$i}"]
            );

            $consolidatedPackage = $result['consolidated_package'];
            $consolidatedPackages[] = $consolidatedPackage;

            // Create history by updating status multiple times
            $statuses = [PackageStatus::SHIPPED, PackageStatus::IN_TRANSIT, PackageStatus::READY_FOR_PICKUP];
            foreach ($statuses as $status) {
                $this->consolidationService->updateConsolidatedStatus(
                    $consolidatedPackage,
                    $status,
                    $this->adminUser
                );
            }
        }

        // Test history retrieval performance
        $startTime = microtime(true);

        foreach ($consolidatedPackages as $consolidatedPackage) {
            $history = $this->consolidationService->getConsolidationHistory(
                $consolidatedPackage,
                $this->adminUser
            );
            $this->assertGreaterThan(0, $history->count());
        }

        $historyTime = microtime(true) - $startTime;

        $this->assertLessThan(3.0, $historyTime,
            "History retrieval for {$consolidationCount} consolidations took {$historyTime} seconds, which exceeds the 3-second threshold");

        // Test history summary performance
        $startTime = microtime(true);

        $summary = $this->consolidationService->getConsolidationStatistics($this->adminUser);

        $summaryTime = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $summaryTime,
            "History summary took {$summaryTime} seconds, which exceeds the 1-second threshold");

        $this->assertArrayHasKey('total_consolidations', $summary);
        $this->assertEquals($consolidationCount, $summary['total_consolidations']);

        Log::info('History performance test completed', [
            'consolidation_count' => $consolidationCount,
            'history_retrieval_time' => $historyTime,
            'summary_time' => $summaryTime
        ]);
    }

    /** @test */
    public function consolidation_search_performance_with_large_datasets()
    {
        // Create large dataset with searchable content
        $customerCount = 10;
        $packagesPerCustomer = 20;

        for ($i = 0; $i < $customerCount; $i++) {
            $customer = User::factory()->create([
                'role_id' => Role::where('name', 'customer')->first()->id
            ]);

            for ($j = 0; $j < $packagesPerCustomer; $j++) {
                Package::factory()->create([
                    'user_id' => $customer->id,
                    'tracking_number' => "PERF-{$i}-{$j}-" . str_pad($j, 3, '0', STR_PAD_LEFT),
                    'status' => PackageStatus::READY,
                    'description' => "Performance test package {$i}-{$j}"
                ]);
            }

            // Consolidate some packages
            if ($i % 2 === 0) {
                $packages = Package::where('user_id', $customer->id)->take(10)->get();
                $this->consolidationService->consolidatePackages(
                    $packages->pluck('id')->toArray(),
                    $this->adminUser
                );
            }
        }

        // Test search performance
        $searchTerms = ['PERF-1', 'PERF-5', 'Performance test'];

        foreach ($searchTerms as $searchTerm) {
            $startTime = microtime(true);

            $results = Package::where('tracking_number', 'like', "%{$searchTerm}%")
                ->orWhere('description', 'like', "%{$searchTerm}%")
                ->orWhereHas('consolidatedPackage', function ($query) use ($searchTerm) {
                    $query->where('consolidated_tracking_number', 'like', "%{$searchTerm}%");
                })
                ->with('consolidatedPackage')
                ->get();

            $searchTime = microtime(true) - $startTime;

            $this->assertLessThan(1.0, $searchTime,
                "Search for '{$searchTerm}' took {$searchTime} seconds, which exceeds the 1-second threshold");

            Log::info("Search performance for '{$searchTerm}'", [
                'search_time' => $searchTime,
                'results_count' => $results->count()
            ]);
        }
    }
}