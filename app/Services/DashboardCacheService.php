<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class DashboardCacheService
{
    protected array $tags = [];
    protected string $prefix = 'dashboard:';

    /**
     * Remember a value in cache with TTL
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        $fullKey = $this->prefix . $key;
        
        if (!empty($this->tags)) {
            return Cache::tags($this->tags)->remember($fullKey, $ttl, $callback);
        }
        
        return Cache::remember($fullKey, $ttl, $callback);
    }

    /**
     * Forget a specific cache key
     */
    public function forget(string $key): bool
    {
        $fullKey = $this->prefix . $key;
        
        if (!empty($this->tags)) {
            return Cache::tags($this->tags)->forget($fullKey);
        }
        
        return Cache::forget($fullKey);
    }

    /**
     * Flush cache by pattern
     */
    public function flush(string $pattern): bool
    {
        try {
            // If using Redis, we can use pattern matching
            if (config('cache.default') === 'redis') {
                $redis = Redis::connection();
                $keys = $redis->keys($this->prefix . $pattern . '*');
                
                if (!empty($keys)) {
                    return $redis->del($keys) > 0;
                }
                
                return true;
            }
            
            // For other cache drivers, flush by tags if available
            if (!empty($this->tags)) {
                Cache::tags($this->tags)->flush();
                return true;
            }
            
            // Fallback: cannot flush by pattern without Redis
            return false;
            
        } catch (\Exception $e) {
            // Log error and return false
            \Log::error('Dashboard cache flush failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Set cache tags for the next operation
     */
    public function tags(array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * Clear all dashboard cache
     */
    public function clearAll(): bool
    {
        return $this->flush('*');
    }

    /**
     * Warm cache with common filter combinations
     */
    public function warmCache(): void
    {
        $commonFilters = [
            ['date_range' => 7],   // Last 7 days
            ['date_range' => 30],  // Last 30 days
            ['date_range' => 90],  // Last 90 days
        ];

        $analyticsService = app(DashboardAnalyticsService::class);

        foreach ($commonFilters as $filters) {
            // Warm customer metrics
            $analyticsService->getCustomerMetrics($filters);
            
            // Warm shipment metrics
            $analyticsService->getShipmentMetrics($filters);
            
            // Warm financial metrics
            $analyticsService->getFinancialMetrics($filters);
            
            // Warm growth data
            $analyticsService->getCustomerGrowthData($filters);
            
            // Warm revenue analytics
            $analyticsService->getRevenueAnalytics($filters);
        }
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        try {
            if (config('cache.default') === 'redis') {
                $redis = Redis::connection();
                $keys = $redis->keys($this->prefix . '*');
                
                $stats = [
                    'total_keys' => count($keys),
                    'memory_usage' => 0,
                    'hit_rate' => 0,
                ];
                
                // Calculate approximate memory usage
                foreach ($keys as $key) {
                    $stats['memory_usage'] += strlen($redis->get($key) ?? '');
                }
                
                return $stats;
            }
            
            return [
                'total_keys' => 'N/A',
                'memory_usage' => 'N/A',
                'hit_rate' => 'N/A',
            ];
            
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Invalidate cache when data changes
     */
    public function invalidateOnDataChange(string $model): void
    {
        $patterns = [
            'User' => ['customer_*', 'dashboard.*'],
            'Package' => ['shipment_*', 'financial_*', 'revenue_*', 'dashboard.*'],
            'Manifest' => ['shipment_*', 'dashboard.*'],
        ];

        if (isset($patterns[$model])) {
            foreach ($patterns[$model] as $pattern) {
                $this->flush($pattern);
            }
        }
    }

    /**
     * Set cache with custom TTL based on data type
     */
    public function put(string $key, $value, ?int $ttl = null): bool
    {
        $fullKey = $this->prefix . $key;
        
        // Default TTL based on key type
        if ($ttl === null) {
            $ttl = $this->getDefaultTtl($key);
        }
        
        if (!empty($this->tags)) {
            return Cache::tags($this->tags)->put($fullKey, $value, $ttl);
        }
        
        return Cache::put($fullKey, $value, $ttl);
    }

    /**
     * Get default TTL based on cache key type
     */
    protected function getDefaultTtl(string $key): int
    {
        // Metrics data - 5 minutes
        if (str_contains($key, 'metrics')) {
            return 300;
        }
        
        // Chart data - 10 minutes
        if (str_contains($key, 'growth') || str_contains($key, 'analytics')) {
            return 600;
        }
        
        // Default - 15 minutes
        return 900;
    }
}