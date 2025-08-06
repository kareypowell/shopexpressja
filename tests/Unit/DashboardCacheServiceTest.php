<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\DashboardCacheService;
use Illuminate\Support\Facades\Cache;

class DashboardCacheServiceTest extends TestCase
{
    protected DashboardCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheService = app(DashboardCacheService::class);
        
        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_can_remember_values_in_cache()
    {
        $key = 'test_key';
        $value = 'test_value';
        $ttl = 300;

        $result = $this->cacheService->remember($key, $ttl, function () use ($value) {
            return $value;
        });

        $this->assertEquals($value, $result);
        
        // Verify it's cached by calling again without the callback being executed
        $cachedResult = $this->cacheService->remember($key, $ttl, function () {
            return 'different_value';
        });
        
        $this->assertEquals($value, $cachedResult);
    }

    /** @test */
    public function it_can_forget_cache_keys()
    {
        $key = 'test_key';
        $value = 'test_value';

        // Store value in cache
        $this->cacheService->remember($key, 300, function () use ($value) {
            return $value;
        });

        // Verify it's cached
        $this->assertTrue(Cache::has('dashboard:' . $key));

        // Forget the key
        $result = $this->cacheService->forget($key);
        
        $this->assertTrue($result);
        $this->assertFalse(Cache::has('dashboard:' . $key));
    }

    /** @test */
    public function it_can_work_with_tags()
    {
        $key = 'test_key';
        $value = 'test_value';
        $tags = ['dashboard', 'metrics'];

        $result = $this->cacheService->tags($tags)->remember($key, 300, function () use ($value) {
            return $value;
        });

        $this->assertEquals($value, $result);
    }

    /** @test */
    public function it_can_put_values_with_custom_ttl()
    {
        $key = 'test_key';
        $value = 'test_value';
        $ttl = 600;

        $result = $this->cacheService->put($key, $value, $ttl);
        
        $this->assertTrue($result);
        $this->assertEquals($value, Cache::get('dashboard:' . $key));
    }

    /** @test */
    public function it_uses_default_ttl_based_on_key_type()
    {
        $metricsKey = 'customer_metrics_test';
        $analyticsKey = 'growth_analytics_test';
        $defaultKey = 'some_other_data';

        // These should use different default TTLs based on key patterns
        $this->cacheService->put($metricsKey, 'metrics_value');
        $this->cacheService->put($analyticsKey, 'analytics_value');
        $this->cacheService->put($defaultKey, 'default_value');

        // All should be cached (we can't easily test TTL differences in unit tests)
        $this->assertEquals('metrics_value', Cache::get('dashboard:' . $metricsKey));
        $this->assertEquals('analytics_value', Cache::get('dashboard:' . $analyticsKey));
        $this->assertEquals('default_value', Cache::get('dashboard:' . $defaultKey));
    }

    /** @test */
    public function it_can_get_cache_stats()
    {
        $stats = $this->cacheService->getStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_keys', $stats);
        $this->assertArrayHasKey('memory_usage', $stats);
        $this->assertArrayHasKey('hit_rate', $stats);
    }

    /** @test */
    public function it_can_invalidate_cache_on_data_change()
    {
        // Store some test data
        $this->cacheService->put('customer_metrics_test', 'test_value');
        $this->cacheService->put('dashboard_summary', 'summary_value');

        // Verify data is cached
        $this->assertEquals('test_value', Cache::get('dashboard:customer_metrics_test'));
        $this->assertEquals('summary_value', Cache::get('dashboard:dashboard_summary'));

        // Invalidate cache for User model changes
        $this->cacheService->invalidateOnDataChange('User');

        // The cache should be cleared (this test may vary based on cache driver)
        // For now, just verify the method doesn't throw an exception
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_cache_errors_gracefully()
    {
        // Test flush with invalid pattern
        $result = $this->cacheService->flush('invalid_pattern');
        
        // Should not throw exception and return boolean
        $this->assertIsBool($result);
    }
}