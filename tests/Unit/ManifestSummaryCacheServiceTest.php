<?php

namespace Tests\Unit;

use App\Models\Manifest;
use App\Models\Package;
use App\Services\ManifestSummaryCacheService;
use App\Services\ManifestSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Mockery;

class ManifestSummaryCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ManifestSummaryCacheService $cacheService;
    protected Manifest $manifest;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->manifest = Manifest::factory()->create([
            'type' => 'air',
            'flight_number' => 'AA123'
        ]);
        
        $this->cacheService = app(ManifestSummaryCacheService::class);
    }

    /** @test */
    public function it_generates_unique_cache_keys_for_different_manifests()
    {
        $manifest1 = Manifest::factory()->create();
        $manifest2 = Manifest::factory()->create();
        
        $reflection = new \ReflectionClass($this->cacheService);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);
        
        $key1 = $method->invoke($this->cacheService, $manifest1);
        $key2 = $method->invoke($this->cacheService, $manifest2);
        
        $this->assertNotEquals($key1, $key2);
        $this->assertStringContainsString('manifest_summary_full_' . $manifest1->id . '_', $key1);
        $this->assertStringContainsString('manifest_summary_full_' . $manifest2->id . '_', $key2);
    }

    /** @test */
    public function it_includes_package_count_in_cache_key_for_auto_invalidation()
    {
        // Create packages
        Package::factory()->count(3)->create(['manifest_id' => $this->manifest->id]);
        
        $reflection = new \ReflectionClass($this->cacheService);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);
        
        $keyBefore = $method->invoke($this->cacheService, $this->manifest);
        
        // Add more packages
        Package::factory()->count(2)->create(['manifest_id' => $this->manifest->id]);
        
        // Refresh manifest to get updated package count
        $this->manifest->refresh();
        
        $keyAfter = $method->invoke($this->cacheService, $this->manifest);
        
        $this->assertNotEquals($keyBefore, $keyAfter);
    }

    /** @test */
    public function it_caches_summary_data_successfully()
    {
        Package::factory()->count(5)->create([
            'manifest_id' => $this->manifest->id,
            'weight' => 10.0
        ]);

        Cache::flush();
        
        // First call should cache the data
        $summary1 = $this->cacheService->getCachedSummary($this->manifest);
        
        // Verify data is in cache by mocking the summary service
        $mockSummaryService = Mockery::mock(ManifestSummaryService::class);
        $mockSummaryService->shouldNotReceive('getManifestSummary');
        
        // Replace the service in container
        $this->app->instance(ManifestSummaryService::class, $mockSummaryService);
        
        // Create new cache service instance with mocked dependency
        $cacheServiceWithMock = new ManifestSummaryCacheService($mockSummaryService);
        
        // Second call should use cache (mock should not be called)
        $summary2 = $cacheServiceWithMock->getCachedSummary($this->manifest);
        
        $this->assertEquals($summary1, $summary2);
    }

    /** @test */
    public function it_handles_cache_failures_gracefully()
    {
        Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'weight' => 15.0
        ]);

        // Mock cache to throw exception
        Cache::shouldReceive('tags')
            ->andThrow(new \Exception('Cache connection failed'));

        // Should fallback to direct calculation
        $summary = $this->cacheService->getCachedSummary($this->manifest);
        
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('package_count', $summary);
        $this->assertEquals(3, $summary['package_count']);
    }

    /** @test */
    public function it_invalidates_specific_manifest_cache()
    {
        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'weight' => 5.0
        ]);

        // Cache the summary
        $this->cacheService->getCachedSummary($this->manifest);
        
        // Verify cache exists by checking if we can retrieve it
        $reflection = new \ReflectionClass($this->cacheService);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);
        $cacheKey = $method->invoke($this->cacheService, $this->manifest);
        
        $this->assertTrue(Cache::has($cacheKey));
        
        // Invalidate cache
        $this->cacheService->invalidateManifestCache($this->manifest);
        
        // Cache should be cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_warms_up_cache_for_manifest()
    {
        Package::factory()->count(4)->create([
            'manifest_id' => $this->manifest->id,
            'weight' => 8.0,
            'freight_price' => 100.0
        ]);

        Cache::flush();
        
        // Warm up cache
        $this->cacheService->warmUpCache($this->manifest);
        
        // Verify both summary types are cached
        $reflection = new \ReflectionClass($this->cacheService);
        
        $cacheKeyMethod = $reflection->getMethod('generateCacheKey');
        $cacheKeyMethod->setAccessible(true);
        $cacheKey = $cacheKeyMethod->invoke($this->cacheService, $this->manifest);
        
        $displayCacheKeyMethod = $reflection->getMethod('generateDisplayCacheKey');
        $displayCacheKeyMethod->setAccessible(true);
        $displayCacheKey = $displayCacheKeyMethod->invoke($this->cacheService, $this->manifest);
        
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertTrue(Cache::has($displayCacheKey));
    }

    /** @test */
    public function it_provides_cache_statistics()
    {
        $stats = $this->cacheService->getCacheStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('cache_enabled', $stats);
        $this->assertArrayHasKey('cache_driver', $stats);
        $this->assertArrayHasKey('cache_ttl', $stats);
        $this->assertArrayHasKey('cache_tag', $stats);
        
        $this->assertIsBool($stats['cache_enabled']);
        $this->assertIsString($stats['cache_driver']);
        $this->assertIsInt($stats['cache_ttl']);
        $this->assertIsString($stats['cache_tag']);
    }

    /** @test */
    public function it_checks_cache_availability()
    {
        $isAvailable = $this->cacheService->isCacheAvailable();
        
        $this->assertIsBool($isAvailable);
        
        // In testing environment, cache should be available
        $this->assertTrue($isAvailable);
    }

    /** @test */
    public function it_handles_cache_availability_check_failure()
    {
        // Mock cache to throw exception during availability check
        Cache::shouldReceive('put')
            ->andThrow(new \Exception('Cache not available'));
        
        Cache::shouldReceive('get')
            ->andReturn(null);
        
        Cache::shouldReceive('forget')
            ->andReturn(true);
        
        $isAvailable = $this->cacheService->isCacheAvailable();
        
        $this->assertFalse($isAvailable);
    }

    /** @test */
    public function it_caches_display_summary_separately()
    {
        Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'weight' => 12.0,
            'freight_price' => 150.0
        ]);

        Cache::flush();
        
        // Cache display summary
        $displaySummary = $this->cacheService->getCachedDisplaySummary($this->manifest);
        
        // Verify it's cached separately from full summary
        $reflection = new \ReflectionClass($this->cacheService);
        
        $displayCacheKeyMethod = $reflection->getMethod('generateDisplayCacheKey');
        $displayCacheKeyMethod->setAccessible(true);
        $displayCacheKey = $displayCacheKeyMethod->invoke($this->cacheService, $this->manifest);
        
        $this->assertTrue(Cache::has($displayCacheKey));
        $this->assertIsArray($displaySummary);
    }

    /** @test */
    public function it_handles_display_summary_cache_failure()
    {
        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'weight' => 7.0
        ]);

        // Mock cache failure for display summary
        Cache::shouldReceive('tags')
            ->with(['manifest_summaries'])
            ->andThrow(new \Exception('Display cache failed'));

        // Should fallback to direct calculation
        $displaySummary = $this->cacheService->getCachedDisplaySummary($this->manifest);
        
        $this->assertIsArray($displaySummary);
        $this->assertArrayHasKey('manifest_type', $displaySummary);
        $this->assertArrayHasKey('package_count', $displaySummary);
    }

    /** @test */
    public function it_invalidates_all_caches()
    {
        // Create multiple manifests and cache their summaries
        $manifest1 = Manifest::factory()->create();
        $manifest2 = Manifest::factory()->create();
        
        Package::factory()->create(['manifest_id' => $manifest1->id, 'weight' => 5.0]);
        Package::factory()->create(['manifest_id' => $manifest2->id, 'weight' => 10.0]);
        
        // Cache summaries
        $this->cacheService->getCachedSummary($manifest1);
        $this->cacheService->getCachedSummary($manifest2);
        
        // Invalidate all caches
        $this->cacheService->invalidateAllCaches();
        
        // Verify caches are cleared by checking if new calculations are performed
        $mockSummaryService = Mockery::mock(ManifestSummaryService::class);
        $mockSummaryService->shouldReceive('getManifestSummary')
            ->twice()
            ->andReturn(['package_count' => 1]);
        
        $cacheServiceWithMock = new ManifestSummaryCacheService($mockSummaryService);
        
        // These should trigger new calculations (mock expectations)
        $cacheServiceWithMock->getCachedSummary($manifest1);
        $cacheServiceWithMock->getCachedSummary($manifest2);
        
        // If we reach here without exception, the mock expectations were met
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_circuit_breaker_pattern()
    {
        Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'weight' => 10.0
        ]);

        // Mock cache to consistently fail and trigger circuit breaker
        Cache::shouldReceive('tags')
            ->andThrow(new \Exception('Cache connection failed'));
        
        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/cache_circuit_breaker_status/'))
            ->andReturn(null);
        
        Cache::shouldReceive('put')
            ->with(Mockery::pattern('/cache_circuit_breaker_status/'), Mockery::any(), Mockery::any())
            ->andReturn(true);

        Cache::shouldReceive('forget')
            ->andReturn(true);

        // Simulate multiple failures to trigger circuit breaker
        for ($i = 0; $i < 6; $i++) {
            $summary = $this->cacheService->getCachedSummary($this->manifest);
            $this->assertIsArray($summary);
        }

        // Verify circuit breaker functionality by checking health report
        $healthReport = $this->cacheService->getCacheHealthReport();
        $this->assertIsArray($healthReport);
        $this->assertArrayHasKey('circuit_breaker', $healthReport);
    }

    /** @test */
    public function it_provides_emergency_fallback_data()
    {
        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'weight' => 5.0
        ]);

        // Mock both cache and summary service to fail
        Cache::shouldReceive('tags')
            ->andThrow(new \Exception('Cache failed'));
        
        Cache::shouldReceive('get')
            ->andReturn(null);
        
        Cache::shouldReceive('put')
            ->andReturn(true);
        
        Cache::shouldReceive('forget')
            ->andReturn(true);
        
        $mockSummaryService = Mockery::mock(ManifestSummaryService::class);
        $mockSummaryService->shouldReceive('getManifestSummary')
            ->andThrow(new \Exception('Summary service failed'));
        
        $mockSummaryService->shouldReceive('getDisplaySummary')
            ->andThrow(new \Exception('Display summary service failed'));

        $cacheServiceWithMock = new ManifestSummaryCacheService($mockSummaryService);

        // Should return emergency fallback data
        $summary = $cacheServiceWithMock->getCachedSummary($this->manifest);
        
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('incomplete_data', $summary);
        $this->assertTrue($summary['incomplete_data']);
        $this->assertArrayHasKey('data_source', $summary);
        $this->assertStringContainsString('fallback', $summary['data_source']);
    }

    /** @test */
    public function it_warms_up_critical_manifests()
    {
        // Use existing manifest and create additional ones
        $manifest1 = $this->manifest;
        $manifest2 = Manifest::factory()->create(['type' => 'sea']);
        $manifest3 = Manifest::factory()->create(['type' => 'air']);
        
        Package::factory()->create(['manifest_id' => $manifest1->id, 'weight' => 5.0]);
        Package::factory()->create(['manifest_id' => $manifest2->id, 'weight' => 10.0]);
        // manifest3 has no packages

        $manifestIds = [$manifest1->id, $manifest2->id, $manifest3->id, 999]; // 999 doesn't exist

        $results = $this->cacheService->warmUpCriticalManifests($manifestIds);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('success', $results);
        $this->assertArrayHasKey('failed', $results);
        $this->assertArrayHasKey('skipped', $results);
        
        // Should have some successful warm-ups
        $this->assertGreaterThanOrEqual(2, count($results['success']));
        
        // Should skip non-existent manifest
        $this->assertGreaterThanOrEqual(1, count($results['skipped']));
    }

    /** @test */
    public function it_provides_comprehensive_health_report()
    {
        $healthReport = $this->cacheService->getCacheHealthReport();

        $this->assertIsArray($healthReport);
        
        // Check main sections
        $this->assertArrayHasKey('cache_available', $healthReport);
        $this->assertArrayHasKey('cache_healthy', $healthReport);
        $this->assertArrayHasKey('circuit_breaker', $healthReport);
        $this->assertArrayHasKey('health_monitoring', $healthReport);
        $this->assertArrayHasKey('cache_statistics', $healthReport);
        $this->assertArrayHasKey('performance_metrics', $healthReport);

        // Check circuit breaker section
        $circuitBreaker = $healthReport['circuit_breaker'];
        $this->assertArrayHasKey('is_open', $circuitBreaker);
        $this->assertArrayHasKey('failure_threshold', $circuitBreaker);
        $this->assertArrayHasKey('recovery_timeout', $circuitBreaker);
        $this->assertArrayHasKey('current_failures', $circuitBreaker);

        // Check health monitoring section
        $healthMonitoring = $healthReport['health_monitoring'];
        $this->assertArrayHasKey('check_interval', $healthMonitoring);

        // Check performance metrics section
        $performanceMetrics = $healthReport['performance_metrics'];
        $this->assertArrayHasKey('cache_ttl', $performanceMetrics);
        $this->assertArrayHasKey('supports_tags', $performanceMetrics);
        $this->assertArrayHasKey('cache_driver', $performanceMetrics);
    }

    /** @test */
    public function it_handles_invalid_manifest_gracefully()
    {
        // Test with null manifest
        $mockSummaryService = Mockery::mock(ManifestSummaryService::class);
        $mockSummaryService->shouldNotReceive('getManifestSummary');
        
        $cacheServiceWithMock = new ManifestSummaryCacheService($mockSummaryService);
        
        // Create a manifest without ID to simulate invalid state
        $invalidManifest = new Manifest();
        
        $summary = $cacheServiceWithMock->getCachedSummary($invalidManifest);
        
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('incomplete_data', $summary);
        $this->assertTrue($summary['incomplete_data']);
    }

    /** @test */
    public function it_logs_performance_metrics()
    {
        Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'weight' => 8.0
        ]);

        // Mock Log facade to verify performance logging
        Log::shouldReceive('info')
            ->with('Cache operation performance', Mockery::type('array'))
            ->once();
        
        Log::shouldReceive('info')
            ->with(Mockery::any(), Mockery::any())
            ->zeroOrMoreTimes();

        // This should trigger performance logging
        $this->cacheService->getCachedSummary($this->manifest);
        
        // Verify the test passes if we reach here
        $this->assertTrue(true);
    }

    /** @test */
    public function it_returns_enhanced_warm_up_results()
    {
        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'weight' => 6.0
        ]);

        $results = $this->cacheService->warmUpCache($this->manifest);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('success', $results);
        $this->assertArrayHasKey('summary_cached', $results);
        $this->assertArrayHasKey('display_summary_cached', $results);
        $this->assertArrayHasKey('errors', $results);
        
        $this->assertIsBool($results['success']);
        $this->assertIsBool($results['summary_cached']);
        $this->assertIsBool($results['display_summary_cached']);
        $this->assertIsArray($results['errors']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}