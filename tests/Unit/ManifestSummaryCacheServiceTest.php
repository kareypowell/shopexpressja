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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}