<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\ConsolidationHistory;
use App\Services\PackageConsolidationService;
use App\Services\ConsolidationCacheService;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConsolidationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected PackageConsolidationService $consolidationService;
    protected ConsolidationCacheService $cacheService;
    protected User $admin;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->consolidationService = app(PackageConsolidationService::class);
        $this->cacheService = app(ConsolidationCacheService::class);
        
        // Create test users
        $this->admin = User::factory()->create(['role_id' => 1]);
        $this->customer = User::factory()->create(['role_id' => 2]);
    }

    /**
     * Test consolidation performance with large dataset
     */
    public function test_consolidation_performance_with_large_dataset()
    {
        // Create a large number of packages for consolidation
        $packageCount = 100;
        $packages = Package::factory()
            ->count($packageCount)
            ->create([
                'user_id' => $this->customer->id,
                'status' => PackageStatus::READY,
                'is_consolidated' => false,
                'consolidated_package_id' => null,
            ]);

        $packageIds = $packages->pluck('id')->toArray();

        // Measure consolidation time
        $startTime = microtime(true);
        
        $result = $this->consolidationService->consolidatePackages(
            $packageIds,
            $this->admin,
            ['notes' => 'Performance test consolidation']
        );
        
        $consolidationTime = microtime(true) - $startTime;

        // Assert consolidation was successful
        $this->assertTrue($result['success']);
        $this->assertInstanceOf(ConsolidatedPackage::class, $result['consolidated_package']);

        // Assert performance is acceptable (should complete within 5 seconds)
        $this->assertLessThan(5.0, $consolidationTime, 
            "Consolidation of {$packageCount} packages took {$consolidationTime} seconds, which exceeds the 5-second threshold");

        // Verify all packages were consolidated
        $consolidatedPackage = $result['consolidated_package'];
        $this->assertEquals($packageCount, $consolidatedPackage->packages()->count());

        // Test query performance for loading consolidated package with packages
        $startTime = microtime(true);
        
        $loadedPackage = ConsolidatedPackage::with('packagesWithDetails')
            ->find($consolidatedPackage->id);
        
        $loadTime = microtime(true) - $startTime;

        // Assert loading time is acceptable (should complete within 1 second)
        $this->assertLessThan(1.0, $loadTime,
            "Loading consolidated package with {$packageCount} packages took {$loadTime} seconds, which exceeds the 1-second threshold");

        Log::info('Consolidation performance test completed', [
            'package_count' => $packageCount,
            'consolidation_time' => $consolidationTime,
            'load_time' => $loadTime,
        ]);
    }

    /**
     * Test search performance with large dataset
     */
    public function test_search_performance_with_large_dataset()
    {
        // Create multiple consolidated packages with many individual packages
        $consolidationCount = 20;
        $packagesPerConsolidation = 50;
        
        for ($i = 0; $i < $consolidationCount; $i++) {
            $packages = Package::factory()
                ->count($packagesPerConsolidation)
                ->create([
                    'user_id' => $this->customer->id,
                    'status' => PackageStatus::READY,
                    'tracking_number' => 'TEST-' . str_pad($i, 3, '0', STR_PAD_LEFT) . '-' . \Illuminate\Support\Str::random(4),
                ]);

            $this->consolidationService->consolidatePackages(
                $packages->pluck('id')->toArray(),
                $this->admin
            );
        }

        // Test search performance
        $searchTerms = ['TEST-001', 'TEST-010', 'TEST-020', 'nonexistent'];
        
        foreach ($searchTerms as $searchTerm) {
            $startTime = microtime(true);
            
            $results = ConsolidatedPackage::searchOptimized($searchTerm)
                ->forCustomer($this->customer->id)
                ->limit(10)
                ->get();
            
            $searchTime = microtime(true) - $startTime;

            // Assert search time is acceptable (should complete within 0.5 seconds)
            $this->assertLessThan(0.5, $searchTime,
                "Search for '{$searchTerm}' took {$searchTime} seconds, which exceeds the 0.5-second threshold");

            Log::info('Search performance test', [
                'search_term' => $searchTerm,
                'search_time' => $searchTime,
                'results_count' => $results->count(),
            ]);
        }
    }

    /**
     * Test cache performance and effectiveness
     */
    public function test_cache_performance_and_effectiveness()
    {
        // Create test data
        $packages = Package::factory()
            ->count(20)
            ->create([
                'user_id' => $this->customer->id,
                'status' => PackageStatus::READY,
            ]);

        $result = $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->admin
        );

        $consolidatedPackage = $result['consolidated_package'];

        // Clear cache to ensure we're testing from cold state
        Cache::flush();

        // Test first call (cache miss)
        $startTime = microtime(true);
        $totals1 = $this->cacheService->getConsolidatedTotals($consolidatedPackage->id);
        $firstCallTime = microtime(true) - $startTime;

        // Test second call (cache hit)
        $startTime = microtime(true);
        $totals2 = $this->cacheService->getConsolidatedTotals($consolidatedPackage->id);
        $secondCallTime = microtime(true) - $startTime;

        // Assert cache hit is significantly faster
        $this->assertLessThan($firstCallTime / 2, $secondCallTime,
            "Cache hit should be at least 2x faster than cache miss");

        // Assert data consistency
        $this->assertEquals($totals1, $totals2);

        // Test customer consolidations cache
        $startTime = microtime(true);
        $consolidations1 = $this->cacheService->getCustomerConsolidations($this->customer->id);
        $firstConsolidationsTime = microtime(true) - $startTime;

        $startTime = microtime(true);
        $consolidations2 = $this->cacheService->getCustomerConsolidations($this->customer->id);
        $secondConsolidationsTime = microtime(true) - $startTime;

        // Assert cache effectiveness
        $this->assertLessThan($firstConsolidationsTime / 2, $secondConsolidationsTime);
        $this->assertEquals($consolidations1->count(), $consolidations2->count());

        Log::info('Cache performance test completed', [
            'totals_first_call' => $firstCallTime,
            'totals_second_call' => $secondCallTime,
            'consolidations_first_call' => $firstConsolidationsTime,
            'consolidations_second_call' => $secondConsolidationsTime,
            'cache_speedup_totals' => $firstCallTime / $secondCallTime,
            'cache_speedup_consolidations' => $firstConsolidationsTime / $secondConsolidationsTime,
        ]);
    }

    /**
     * Test database query performance with indexes
     */
    public function test_database_query_performance_with_indexes()
    {
        // Create large dataset
        $customerCount = 10;
        $consolidationsPerCustomer = 10;
        $packagesPerConsolidation = 20;

        $customers = User::factory()->count($customerCount)->create(['role_id' => 2]);

        foreach ($customers as $customer) {
            for ($i = 0; $i < $consolidationsPerCustomer; $i++) {
                $packages = Package::factory()
                    ->count($packagesPerConsolidation)
                    ->create([
                        'user_id' => $customer->id,
                        'status' => PackageStatus::READY,
                    ]);

                $this->consolidationService->consolidatePackages(
                    $packages->pluck('id')->toArray(),
                    $this->admin
                );
            }
        }

        // Test various query patterns that should benefit from indexes
        $queryTests = [
            'customer_active_consolidations' => function () use ($customers) {
                return ConsolidatedPackage::where('customer_id', $customers->first()->id)
                    ->where('is_active', true)
                    ->get();
            },
            'status_based_filtering' => function () {
                return ConsolidatedPackage::where('status', PackageStatus::READY)
                    ->where('is_active', true)
                    ->get();
            },
            'consolidated_packages_with_packages' => function () use ($customers) {
                return Package::where('user_id', $customers->first()->id)
                    ->where('is_consolidated', true)
                    ->with('consolidatedPackage')
                    ->get();
            },
            'consolidation_history_queries' => function () {
                return ConsolidationHistory::with('consolidatedPackage')
                    ->where('action', 'consolidated')
                    ->orderBy('performed_at', 'desc')
                    ->limit(50)
                    ->get();
            },
        ];

        foreach ($queryTests as $testName => $queryFunction) {
            // Enable query logging
            DB::enableQueryLog();
            
            $startTime = microtime(true);
            $results = $queryFunction();
            $queryTime = microtime(true) - $startTime;
            
            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            // Assert query performance is acceptable
            $this->assertLessThan(1.0, $queryTime,
                "Query test '{$testName}' took {$queryTime} seconds, which exceeds the 1-second threshold");

            Log::info('Database query performance test', [
                'test_name' => $testName,
                'query_time' => $queryTime,
                'query_count' => count($queries),
                'results_count' => is_countable($results) ? count($results) : 'N/A',
            ]);
        }
    }

    /**
     * Test memory usage during large operations
     */
    public function test_memory_usage_during_large_operations()
    {
        $initialMemory = memory_get_usage(true);

        // Create large dataset
        $packageCount = 200;
        $packages = Package::factory()
            ->count($packageCount)
            ->create([
                'user_id' => $this->customer->id,
                'status' => PackageStatus::READY,
            ]);

        $memoryAfterCreation = memory_get_usage(true);

        // Perform consolidation
        $result = $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->admin
        );

        $memoryAfterConsolidation = memory_get_usage(true);

        // Load consolidated package with all relationships
        $consolidatedPackage = ConsolidatedPackage::with([
            'packagesWithDetails',
            'customer',
            'createdBy',
            'history'
        ])->find($result['consolidated_package']->id);

        $memoryAfterLoading = memory_get_usage(true);

        // Calculate memory usage
        $creationMemory = $memoryAfterCreation - $initialMemory;
        $consolidationMemory = $memoryAfterConsolidation - $memoryAfterCreation;
        $loadingMemory = $memoryAfterLoading - $memoryAfterConsolidation;
        $totalMemory = $memoryAfterLoading - $initialMemory;

        // Assert memory usage is reasonable (less than 50MB for this test)
        $maxMemoryMB = 50;
        $totalMemoryMB = $totalMemory / 1024 / 1024;
        
        $this->assertLessThan($maxMemoryMB, $totalMemoryMB,
            "Total memory usage of {$totalMemoryMB}MB exceeds the {$maxMemoryMB}MB threshold");

        Log::info('Memory usage test completed', [
            'package_count' => $packageCount,
            'initial_memory_mb' => $initialMemory / 1024 / 1024,
            'creation_memory_mb' => $creationMemory / 1024 / 1024,
            'consolidation_memory_mb' => $consolidationMemory / 1024 / 1024,
            'loading_memory_mb' => $loadingMemory / 1024 / 1024,
            'total_memory_mb' => $totalMemoryMB,
        ]);
    }

    /**
     * Test concurrent consolidation operations
     */
    public function test_concurrent_consolidation_operations()
    {
        // Create multiple sets of packages for different customers
        $customerCount = 5;
        $packagesPerCustomer = 20;
        
        $customers = User::factory()->count($customerCount)->create(['role_id' => 2]);
        $packageSets = [];

        foreach ($customers as $customer) {
            $packages = Package::factory()
                ->count($packagesPerCustomer)
                ->create([
                    'user_id' => $customer->id,
                    'status' => PackageStatus::READY,
                ]);
            
            $packageSets[] = [
                'customer' => $customer,
                'package_ids' => $packages->pluck('id')->toArray(),
            ];
        }

        // Simulate concurrent consolidations
        $startTime = microtime(true);
        $results = [];

        foreach ($packageSets as $packageSet) {
            $result = $this->consolidationService->consolidatePackages(
                $packageSet['package_ids'],
                $this->admin,
                ['notes' => 'Concurrent test consolidation']
            );
            
            $results[] = $result;
        }

        $totalTime = microtime(true) - $startTime;

        // Assert all consolidations were successful
        foreach ($results as $result) {
            $this->assertTrue($result['success']);
        }

        // Assert total time is reasonable
        $this->assertLessThan(10.0, $totalTime,
            "Concurrent consolidation of {$customerCount} sets took {$totalTime} seconds, which exceeds the 10-second threshold");

        // Verify no data corruption occurred
        $totalConsolidatedPackages = ConsolidatedPackage::count();
        $this->assertEquals($customerCount, $totalConsolidatedPackages);

        $totalPackagesInConsolidations = Package::where('is_consolidated', true)->count();
        $this->assertEquals($customerCount * $packagesPerCustomer, $totalPackagesInConsolidations);

        Log::info('Concurrent consolidation test completed', [
            'customer_count' => $customerCount,
            'packages_per_customer' => $packagesPerCustomer,
            'total_time' => $totalTime,
            'average_time_per_consolidation' => $totalTime / $customerCount,
        ]);
    }

    /**
     * Test cache invalidation performance
     */
    public function test_cache_invalidation_performance()
    {
        // Create test data and populate cache
        $packages = Package::factory()
            ->count(50)
            ->create([
                'user_id' => $this->customer->id,
                'status' => PackageStatus::READY,
            ]);

        $result = $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->admin
        );

        $consolidatedPackage = $result['consolidated_package'];

        // Populate various caches
        $this->cacheService->getConsolidatedTotals($consolidatedPackage->id);
        $this->cacheService->getCustomerConsolidations($this->customer->id);
        $this->cacheService->getAvailablePackagesForConsolidation($this->customer->id);
        $this->cacheService->getConsolidationStats();

        // Test cache invalidation performance
        $startTime = microtime(true);
        $this->cacheService->invalidateAllForCustomer($this->customer->id);
        $invalidationTime = microtime(true) - $startTime;

        // Assert invalidation is fast
        $this->assertLessThan(0.1, $invalidationTime,
            "Cache invalidation took {$invalidationTime} seconds, which exceeds the 0.1-second threshold");

        // Test full cache invalidation
        $startTime = microtime(true);
        $this->cacheService->invalidateAll();
        $fullInvalidationTime = microtime(true) - $startTime;

        $this->assertLessThan(0.2, $fullInvalidationTime,
            "Full cache invalidation took {$fullInvalidationTime} seconds, which exceeds the 0.2-second threshold");

        Log::info('Cache invalidation performance test completed', [
            'customer_invalidation_time' => $invalidationTime,
            'full_invalidation_time' => $fullInvalidationTime,
        ]);
    }
}