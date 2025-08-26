<?php

namespace App\Services;

use App\Models\Manifest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ManifestSummaryCacheService
{
    protected const CACHE_PREFIX = 'manifest_summary_';
    protected const CACHE_TTL = 3600; // 1 hour
    protected const CACHE_TAG = 'manifest_summaries';

    /** @var ManifestSummaryService */
    protected $summaryService;

    public function __construct(ManifestSummaryService $summaryService)
    {
        $this->summaryService = $summaryService;
    }

    /**
     * Get cached manifest summary or calculate and cache it
     *
     * @param Manifest $manifest
     * @return array
     */
    public function getCachedSummary(Manifest $manifest)
    {
        $cacheKey = $this->generateCacheKey($manifest);
        
        try {
            // Check if cache driver supports tagging
            if ($this->supportsTags()) {
                return Cache::tags([self::CACHE_TAG])->remember(
                    $cacheKey,
                    self::CACHE_TTL,
                    function () use ($manifest) {
                        Log::info('Calculating and caching manifest summary', [
                            'manifest_id' => $manifest->id,
                            'cache_key' => $this->generateCacheKey($manifest)
                        ]);
                        
                        return $this->summaryService->getManifestSummary($manifest);
                    }
                );
            } else {
                // Fallback to regular caching without tags
                return Cache::remember(
                    $cacheKey,
                    self::CACHE_TTL,
                    function () use ($manifest) {
                        Log::info('Calculating and caching manifest summary (no tags)', [
                            'manifest_id' => $manifest->id,
                            'cache_key' => $this->generateCacheKey($manifest)
                        ]);
                        
                        return $this->summaryService->getManifestSummary($manifest);
                    }
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to get cached summary, falling back to direct calculation', [
                'manifest_id' => $manifest->id,
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to direct calculation if cache fails
            return $this->summaryService->getManifestSummary($manifest);
        }
    }

    /**
     * Get cached display summary or calculate and cache it
     *
     * @param Manifest $manifest
     * @return array
     */
    public function getCachedDisplaySummary(Manifest $manifest)
    {
        $cacheKey = $this->generateDisplayCacheKey($manifest);
        
        try {
            // Check if cache driver supports tagging
            if ($this->supportsTags()) {
                return Cache::tags([self::CACHE_TAG])->remember(
                    $cacheKey,
                    self::CACHE_TTL,
                    function () use ($manifest) {
                        Log::info('Calculating and caching display summary', [
                            'manifest_id' => $manifest->id,
                            'cache_key' => $this->generateDisplayCacheKey($manifest)
                        ]);
                        
                        return $this->summaryService->getDisplaySummary($manifest);
                    }
                );
            } else {
                // Fallback to regular caching without tags
                return Cache::remember(
                    $cacheKey,
                    self::CACHE_TTL,
                    function () use ($manifest) {
                        Log::info('Calculating and caching display summary (no tags)', [
                            'manifest_id' => $manifest->id,
                            'cache_key' => $this->generateDisplayCacheKey($manifest)
                        ]);
                        
                        return $this->summaryService->getDisplaySummary($manifest);
                    }
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to get cached display summary, falling back to direct calculation', [
                'manifest_id' => $manifest->id,
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to direct calculation if cache fails
            return $this->summaryService->getDisplaySummary($manifest);
        }
    }

    /**
     * Invalidate cache for a specific manifest
     *
     * @param Manifest $manifest
     * @return void
     */
    public function invalidateManifestCache(Manifest $manifest)
    {
        try {
            $cacheKey = $this->generateCacheKey($manifest);
            $displayCacheKey = $this->generateDisplayCacheKey($manifest);
            
            Cache::forget($cacheKey);
            Cache::forget($displayCacheKey);
            
            Log::info('Invalidated manifest summary cache', [
                'manifest_id' => $manifest->id,
                'cache_keys' => [$cacheKey, $displayCacheKey]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate manifest cache', [
                'manifest_id' => $manifest->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Invalidate all manifest summary caches
     *
     * @return void
     */
    public function invalidateAllCaches()
    {
        try {
            if ($this->supportsTags()) {
                Cache::tags([self::CACHE_TAG])->flush();
            } else {
                // For drivers without tag support, we can't flush all at once
                // This would need to be handled differently in production
                Log::info('Cache driver does not support tags, cannot flush all caches at once');
            }
            
            Log::info('Invalidated all manifest summary caches');
        } catch (\Exception $e) {
            Log::error('Failed to invalidate all manifest caches', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Warm up cache for a manifest
     *
     * @param Manifest $manifest
     * @return void
     */
    public function warmUpCache(Manifest $manifest)
    {
        try {
            // Pre-calculate and cache both summary types
            $this->getCachedSummary($manifest);
            $this->getCachedDisplaySummary($manifest);
            
            Log::info('Warmed up cache for manifest', [
                'manifest_id' => $manifest->id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to warm up cache for manifest', [
                'manifest_id' => $manifest->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function getCacheStatistics()
    {
        try {
            // This is a simplified version - in production you might want more detailed stats
            return [
                'cache_enabled' => config('cache.default') !== 'array',
                'cache_driver' => config('cache.default'),
                'cache_ttl' => self::CACHE_TTL,
                'cache_tag' => self::CACHE_TAG,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get cache statistics', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'cache_enabled' => false,
                'error' => 'Failed to retrieve cache statistics'
            ];
        }
    }

    /**
     * Generate cache key for manifest summary
     *
     * @param Manifest $manifest
     * @return string
     */
    protected function generateCacheKey(Manifest $manifest)
    {
        // Include manifest updated_at and package count in cache key for auto-invalidation
        $packageCount = $manifest->packages()->count();
        $lastUpdated = $manifest->updated_at ? $manifest->updated_at->timestamp : 0;
        
        return self::CACHE_PREFIX . 'full_' . $manifest->id . '_' . $packageCount . '_' . $lastUpdated;
    }

    /**
     * Generate cache key for display summary
     *
     * @param Manifest $manifest
     * @return string
     */
    protected function generateDisplayCacheKey(Manifest $manifest)
    {
        // Include manifest updated_at and package count in cache key for auto-invalidation
        $packageCount = $manifest->packages()->count();
        $lastUpdated = $manifest->updated_at ? $manifest->updated_at->timestamp : 0;
        
        return self::CACHE_PREFIX . 'display_' . $manifest->id . '_' . $packageCount . '_' . $lastUpdated;
    }

    /**
     * Check if cache is available and working
     *
     * @return bool
     */
    public function isCacheAvailable()
    {
        try {
            $testKey = 'cache_test_' . time();
            $testValue = 'test_value';
            
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            return $retrieved === $testValue;
        } catch (\Exception $e) {
            Log::warning('Cache availability check failed', [
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Check if the current cache driver supports tagging
     *
     * @return bool
     */
    protected function supportsTags()
    {
        $driver = config('cache.default');
        
        // Only redis, memcached, and database drivers support tagging
        return in_array($driver, ['redis', 'memcached', 'database']);
    }
}