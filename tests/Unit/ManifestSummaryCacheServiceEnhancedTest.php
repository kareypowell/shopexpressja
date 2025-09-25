<?php

namespace Tests\Unit;

use App\Models\Manifest;
use App\Services\ManifestSummaryCacheService;
use App\Services\ManifestSummaryService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Mockery;

class ManifestSummaryCacheServiceEnhancedTest extends TestCase
{
    protected ManifestSummaryCacheService $cacheService;
    protected Manifest $manifest;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a simple manifest without database dependencies
        $this->manifest = new Manifest();
        $this->manifest->id = 1;
        $this->manifest->type = 'air';
        $this->manifest->updated_at = now();
        
        $this->cacheService = app(ManifestSummaryCacheService::class);
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
    public function it_handles_cache_failures_with_circuit_breaker()
    {
        // Mock cache to consistently fail
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

        // Mock the packages relationship to avoid database calls
        $packagesMock = Mockery::mock();
        $packagesMock->shouldReceive('count')->andReturn(3);
        $this->manifest->shouldReceive('packages')->andReturn($packagesMock);

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

        // Mock the packages relationship to avoid database calls
        $packagesMock = Mockery::mock();
        $packagesMock->shouldReceive('count')->andReturn(2);
        $this->manifest->shouldReceive('packages')->andReturn($packagesMock);

        // Should return emergency fallback data
        $summary = $cacheServiceWithMock->getCachedSummary($this->manifest);
        
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('incomplete_data', $summary);
        $this->assertTrue($summary['incomplete_data']);
        $this->assertArrayHasKey('data_source', $summary);
        $this->assertStringContainsString('fallback', $summary['data_source']);
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
    public function it_returns_enhanced_warm_up_results()
    {
        // Mock the packages relationship to avoid database calls
        $packagesMock = Mockery::mock();
        $packagesMock->shouldReceive('count')->andReturn(2);
        $this->manifest->shouldReceive('packages')->andReturn($packagesMock);

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

    /** @test */
    public function it_warms_up_critical_manifests_with_proper_error_handling()
    {
        // Create mock manifests
        $manifest1 = Mockery::mock(Manifest::class);
        $manifest1->id = 1;
        $manifest1->shouldReceive('getAttribute')->with('id')->andReturn(1);
        
        $manifest2 = Mockery::mock(Manifest::class);
        $manifest2->id = 2;
        $manifest2->shouldReceive('getAttribute')->with('id')->andReturn(2);

        // Mock Manifest::find to return our mocks
        Manifest::shouldReceive('find')
            ->with(1)->andReturn($manifest1)
            ->with(2)->andReturn($manifest2)
            ->with(999)->andReturn(null);

        // Mock packages relationship
        $packagesMock = Mockery::mock();
        $packagesMock->shouldReceive('count')->andReturn(1);
        $manifest1->shouldReceive('packages')->andReturn($packagesMock);
        $manifest2->shouldReceive('packages')->andReturn($packagesMock);

        $manifestIds = [1, 2, 999]; // 999 doesn't exist

        $results = $this->cacheService->warmUpCriticalManifests($manifestIds);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('success', $results);
        $this->assertArrayHasKey('failed', $results);
        $this->assertArrayHasKey('skipped', $results);
        
        // Should have some successful warm-ups
        $this->assertGreaterThanOrEqual(1, count($results['success']));
        
        // Should skip non-existent manifest
        $this->assertGreaterThanOrEqual(1, count($results['skipped']));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}