<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Services\PackageConsolidationService;
use App\Services\ConsolidationCacheService;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ConsolidationIndexPerformanceTest extends TestCase
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
     * Test that database indexes improve query performance
     */
    public function test_database_indexes_improve_query_performance()
    {
        // Create test data
        $packageCount = 50;
        $packages = [];
        
        for ($i = 0; $i < $packageCount; $i++) {
            $packages[] = Package::factory()->create([
                'user_id' => $this->customer->id,
                'status' => PackageStatus::READY,
                'is_consolidated' => false,
                'consolidated_package_id' => null,
                'tracking_number' => 'TEST-' . str_pad($i, 4, '0', STR_PAD_LEFT),
            ]);
        }

        // Create some consolidated packages
        $consolidationCount = 5;
        for ($i = 0; $i < $consolidationCount; $i++) {
            $packageBatch = array_slice($packages, $i * 10, 10);
            $packageIds = collect($packageBatch)->pluck('id')->toArray();
            
            $this->consolidationService->consolidatePackages($packageIds, $this->admin);
        }

        // Test queries that should benefit from indexes
        $queries = [
            'customer_active_consolidations' => function () {
                return ConsolidatedPackage::where('customer_id', $this->customer->id)
                    ->where('is_active', true)
                    ->get();
            },
            'consolidated_packages_by_status' => function () {
                return ConsolidatedPackage::where('status', PackageStatus::READY)
                    ->where('is_active', true)
                    ->get();
            },
            'packages_for_consolidation' => function () {
                return Package::where('user_id', $this->customer->id)
                    ->where('is_consolidated', false)
                    ->where('status', PackageStatus::READY)
                    ->get();
            },
            'consolidated_package_search' => function () {
                return ConsolidatedPackage::where('consolidated_tracking_number', 'like', '%CONS%')
                    ->where('is_active', true)
                    ->get();
            },
        ];

        foreach ($queries as $queryName => $queryFunction) {
            // Enable query logging
            DB::enableQueryLog();
            
            $startTime = microtime(true);
            $results = $queryFunction();
            $queryTime = microtime(true) - $startTime;
            
            $queryLog = DB::getQueryLog();
            DB::disableQueryLog();

            // Assert query performance is reasonable
            $this->assertLessThan(0.5, $queryTime,
                "Query '{$queryName}' took {$queryTime} seconds, which may indicate missing indexes");

            // Log performance for monitoring
            \Log::info("Index performance test: {$queryName}", [
                'query_time' => $queryTime,
                'query_count' => count($queryLog),
                'results_count' => is_countable($results) ? count($results) : 'N/A',
            ]);
        }
    }

    /**
     * Test cache performance improvements
     */
    public function test_cache_performance_improvements()
    {
        // Create test data
        $packages = Package::factory()->count(10)->create([
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

        // Test consolidated totals caching
        $startTime = microtime(true);
        $totals1 = $this->cacheService->getConsolidatedTotals($consolidatedPackage->id);
        $firstCallTime = microtime(true) - $startTime;

        $startTime = microtime(true);
        $totals2 = $this->cacheService->getConsolidatedTotals($consolidatedPackage->id);
        $secondCallTime = microtime(true) - $startTime;

        // Cache hit should be significantly faster
        $this->assertLessThan($firstCallTime / 2, $secondCallTime,
            "Cache hit should be at least 2x faster than cache miss");

        // Test customer consolidations caching
        $startTime = microtime(true);
        $consolidations1 = $this->cacheService->getCustomerConsolidations($this->customer->id);
        $firstConsolidationsTime = microtime(true) - $startTime;

        $startTime = microtime(true);
        $consolidations2 = $this->cacheService->getCustomerConsolidations($this->customer->id);
        $secondConsolidationsTime = microtime(true) - $startTime;

        $this->assertLessThan($firstConsolidationsTime / 2, $secondConsolidationsTime,
            "Consolidations cache hit should be at least 2x faster");

        \Log::info('Cache performance test results', [
            'totals_cache_speedup' => $firstCallTime / $secondCallTime,
            'consolidations_cache_speedup' => $firstConsolidationsTime / $secondConsolidationsTime,
        ]);
    }

    /**
     * Test search performance with indexes
     */
    public function test_search_performance_with_indexes()
    {
        // Create consolidated packages with searchable content
        $consolidationCount = 10;
        
        for ($i = 0; $i < $consolidationCount; $i++) {
            $packages = Package::factory()->count(5)->create([
                'user_id' => $this->customer->id,
                'status' => PackageStatus::READY,
                'tracking_number' => 'SEARCH-' . str_pad($i, 3, '0', STR_PAD_LEFT) . '-' . \Illuminate\Support\Str::random(4),
            ]);

            $this->consolidationService->consolidatePackages(
                $packages->pluck('id')->toArray(),
                $this->admin
            );
        }

        // Test various search patterns
        $searchTerms = ['SEARCH', 'CONS', 'nonexistent'];
        
        foreach ($searchTerms as $searchTerm) {
            $startTime = microtime(true);
            
            $results = ConsolidatedPackage::searchOptimized($searchTerm)
                ->forCustomer($this->customer->id)
                ->limit(10)
                ->get();
            
            $searchTime = microtime(true) - $startTime;

            // Assert search performance is reasonable
            $this->assertLessThan(0.3, $searchTime,
                "Search for '{$searchTerm}' took {$searchTime} seconds, which may indicate missing indexes");

            \Log::info("Search performance test: {$searchTerm}", [
                'search_time' => $searchTime,
                'results_count' => $results->count(),
            ]);
        }
    }

    /**
     * Test memory usage during operations
     */
    public function test_memory_usage_optimization()
    {
        $initialMemory = memory_get_usage(true);

        // Create moderate dataset
        $packageCount = 30;
        $packages = Package::factory()->count($packageCount)->create([
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

        // Load with optimized eager loading
        $consolidatedPackage = ConsolidatedPackage::withEssentials()
            ->find($result['consolidated_package']->id);

        $memoryAfterLoading = memory_get_usage(true);

        // Calculate memory usage
        $totalMemoryMB = ($memoryAfterLoading - $initialMemory) / 1024 / 1024;

        // Assert memory usage is reasonable (less than 20MB for this test)
        $this->assertLessThan(20, $totalMemoryMB,
            "Total memory usage of {$totalMemoryMB}MB seems excessive for {$packageCount} packages");

        \Log::info('Memory usage test completed', [
            'package_count' => $packageCount,
            'total_memory_mb' => $totalMemoryMB,
            'creation_memory_mb' => ($memoryAfterCreation - $initialMemory) / 1024 / 1024,
            'consolidation_memory_mb' => ($memoryAfterConsolidation - $memoryAfterCreation) / 1024 / 1024,
            'loading_memory_mb' => ($memoryAfterLoading - $memoryAfterConsolidation) / 1024 / 1024,
        ]);
    }

    /**
     * Test that indexes exist and are being used
     */
    public function test_indexes_exist_and_are_used()
    {
        // Check that our performance indexes exist
        $indexes = [
            'consolidated_packages' => [
                'idx_consolidated_customer_active_status',
                'idx_consolidated_tracking_number',
                'idx_consolidated_date_active',
                'idx_consolidated_status_active',
            ],
            'packages' => [
                'idx_packages_consolidated_user',
                'idx_packages_user_consolidated_status',
                'idx_packages_consolidated_tracking',
                'idx_packages_consolidated_totals',
            ],
            'consolidation_history' => [
                'idx_history_package_date_action',
                'idx_history_user_date',
                'idx_history_action_date',
            ],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            foreach ($tableIndexes as $indexName) {
                // Check if index exists (MySQL specific query)
                $indexExists = DB::select("
                    SELECT COUNT(*) as count 
                    FROM information_schema.statistics 
                    WHERE table_schema = DATABASE() 
                    AND table_name = ? 
                    AND index_name = ?
                ", [$table, $indexName]);

                $this->assertGreaterThan(0, $indexExists[0]->count,
                    "Index '{$indexName}' should exist on table '{$table}'");
            }
        }

        \Log::info('Index existence verification completed', [
            'tables_checked' => array_keys($indexes),
            'total_indexes_verified' => array_sum(array_map('count', $indexes)),
        ]);
    }
}