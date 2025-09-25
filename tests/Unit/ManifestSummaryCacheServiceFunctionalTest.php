<?php

namespace Tests\Unit;

use App\Services\ManifestSummaryCacheService;
use App\Services\ManifestSummaryService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Mockery;

class ManifestSummaryCacheServiceFunctionalTest extends TestCase
{
    /** @test */
    public function it_provides_comprehensive_health_report()
    {
        $cacheService = app(ManifestSummaryCacheService::class);
        $healthReport = $cacheService->getCacheHealthReport();

        $this->assertIsArray($healthReport);
        
        // Check main sections exist
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

        // Verify data types
        $this->assertIsBool($healthReport['cache_available']);
        $this->assertIsBool($healthReport['cache_healthy']);
        $this->assertIsBool($circuitBreaker['is_open']);
        $this->assertIsInt($circuitBreaker['failure_threshold']);
        $this->assertIsInt($circuitBreaker['recovery_timeout']);
        $this->assertIsInt($circuitBreaker['current_failures']);
    }

    /** @test */
    public function it_handles_cache_availability_check()
    {
        $cacheService = app(ManifestSummaryCacheService::class);
        
        // Test cache availability
        $isAvailable = $cacheService->isCacheAvailable();
        $this->assertIsBool($isAvailable);
        
        // In testing environment, cache should be available
        $this->assertTrue($isAvailable);
    }

    /** @test */
    public function it_provides_cache_statistics()
    {
        $cacheService = app(ManifestSummaryCacheService::class);
        $stats = $cacheService->getCacheStatistics();
        
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
    public function it_handles_warm_up_critical_manifests_with_empty_array()
    {
        $cacheService = app(ManifestSummaryCacheService::class);
        
        // Test with empty array
        $results = $cacheService->warmUpCriticalManifests([]);
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('success', $results);
        $this->assertArrayHasKey('failed', $results);
        $this->assertArrayHasKey('skipped', $results);
        
        // All arrays should be empty
        $this->assertEmpty($results['success']);
        $this->assertEmpty($results['failed']);
        $this->assertEmpty($results['skipped']);
    }

    /** @test */
    public function it_handles_warm_up_critical_manifests_with_non_existent_ids()
    {
        $cacheService = app(ManifestSummaryCacheService::class);
        
        // Test with non-existent manifest IDs
        $results = $cacheService->warmUpCriticalManifests([999, 1000, 1001]);
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('success', $results);
        $this->assertArrayHasKey('failed', $results);
        $this->assertArrayHasKey('skipped', $results);
        
        // Should have skipped all non-existent manifests
        $this->assertEmpty($results['success']);
        $this->assertEmpty($results['failed']);
        $this->assertCount(3, $results['skipped']);
        
        // Check skipped entries have proper structure
        foreach ($results['skipped'] as $skipped) {
            $this->assertArrayHasKey('manifest_id', $skipped);
            $this->assertArrayHasKey('reason', $skipped);
            $this->assertEquals('Manifest not found', $skipped['reason']);
        }
    }

    /** @test */
    public function it_handles_circuit_breaker_constants()
    {
        $cacheService = app(ManifestSummaryCacheService::class);
        $healthReport = $cacheService->getCacheHealthReport();
        
        $circuitBreaker = $healthReport['circuit_breaker'];
        
        // Verify circuit breaker constants are properly set
        $this->assertEquals(5, $circuitBreaker['failure_threshold']);
        $this->assertEquals(60, $circuitBreaker['recovery_timeout']);
        $this->assertIsInt($circuitBreaker['current_failures']);
        $this->assertGreaterThanOrEqual(0, $circuitBreaker['current_failures']);
    }

    /** @test */
    public function it_handles_health_monitoring_configuration()
    {
        $cacheService = app(ManifestSummaryCacheService::class);
        $healthReport = $cacheService->getCacheHealthReport();
        
        $healthMonitoring = $healthReport['health_monitoring'];
        
        // Verify health monitoring configuration
        $this->assertArrayHasKey('check_interval', $healthMonitoring);
        $this->assertEquals(30, $healthMonitoring['check_interval']);
    }

    /** @test */
    public function it_handles_performance_metrics_configuration()
    {
        $cacheService = app(ManifestSummaryCacheService::class);
        $healthReport = $cacheService->getCacheHealthReport();
        
        $performanceMetrics = $healthReport['performance_metrics'];
        
        // Verify performance metrics configuration
        $this->assertArrayHasKey('cache_ttl', $performanceMetrics);
        $this->assertArrayHasKey('supports_tags', $performanceMetrics);
        $this->assertArrayHasKey('cache_driver', $performanceMetrics);
        
        $this->assertEquals(3600, $performanceMetrics['cache_ttl']);
        $this->assertIsBool($performanceMetrics['supports_tags']);
        $this->assertIsString($performanceMetrics['cache_driver']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}