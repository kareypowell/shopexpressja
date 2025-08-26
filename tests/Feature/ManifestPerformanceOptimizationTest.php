<?php

namespace Tests\Feature;

use App\Models\Manifest;
use App\Models\Package;
use App\Models\User;
use App\Services\ManifestSummaryCacheService;
use App\Services\ManifestQueryOptimizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManifestPerformanceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Manifest $manifest;
    protected $shipper;
    protected $faker;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->faker = \Faker\Factory::create();
        
        $this->user = User::factory()->create(['role_id' => 1]);
        $this->shipper = \App\Models\Shipper::factory()->create();
        $this->manifest = Manifest::factory()->create([
            'type' => 'air',
            'flight_number' => 'AA123',
            'flight_destination' => 'JFK'
        ]);
    }

    /** @test */
    public function it_caches_manifest_summary_calculations()
    {
        // Create packages for the manifest
        Package::factory()->count(10)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'weight' => 10.5,
            'freight_price' => 100.00
        ]);

        $cacheService = app(ManifestSummaryCacheService::class);
        
        // Clear any existing cache
        Cache::flush();
        
        // First call should calculate and cache
        $startTime = microtime(true);
        $summary1 = $cacheService->getCachedSummary($this->manifest);
        $firstCallTime = microtime(true) - $startTime;
        
        // Second call should be from cache and faster
        $startTime = microtime(true);
        $summary2 = $cacheService->getCachedSummary($this->manifest);
        $secondCallTime = microtime(true) - $startTime;
        
        // Verify results are identical
        $this->assertEquals($summary1, $summary2);
        
        // Second call should be significantly faster (at least 50% faster)
        $this->assertLessThan($firstCallTime * 0.5, $secondCallTime);
        
        // Verify cache key exists
        $this->assertTrue($cacheService->isCacheAvailable());
    }

    /** @test */
    public function it_invalidates_cache_when_manifest_changes()
    {
        Package::factory()->count(5)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'weight' => 10.0
        ]);

        $cacheService = app(ManifestSummaryCacheService::class);
        
        // Get initial cached summary
        $initialSummary = $cacheService->getCachedSummary($this->manifest);
        
        // Add more packages
        Package::factory()->count(5)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'weight' => 15.0
        ]);
        
        // Update manifest to trigger cache invalidation
        $this->manifest->touch();
        $this->manifest->refresh(); // Refresh to get updated package count
        
        // Get summary again - should be recalculated
        $updatedSummary = $cacheService->getCachedSummary($this->manifest);
        
        // Package count should be different
        $this->assertNotEquals(
            $initialSummary['package_count'],
            $updatedSummary['package_count']
        );
        
        $this->assertEquals(10, $updatedSummary['package_count']);
    }

    /** @test */
    public function it_optimizes_database_queries_for_weight_calculations()
    {
        // Create packages with weight data
        Package::factory()->count(20)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'weight' => $this->faker->randomFloat(2, 1, 50),
            'freight_price' => $this->faker->randomFloat(2, 50, 500)
        ]);

        $queryService = app(ManifestQueryOptimizationService::class);
        
        // Enable query logging
        DB::enableQueryLog();
        
        // Get optimized summary stats
        $stats = $queryService->getOptimizedSummaryStats($this->manifest);
        
        $queries = DB::getQueryLog();
        
        // Should use only one optimized query
        $this->assertCount(1, $queries);
        
        // Verify the query uses the proper indexes
        $query = $queries[0]['query'];
        $this->assertStringContainsString('manifest_id', $query);
        $this->assertStringContainsString('SUM', $query);
        $this->assertStringContainsString('COUNT', $query);
        
        // Verify results
        $this->assertEquals(20, $stats['total_packages']);
        $this->assertGreaterThan(0, $stats['total_weight']);
        $this->assertEquals(20, $stats['packages_with_weight']);
    }

    /** @test */
    public function it_handles_large_datasets_efficiently()
    {
        // Create a large number of packages
        $packageCount = 1000;
        
        // Create an office for the packages
        $office = \App\Models\Office::factory()->create();
        
        $packages = [];
        for ($i = 0; $i < $packageCount; $i++) {
            $packages[] = [
                'manifest_id' => $this->manifest->id,
                'user_id' => $this->user->id,
                'shipper_id' => $this->shipper->id,
                'office_id' => $office->id,
                'tracking_number' => 'PKG' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'description' => 'Test package ' . $i,
                'weight' => $this->faker->randomFloat(2, 1, 100),
                'cubic_feet' => $this->faker->randomFloat(3, 0.1, 10),
                'freight_price' => $this->faker->randomFloat(2, 10, 1000),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // Batch insert for performance
        Package::insert($packages);
        
        $queryService = app(ManifestQueryOptimizationService::class);
        
        // Measure performance
        $startTime = microtime(true);
        $stats = $queryService->getOptimizedSummaryStats($this->manifest);
        $executionTime = microtime(true) - $startTime;
        
        // Should complete within reasonable time (less than 1 second)
        $this->assertLessThan(1.0, $executionTime);
        
        // Verify correct results
        $this->assertEquals($packageCount, $stats['total_packages']);
        $this->assertGreaterThan(0, $stats['total_weight']);
        $this->assertGreaterThan(0, $stats['total_value']);
    }

    /** @test */
    public function it_optimizes_individual_and_consolidated_package_counts()
    {
        // Create individual packages
        Package::factory()->count(15)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'consolidated_package_id' => null
        ]);
        
        // Create consolidated packages (create the consolidated packages first)
        $consolidatedPackages = \App\Models\ConsolidatedPackage::factory()->count(3)->create();
        
        foreach ($consolidatedPackages as $consolidatedPackage) {
            Package::factory()->count(5)->create([
                'manifest_id' => $this->manifest->id,
                'user_id' => $this->user->id,
                'consolidated_package_id' => $consolidatedPackage->id
            ]);
        }

        $queryService = app(ManifestQueryOptimizationService::class);
        
        // Enable query logging
        DB::enableQueryLog();
        
        $individualCount = $queryService->getIndividualPackagesCount($this->manifest);
        $consolidatedCount = $queryService->getConsolidatedPackagesCount($this->manifest);
        
        $queries = DB::getQueryLog();
        
        // Should use optimized queries with proper indexes
        $this->assertCount(2, $queries);
        
        // Verify results
        $this->assertEquals(15, $individualCount);
        $this->assertEquals(3, $consolidatedCount);
        
        // Verify queries use indexes
        foreach ($queries as $query) {
            $this->assertStringContainsString('manifest_id', $query['query']);
            $this->assertStringContainsString('consolidated_package_id', $query['query']);
        }
    }

    /** @test */
    public function it_preloads_related_data_to_prevent_n_plus_one_queries()
    {
        // Create packages with relationships
        $consolidatedPackage = \App\Models\ConsolidatedPackage::factory()->create();
        
        Package::factory()->count(10)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'consolidated_package_id' => $consolidatedPackage->id
        ]);

        $queryService = app(ManifestQueryOptimizationService::class);
        
        // Enable query logging
        DB::enableQueryLog();
        
        // Preload manifest data
        $preloadedManifest = $queryService->preloadManifestData($this->manifest);
        
        // Access related data (should not trigger additional queries)
        $packages = $preloadedManifest->packages;
        foreach ($packages as $package) {
            $consolidatedPackage = $package->consolidatedPackage;
        }
        
        $queries = DB::getQueryLog();
        
        // Should use minimal queries due to eager loading
        $this->assertLessThanOrEqual(3, count($queries));
        
        // Verify data is loaded
        $this->assertCount(10, $packages);
        $this->assertTrue($preloadedManifest->relationLoaded('packages'));
    }

    /** @test */
    public function it_monitors_query_performance()
    {
        Package::factory()->count(50)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'weight' => $this->faker->randomFloat(2, 1, 50)
        ]);

        $queryService = app(ManifestQueryOptimizationService::class);
        
        // Enable query logging
        DB::enableQueryLog();
        
        // Perform operations
        $queryService->getOptimizedSummaryStats($this->manifest);
        $queryService->getPackagesForWeightCalculation($this->manifest);
        
        // Get query statistics
        $stats = $queryService->getQueryStatistics();
        
        $this->assertArrayHasKey('total_queries', $stats);
        $this->assertArrayHasKey('queries', $stats);
        $this->assertArrayHasKey('slow_queries', $stats);
        
        $this->assertGreaterThan(0, $stats['total_queries']);
        $this->assertIsArray($stats['queries']);
        $this->assertIsArray($stats['slow_queries']);
    }

    /** @test */
    public function it_handles_cache_failures_gracefully()
    {
        Package::factory()->count(5)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'weight' => 10.0
        ]);

        // Mock cache failure
        Cache::shouldReceive('tags')
            ->andThrow(new \Exception('Cache connection failed'));

        $cacheService = app(ManifestSummaryCacheService::class);
        
        // Should fallback to direct calculation without throwing exception
        $summary = $cacheService->getCachedSummary($this->manifest);
        
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('package_count', $summary);
        $this->assertEquals(5, $summary['package_count']);
    }

    /** @test */
    public function it_validates_performance_improvements_with_indexes()
    {
        // Run migration to add indexes
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_08_22_000001_add_manifest_performance_indexes.php']);
        
        // Create test data
        Package::factory()->count(100)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'weight' => $this->faker->randomFloat(2, 1, 50),
            'cubic_feet' => $this->faker->randomFloat(3, 0.1, 10),
            'freight_price' => $this->faker->randomFloat(2, 10, 500)
        ]);

        $queryService = app(ManifestQueryOptimizationService::class);
        
        // Measure query performance
        $startTime = microtime(true);
        $stats = $queryService->getOptimizedSummaryStats($this->manifest);
        $executionTime = microtime(true) - $startTime;
        
        // With indexes, should be very fast even with 100 packages
        $this->assertLessThan(0.1, $executionTime); // Less than 100ms
        
        // Verify correct results
        $this->assertEquals(100, $stats['total_packages']);
        $this->assertGreaterThan(0, $stats['total_weight']);
    }

    /** @test */
    public function it_measures_memory_usage_during_operations()
    {
        if (!function_exists('memory_get_usage')) {
            $this->markTestSkipped('Memory functions not available');
        }

        // Create large dataset
        Package::factory()->count(500)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'weight' => $this->faker->randomFloat(2, 1, 50),
            'cubic_feet' => $this->faker->randomFloat(3, 0.1, 10)
        ]);

        $initialMemory = memory_get_usage(true);
        
        $cacheService = app(ManifestSummaryCacheService::class);
        $queryService = app(ManifestQueryOptimizationService::class);
        
        // Perform memory-intensive operations
        $summary = $cacheService->getCachedSummary($this->manifest);
        $stats = $queryService->getOptimizedSummaryStats($this->manifest);
        $packages = $queryService->getPackagesForWeightCalculation($this->manifest);
        
        $peakMemory = memory_get_peak_usage(true);
        $memoryIncrease = $peakMemory - $initialMemory;
        
        // Memory increase should be reasonable (less than 50MB for 500 packages)
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease);
        
        // Verify operations completed successfully
        $this->assertIsArray($summary);
        $this->assertIsArray($stats);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $packages);
    }
}