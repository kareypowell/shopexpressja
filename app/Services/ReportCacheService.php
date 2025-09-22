<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReportCacheService
{
    /**
     * Cache TTL configurations (in minutes)
     */
    private const CACHE_TTL = [
        'query_results' => 15,      // Raw query results
        'aggregated_data' => 60,    // Processed/aggregated data
        'report_templates' => 1440, // Report templates (24 hours)
        'chart_data' => 30,         // Chart visualization data
        'dashboard_widgets' => 10,  // Dashboard widget data
        'export_data' => 5          // Export preparation data
    ];

    /**
     * Cache key prefixes for different data types
     */
    private const CACHE_PREFIXES = [
        'sales' => 'report:sales',
        'manifest' => 'report:manifest',
        'customer' => 'report:customer',
        'financial' => 'report:financial',
        'analytics' => 'report:analytics',
        'export' => 'report:export',
        'dashboard' => 'report:dashboard'
    ];

    /**
     * Cache report data with appropriate TTL
     */
    public function cacheReportData(string $key, array $data, int $ttl = null): void
    {
        try {
            $cacheKey = $this->buildCacheKey($key);
            $cacheTtl = $ttl ?? $this->determineTtl($key);
            
            Cache::put($cacheKey, [
                'data' => $data,
                'cached_at' => now()->toISOString(),
                'expires_at' => now()->addMinutes($cacheTtl)->toISOString()
            ], $cacheTtl * 60); // Convert to seconds

            Log::info('Report data cached', [
                'cache_key' => $cacheKey,
                'ttl_minutes' => $cacheTtl,
                'data_size' => count($data)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cache report data', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cached report data
     */
    public function getCachedReportData(string $key): ?array
    {
        try {
            $cacheKey = $this->buildCacheKey($key);
            $cached = Cache::get($cacheKey);
            
            if ($cached && is_array($cached) && isset($cached['data'])) {
                Log::info('Report data retrieved from cache', [
                    'cache_key' => $cacheKey,
                    'cached_at' => $cached['cached_at'] ?? 'unknown'
                ]);
                
                return $cached['data'];
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to retrieve cached report data', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Invalidate report cache by pattern
     */
    public function invalidateReportCache(string $pattern): void
    {
        try {
            // For Redis cache, we can use pattern matching
            if (config('cache.default') === 'redis') {
                $this->invalidateRedisPattern($pattern);
            } else {
                // For other cache drivers, invalidate known keys
                $this->invalidateKnownKeys($pattern);
            }
            
            Log::info('Report cache invalidated', ['pattern' => $pattern]);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate report cache', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Warm up report cache for frequently accessed reports
     */
    public function warmUpReportCache(array $filters = []): void
    {
        try {
            $warmupTasks = [
                'sales_summary' => fn() => $this->warmupSalesData($filters),
                'manifest_metrics' => fn() => $this->warmupManifestData($filters),
                'customer_analytics' => fn() => $this->warmupCustomerData($filters),
                'dashboard_widgets' => fn() => $this->warmupDashboardData($filters)
            ];

            foreach ($warmupTasks as $taskName => $task) {
                try {
                    $task();
                    Log::info('Cache warmup completed', ['task' => $taskName]);
                } catch (\Exception $e) {
                    Log::warning('Cache warmup failed for task', [
                        'task' => $taskName,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Cache warmup process failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cache statistics and health information
     */
    public function getCacheStats(): array
    {
        try {
            $stats = [
                'cache_driver' => config('cache.default'),
                'cache_health' => $this->checkCacheHealth(),
                'key_counts' => $this->getCacheKeyCounts(),
                'memory_usage' => $this->getCacheMemoryUsage(),
                'hit_rates' => $this->calculateHitRates()
            ];

            return $stats;
        } catch (\Exception $e) {
            Log::error('Failed to get cache statistics', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'cache_driver' => config('cache.default'),
                'cache_health' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clear all report-related cache
     */
    public function clearAllReportCache(): void
    {
        try {
            foreach (self::CACHE_PREFIXES as $type => $prefix) {
                $this->invalidateReportCache($prefix . ':*');
            }
            
            Log::info('All report cache cleared');
        } catch (\Exception $e) {
            Log::error('Failed to clear all report cache', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Cache sales report data
     */
    public function cacheSalesData(array $filters, array $data): void
    {
        $key = $this->generateSalesKey($filters);
        $this->cacheReportData($key, $data, self::CACHE_TTL['aggregated_data']);
    }

    /**
     * Get cached sales report data
     */
    public function getCachedSalesData(array $filters): ?array
    {
        $key = $this->generateSalesKey($filters);
        return $this->getCachedReportData($key);
    }

    /**
     * Cache manifest analytics data
     */
    public function cacheManifestData(array $filters, array $data): void
    {
        $key = $this->generateManifestKey($filters);
        $this->cacheReportData($key, $data, self::CACHE_TTL['aggregated_data']);
    }

    /**
     * Get cached manifest analytics data
     */
    public function getCachedManifestData(array $filters): ?array
    {
        $key = $this->generateManifestKey($filters);
        return $this->getCachedReportData($key);
    }

    /**
     * Cache customer analytics data
     */
    public function cacheCustomerData(array $filters, array $data): void
    {
        $key = $this->generateCustomerKey($filters);
        $this->cacheReportData($key, $data, self::CACHE_TTL['aggregated_data']);
    }

    /**
     * Get cached customer analytics data
     */
    public function getCachedCustomerData(array $filters): ?array
    {
        $key = $this->generateCustomerKey($filters);
        return $this->getCachedReportData($key);
    }

    /**
     * Cache chart data
     */
    public function cacheChartData(string $chartType, array $filters, array $data): void
    {
        $key = $this->generateChartKey($chartType, $filters);
        $this->cacheReportData($key, $data, self::CACHE_TTL['chart_data']);
    }

    /**
     * Get cached chart data
     */
    public function getCachedChartData(string $chartType, array $filters): ?array
    {
        $key = $this->generateChartKey($chartType, $filters);
        return $this->getCachedReportData($key);
    }

    /**
     * Cache dashboard widget data
     */
    public function cacheDashboardWidget(string $widgetType, array $data): void
    {
        $key = self::CACHE_PREFIXES['dashboard'] . ':widget:' . $widgetType;
        $this->cacheReportData($key, $data, self::CACHE_TTL['dashboard_widgets']);
    }

    /**
     * Get cached dashboard widget data
     */
    public function getCachedDashboardWidget(string $widgetType): ?array
    {
        $key = self::CACHE_PREFIXES['dashboard'] . ':widget:' . $widgetType;
        return $this->getCachedReportData($key);
    }

    /**
     * Invalidate cache when models are updated
     */
    public function invalidateModelCache(string $modelType, int $modelId = null): void
    {
        $patterns = [];
        
        switch ($modelType) {
            case 'package':
                $patterns = [
                    self::CACHE_PREFIXES['sales'] . ':*',
                    self::CACHE_PREFIXES['manifest'] . ':*',
                    self::CACHE_PREFIXES['customer'] . ':*',
                    self::CACHE_PREFIXES['dashboard'] . ':*'
                ];
                break;
                
            case 'manifest':
                $patterns = [
                    self::CACHE_PREFIXES['manifest'] . ':*',
                    self::CACHE_PREFIXES['dashboard'] . ':*'
                ];
                break;
                
            case 'customer_transaction':
                $patterns = [
                    self::CACHE_PREFIXES['sales'] . ':*',
                    self::CACHE_PREFIXES['financial'] . ':*',
                    self::CACHE_PREFIXES['customer'] . ':*',
                    self::CACHE_PREFIXES['dashboard'] . ':*'
                ];
                break;
                
            case 'user':
                if ($modelId) {
                    $patterns = [
                        self::CACHE_PREFIXES['customer'] . ':*:user:' . $modelId . ':*'
                    ];
                }
                break;
        }
        
        foreach ($patterns as $pattern) {
            $this->invalidateReportCache($pattern);
        }
    }

    /**
     * Build cache key with prefix
     */
    private function buildCacheKey(string $key): string
    {
        return 'reports:' . $key;
    }

    /**
     * Determine TTL based on key pattern
     */
    private function determineTtl(string $key): int
    {
        if (str_contains($key, 'query')) {
            return self::CACHE_TTL['query_results'];
        }
        
        if (str_contains($key, 'chart')) {
            return self::CACHE_TTL['chart_data'];
        }
        
        if (str_contains($key, 'dashboard')) {
            return self::CACHE_TTL['dashboard_widgets'];
        }
        
        if (str_contains($key, 'template')) {
            return self::CACHE_TTL['report_templates'];
        }
        
        if (str_contains($key, 'export')) {
            return self::CACHE_TTL['export_data'];
        }
        
        return self::CACHE_TTL['aggregated_data'];
    }

    /**
     * Generate sales report cache key
     */
    private function generateSalesKey(array $filters): string
    {
        $keyParts = [
            self::CACHE_PREFIXES['sales'],
            'summary',
            md5(serialize($this->normalizeFilters($filters)))
        ];
        
        return implode(':', $keyParts);
    }

    /**
     * Generate manifest analytics cache key
     */
    private function generateManifestKey(array $filters): string
    {
        $keyParts = [
            self::CACHE_PREFIXES['manifest'],
            'analytics',
            md5(serialize($this->normalizeFilters($filters)))
        ];
        
        return implode(':', $keyParts);
    }

    /**
     * Generate customer analytics cache key
     */
    private function generateCustomerKey(array $filters): string
    {
        $keyParts = [
            self::CACHE_PREFIXES['customer'],
            'analytics',
            md5(serialize($this->normalizeFilters($filters)))
        ];
        
        return implode(':', $keyParts);
    }

    /**
     * Generate chart data cache key
     */
    private function generateChartKey(string $chartType, array $filters): string
    {
        $keyParts = [
            self::CACHE_PREFIXES['analytics'],
            'chart',
            $chartType,
            md5(serialize($this->normalizeFilters($filters)))
        ];
        
        return implode(':', $keyParts);
    }

    /**
     * Normalize filters for consistent cache keys
     */
    private function normalizeFilters(array $filters): array
    {
        // Remove empty values and sort for consistent keys
        $normalized = array_filter($filters, fn($value) => $value !== null && $value !== '');
        ksort($normalized);
        
        // Convert dates to consistent format
        foreach (['date_from', 'date_to', 'start_date', 'end_date'] as $dateField) {
            if (isset($normalized[$dateField])) {
                $normalized[$dateField] = Carbon::parse($normalized[$dateField])->toDateString();
            }
        }
        
        return $normalized;
    }

    /**
     * Invalidate Redis cache by pattern
     */
    private function invalidateRedisPattern(string $pattern): void
    {
        $redis = Cache::getRedis();
        $keys = $redis->keys($this->buildCacheKey($pattern));
        
        if (!empty($keys)) {
            $redis->del($keys);
        }
    }

    /**
     * Invalidate known cache keys for non-Redis drivers
     */
    private function invalidateKnownKeys(string $pattern): void
    {
        // For non-Redis drivers, we maintain a list of active keys
        $activeKeys = Cache::get('reports:active_keys', []);
        
        foreach ($activeKeys as $key) {
            if (fnmatch($pattern, $key)) {
                Cache::forget($key);
            }
        }
    }

    /**
     * Warm up sales data cache
     */
    private function warmupSalesData(array $filters): void
    {
        $businessReportService = app(BusinessReportService::class);
        
        // Common filter combinations for warmup
        $commonFilters = [
            [], // No filters (all data)
            ['date_from' => now()->subDays(30)->toDateString()], // Last 30 days
            ['date_from' => now()->subDays(7)->toDateString()], // Last 7 days
        ];
        
        foreach ($commonFilters as $filterSet) {
            $mergedFilters = array_merge($filters, $filterSet);
            $data = $businessReportService->generateSalesCollectionsReport($mergedFilters);
            $this->cacheSalesData($mergedFilters, $data);
        }
    }

    /**
     * Warm up manifest data cache
     */
    private function warmupManifestData(array $filters): void
    {
        $manifestAnalyticsService = app(ManifestAnalyticsService::class);
        
        $commonFilters = [
            [],
            ['type' => 'air'],
            ['type' => 'sea'],
            ['date_from' => now()->subDays(30)->toDateString()]
        ];
        
        foreach ($commonFilters as $filterSet) {
            $mergedFilters = array_merge($filters, $filterSet);
            $data = $manifestAnalyticsService->getEfficiencyMetrics($mergedFilters);
            $this->cacheManifestData($mergedFilters, $data);
        }
    }

    /**
     * Warm up customer data cache
     */
    private function warmupCustomerData(array $filters): void
    {
        // Placeholder for customer analytics warmup
        // Will be implemented when customer analytics service is available
    }

    /**
     * Warm up dashboard data cache
     */
    private function warmupDashboardData(array $filters): void
    {
        // Common dashboard widgets
        $widgets = ['sales_summary', 'manifest_count', 'recent_activity'];
        
        foreach ($widgets as $widget) {
            // Placeholder data - will be replaced with actual service calls
            $data = ['widget' => $widget, 'warmed_at' => now()->toISOString()];
            $this->cacheDashboardWidget($widget, $data);
        }
    }

    /**
     * Check cache health
     */
    private function checkCacheHealth(): bool
    {
        try {
            $testKey = 'reports:health_check:' . time();
            $testValue = ['test' => true, 'timestamp' => time()];
            
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            return $retrieved && $retrieved['test'] === true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get cache key counts by prefix
     */
    private function getCacheKeyCounts(): array
    {
        $counts = [];
        
        foreach (self::CACHE_PREFIXES as $type => $prefix) {
            $counts[$type] = 0; // Placeholder - actual implementation depends on cache driver
        }
        
        return $counts;
    }

    /**
     * Get cache memory usage information
     */
    private function getCacheMemoryUsage(): array
    {
        return [
            'driver' => config('cache.default'),
            'usage' => 'Not available for this driver'
        ];
    }

    /**
     * Calculate cache hit rates
     */
    private function calculateHitRates(): array
    {
        // This would require implementing hit/miss tracking
        return [
            'overall' => 0,
            'by_type' => []
        ];
    }
}