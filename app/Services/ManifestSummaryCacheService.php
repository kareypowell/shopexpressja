<?php

namespace App\Services;

use App\Models\Manifest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ManifestSummaryCacheService
{
    protected const CACHE_PREFIX = 'manifest_summary_';
    protected const CACHE_TTL = 3600; // 1 hour
    protected const CACHE_TAG = 'manifest_summaries';
    
    // Circuit breaker constants
    protected const CIRCUIT_BREAKER_PREFIX = 'cache_circuit_breaker_';
    protected const CIRCUIT_BREAKER_TTL = 300; // 5 minutes
    protected const FAILURE_THRESHOLD = 5; // Number of failures before opening circuit
    protected const RECOVERY_TIMEOUT = 60; // Seconds before attempting recovery
    
    // Cache health monitoring
    protected const HEALTH_CHECK_PREFIX = 'cache_health_';
    protected const HEALTH_CHECK_TTL = 60; // 1 minute
    protected const HEALTH_CHECK_INTERVAL = 30; // Check every 30 seconds

    /** @var ManifestSummaryService */
    protected $summaryService;
    
    /** @var array Cache health status */
    protected $healthStatus = [
        'is_healthy' => true,
        'last_check' => null,
        'consecutive_failures' => 0,
        'circuit_open' => false
    ];

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
        $startTime = microtime(true);
        $cacheKey = $this->generateCacheKey($manifest);
        
        // Check circuit breaker status
        if ($this->isCircuitOpen()) {
            Log::warning('Cache circuit breaker is open, using direct calculation', [
                'manifest_id' => $manifest->id,
                'cache_key' => $cacheKey
            ]);
            
            return $this->getDirectSummaryWithFallback($manifest);
        }
        
        // Check cache health before attempting operation
        if (!$this->isCacheHealthy()) {
            Log::warning('Cache health check failed, using direct calculation', [
                'manifest_id' => $manifest->id,
                'cache_key' => $cacheKey,
                'health_status' => $this->healthStatus
            ]);
            
            return $this->getDirectSummaryWithFallback($manifest);
        }
        
        try {
            $result = $this->attemptCachedSummary($manifest, $cacheKey);
            
            // Record successful cache operation
            $this->recordCacheSuccess();
            
            // Log performance metrics
            $this->logPerformanceMetrics('getCachedSummary', $manifest->id, $startTime, true);
            
            return $result;
            
        } catch (\Exception $e) {
            // Record cache failure
            $this->recordCacheFailure($e);
            
            Log::error('Failed to get cached summary, falling back to direct calculation', [
                'manifest_id' => $manifest->id,
                'cache_key' => $cacheKey,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'stack_trace' => $e->getTraceAsString()
            ]);
            
            // Log performance metrics for failed cache operation
            $this->logPerformanceMetrics('getCachedSummary', $manifest->id, $startTime, false);
            
            // Fallback to direct calculation with additional error handling
            return $this->getDirectSummaryWithFallback($manifest);
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
        $startTime = microtime(true);
        $cacheKey = $this->generateDisplayCacheKey($manifest);
        
        // Check circuit breaker status
        if ($this->isCircuitOpen()) {
            Log::warning('Cache circuit breaker is open, using direct calculation', [
                'manifest_id' => $manifest->id,
                'cache_key' => $cacheKey
            ]);
            
            return $this->getDirectDisplaySummaryWithFallback($manifest);
        }
        
        // Check cache health before attempting operation
        if (!$this->isCacheHealthy()) {
            Log::warning('Cache health check failed, using direct calculation', [
                'manifest_id' => $manifest->id,
                'cache_key' => $cacheKey,
                'health_status' => $this->healthStatus
            ]);
            
            return $this->getDirectDisplaySummaryWithFallback($manifest);
        }
        
        try {
            $result = $this->attemptCachedDisplaySummary($manifest, $cacheKey);
            
            // Record successful cache operation
            $this->recordCacheSuccess();
            
            // Log performance metrics
            $this->logPerformanceMetrics('getCachedDisplaySummary', $manifest->id, $startTime, true);
            
            return $result;
            
        } catch (\Exception $e) {
            // Record cache failure
            $this->recordCacheFailure($e);
            
            Log::error('Failed to get cached display summary, falling back to direct calculation', [
                'manifest_id' => $manifest->id,
                'cache_key' => $cacheKey,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'stack_trace' => $e->getTraceAsString()
            ]);
            
            // Log performance metrics for failed cache operation
            $this->logPerformanceMetrics('getCachedDisplaySummary', $manifest->id, $startTime, false);
            
            // Fallback to direct calculation with additional error handling
            return $this->getDirectDisplaySummaryWithFallback($manifest);
        }
    }

    /**
     * Invalidate cache for a specific manifest
     *
     * @param Manifest $manifest
     * @return void
     */
    // public function invalidateManifestCache(Manifest $manifest)
    // {
    //     try {
    //         $cacheKey = $this->generateCacheKey($manifest);
    //         $displayCacheKey = $this->generateDisplayCacheKey($manifest);
            
    //         Cache::forget($cacheKey);
    //         Cache::forget($displayCacheKey);
            
    //         Log::info('Invalidated manifest summary cache', [
    //             'manifest_id' => $manifest->id,
    //             'cache_keys' => [$cacheKey, $displayCacheKey]
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('Failed to invalidate manifest cache', [
    //             'manifest_id' => $manifest->id,
    //             'error' => $e->getMessage()
    //         ]);
    //     }
    // }

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
     * @return array
     */
    public function warmUpCache(Manifest $manifest): array
    {
        $startTime = microtime(true);
        $results = [
            'success' => false,
            'summary_cached' => false,
            'display_summary_cached' => false,
            'errors' => []
        ];
        
        try {
            // Check if cache is healthy before warming
            if (!$this->isCacheHealthy()) {
                $results['errors'][] = 'Cache is not healthy, skipping warm-up';
                
                Log::warning('Skipping cache warm-up due to unhealthy cache', [
                    'manifest_id' => $manifest->id,
                    'health_status' => $this->healthStatus
                ]);
                
                return $results;
            }
            
            // Check circuit breaker
            if ($this->isCircuitOpen()) {
                $results['errors'][] = 'Circuit breaker is open, skipping warm-up';
                
                Log::warning('Skipping cache warm-up due to open circuit breaker', [
                    'manifest_id' => $manifest->id
                ]);
                
                return $results;
            }
            
            // Pre-calculate and cache summary
            try {
                $this->getCachedSummary($manifest);
                $results['summary_cached'] = true;
            } catch (\Exception $e) {
                $results['errors'][] = 'Failed to cache summary: ' . $e->getMessage();
                
                Log::error('Failed to warm up summary cache', [
                    'manifest_id' => $manifest->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            // Pre-calculate and cache display summary
            try {
                $this->getCachedDisplaySummary($manifest);
                $results['display_summary_cached'] = true;
            } catch (\Exception $e) {
                $results['errors'][] = 'Failed to cache display summary: ' . $e->getMessage();
                
                Log::error('Failed to warm up display summary cache', [
                    'manifest_id' => $manifest->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            // Mark as successful if at least one cache operation succeeded
            $results['success'] = $results['summary_cached'] || $results['display_summary_cached'];
            
            if ($results['success']) {
                Log::info('Successfully warmed up cache for manifest', [
                    'manifest_id' => $manifest->id,
                    'summary_cached' => $results['summary_cached'],
                    'display_summary_cached' => $results['display_summary_cached'],
                    'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
                ]);
            }
            
        } catch (\Exception $e) {
            $results['errors'][] = 'Unexpected error during warm-up: ' . $e->getMessage();
            
            Log::error('Unexpected error during cache warm-up', [
                'manifest_id' => $manifest->id,
                'error' => $e->getMessage(),
                'error_type' => get_class($e)
            ]);
        }
        
        return $results;
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
    
    /**
     * Attempt to get cached summary with comprehensive error handling
     *
     * @param Manifest $manifest
     * @param string $cacheKey
     * @return array
     * @throws \Exception
     */
    protected function attemptCachedSummary(Manifest $manifest, string $cacheKey): array
    {
        // Validate manifest before proceeding
        if (!$manifest || !$manifest->id) {
            throw new \InvalidArgumentException('Invalid manifest provided for caching');
        }
        
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
    }
    
    /**
     * Get summary directly with comprehensive fallback handling
     *
     * @param Manifest $manifest
     * @return array
     */
    protected function getDirectSummaryWithFallback(Manifest $manifest): array
    {
        try {
            // Attempt direct calculation
            $result = $this->summaryService->getManifestSummary($manifest);
            
            // Validate result before returning
            if (!is_array($result) || empty($result)) {
                throw new \RuntimeException('Invalid summary data returned from service');
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Direct summary calculation failed, using emergency fallback', [
                'manifest_id' => $manifest->id,
                'error' => $e->getMessage(),
                'error_type' => get_class($e)
            ]);
            
            // Return emergency fallback data
            return $this->getEmergencySummaryFallback($manifest);
        }
    }
    
    /**
     * Get emergency fallback summary data
     *
     * @param Manifest $manifest
     * @return array
     */
    protected function getEmergencySummaryFallback(Manifest $manifest): array
    {
        try {
            // Provide minimal safe data structure
            $packageCount = $manifest->packages()->count() ?? 0;
            
            return [
                'package_count' => $packageCount,
                'total_value' => 0.0,
                'total_weight' => 0.0,
                'total_volume' => 0.0,
                'manifest_type' => $manifest->type ?? 'unknown',
                'incomplete_data' => true,
                'data_source' => 'emergency_fallback',
                'error_message' => 'Summary data temporarily unavailable'
            ];
            
        } catch (\Exception $e) {
            Log::critical('Emergency fallback summary generation failed', [
                'manifest_id' => $manifest->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            // Absolute minimal fallback
            return [
                'package_count' => 0,
                'total_value' => 0.0,
                'total_weight' => 0.0,
                'total_volume' => 0.0,
                'manifest_type' => 'unknown',
                'incomplete_data' => true,
                'data_source' => 'critical_fallback',
                'error_message' => 'System temporarily unavailable'
            ];
        }
    }
    
    /**
     * Attempt to get cached display summary with comprehensive error handling
     *
     * @param Manifest $manifest
     * @param string $cacheKey
     * @return array
     * @throws \Exception
     */
    protected function attemptCachedDisplaySummary(Manifest $manifest, string $cacheKey): array
    {
        // Validate manifest before proceeding
        if (!$manifest || !$manifest->id) {
            throw new \InvalidArgumentException('Invalid manifest provided for caching');
        }
        
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
    }
    
    /**
     * Get display summary directly with comprehensive fallback handling
     *
     * @param Manifest $manifest
     * @return array
     */
    protected function getDirectDisplaySummaryWithFallback(Manifest $manifest): array
    {
        try {
            // Attempt direct calculation
            $result = $this->summaryService->getDisplaySummary($manifest);
            
            // Validate result before returning
            if (!is_array($result) || empty($result)) {
                throw new \RuntimeException('Invalid summary data returned from service');
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Direct display summary calculation failed, using emergency fallback', [
                'manifest_id' => $manifest->id,
                'error' => $e->getMessage(),
                'error_type' => get_class($e)
            ]);
            
            // Return emergency fallback data
            return $this->getEmergencyDisplaySummaryFallback($manifest);
        }
    }
    
    /**
     * Get emergency fallback display summary data
     *
     * @param Manifest $manifest
     * @return array
     */
    protected function getEmergencyDisplaySummaryFallback(Manifest $manifest): array
    {
        try {
            // Provide minimal safe data structure
            $packageCount = $manifest->packages()->count() ?? 0;
            
            return [
                'manifest_type' => $manifest->type ?? 'unknown',
                'package_count' => $packageCount,
                'total_value' => 0.0,
                'primary_metric' => [
                    'label' => $manifest->type === 'sea' ? 'Volume' : 'Weight',
                    'value' => 0.0,
                    'unit' => $manifest->type === 'sea' ? 'CBM' : 'KG'
                ],
                'incomplete_data' => true,
                'data_source' => 'emergency_fallback',
                'error_message' => 'Summary data temporarily unavailable'
            ];
            
        } catch (\Exception $e) {
            Log::critical('Emergency fallback data generation failed', [
                'manifest_id' => $manifest->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            // Absolute minimal fallback
            return [
                'manifest_type' => 'unknown',
                'package_count' => 0,
                'total_value' => 0.0,
                'primary_metric' => [
                    'label' => 'Data',
                    'value' => 0.0,
                    'unit' => 'N/A'
                ],
                'incomplete_data' => true,
                'data_source' => 'critical_fallback',
                'error_message' => 'System temporarily unavailable'
            ];
        }
    }
    
    /**
     * Check if cache circuit breaker is open
     *
     * @return bool
     */
    protected function isCircuitOpen(): bool
    {
        try {
            $circuitKey = self::CIRCUIT_BREAKER_PREFIX . 'status';
            $circuitData = Cache::get($circuitKey);
            
            if (!$circuitData) {
                return false;
            }
            
            // Check if circuit should be closed (recovery attempt)
            if ($circuitData['opened_at'] && 
                Carbon::parse($circuitData['opened_at'])->addSeconds(self::RECOVERY_TIMEOUT)->isPast()) {
                
                // Attempt to close circuit
                $this->closeCircuit();
                return false;
            }
            
            return $circuitData['is_open'] ?? false;
            
        } catch (\Exception $e) {
            Log::warning('Failed to check circuit breaker status', [
                'error' => $e->getMessage()
            ]);
            
            // If we can't check circuit status, assume it's closed
            return false;
        }
    }
    
    /**
     * Record cache operation success
     *
     * @return void
     */
    protected function recordCacheSuccess(): void
    {
        try {
            $this->healthStatus['consecutive_failures'] = 0;
            $this->healthStatus['is_healthy'] = true;
            $this->healthStatus['last_check'] = Carbon::now();
            
            // Close circuit if it was open
            if ($this->healthStatus['circuit_open']) {
                $this->closeCircuit();
            }
            
        } catch (\Exception $e) {
            Log::warning('Failed to record cache success', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Record cache operation failure
     *
     * @param \Exception $exception
     * @return void
     */
    protected function recordCacheFailure(\Exception $exception): void
    {
        try {
            $this->healthStatus['consecutive_failures']++;
            $this->healthStatus['last_check'] = Carbon::now();
            
            // Open circuit if failure threshold is reached
            if ($this->healthStatus['consecutive_failures'] >= self::FAILURE_THRESHOLD) {
                $this->openCircuit();
            }
            
            // Update cache health status
            $this->updateCacheHealthStatus(false, $exception);
            
        } catch (\Exception $e) {
            Log::warning('Failed to record cache failure', [
                'original_error' => $exception->getMessage(),
                'recording_error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Open circuit breaker
     *
     * @return void
     */
    protected function openCircuit(): void
    {
        try {
            $circuitKey = self::CIRCUIT_BREAKER_PREFIX . 'status';
            $circuitData = [
                'is_open' => true,
                'opened_at' => Carbon::now()->toISOString(),
                'failure_count' => $this->healthStatus['consecutive_failures']
            ];
            
            Cache::put($circuitKey, $circuitData, self::CIRCUIT_BREAKER_TTL);
            
            $this->healthStatus['circuit_open'] = true;
            
            Log::warning('Cache circuit breaker opened due to repeated failures', [
                'failure_count' => $this->healthStatus['consecutive_failures'],
                'threshold' => self::FAILURE_THRESHOLD
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to open circuit breaker', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Close circuit breaker
     *
     * @return void
     */
    protected function closeCircuit(): void
    {
        try {
            $circuitKey = self::CIRCUIT_BREAKER_PREFIX . 'status';
            Cache::forget($circuitKey);
            
            $this->healthStatus['circuit_open'] = false;
            $this->healthStatus['consecutive_failures'] = 0;
            
            Log::info('Cache circuit breaker closed - attempting recovery');
            
        } catch (\Exception $e) {
            Log::error('Failed to close circuit breaker', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Check if cache is healthy
     *
     * @return bool
     */
    protected function isCacheHealthy(): bool
    {
        try {
            // Check if we need to perform health check
            if (!$this->healthStatus['last_check'] || 
                Carbon::parse($this->healthStatus['last_check'])->addSeconds(self::HEALTH_CHECK_INTERVAL)->isPast()) {
                
                $this->performHealthCheck();
            }
            
            return $this->healthStatus['is_healthy'];
            
        } catch (\Exception $e) {
            Log::warning('Cache health check failed', [
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Perform cache health check
     *
     * @return void
     */
    protected function performHealthCheck(): void
    {
        try {
            $healthKey = self::HEALTH_CHECK_PREFIX . 'test_' . time();
            $testValue = 'health_check_' . uniqid();
            
            // Test cache write
            Cache::put($healthKey, $testValue, 60);
            
            // Test cache read
            $retrieved = Cache::get($healthKey);
            
            // Test cache delete
            Cache::forget($healthKey);
            
            // Update health status
            $isHealthy = ($retrieved === $testValue);
            $this->updateCacheHealthStatus($isHealthy);
            
            if (!$isHealthy) {
                Log::warning('Cache health check failed - retrieved value does not match', [
                    'expected' => $testValue,
                    'retrieved' => $retrieved
                ]);
            }
            
        } catch (\Exception $e) {
            $this->updateCacheHealthStatus(false, $e);
            
            Log::error('Cache health check exception', [
                'error' => $e->getMessage(),
                'error_type' => get_class($e)
            ]);
        }
    }
    
    /**
     * Update cache health status
     *
     * @param bool $isHealthy
     * @param \Exception|null $exception
     * @return void
     */
    protected function updateCacheHealthStatus(bool $isHealthy, ?\Exception $exception = null): void
    {
        $this->healthStatus['is_healthy'] = $isHealthy;
        $this->healthStatus['last_check'] = Carbon::now();
        
        if (!$isHealthy) {
            $this->healthStatus['consecutive_failures']++;
        } else {
            $this->healthStatus['consecutive_failures'] = 0;
        }
        
        // Store health status in cache for monitoring
        try {
            $healthStatusKey = self::HEALTH_CHECK_PREFIX . 'status';
            $statusData = [
                'is_healthy' => $isHealthy,
                'last_check' => $this->healthStatus['last_check']->toISOString(),
                'consecutive_failures' => $this->healthStatus['consecutive_failures'],
                'circuit_open' => $this->healthStatus['circuit_open']
            ];
            
            if ($exception) {
                $statusData['last_error'] = [
                    'message' => $exception->getMessage(),
                    'type' => get_class($exception),
                    'time' => Carbon::now()->toISOString()
                ];
            }
            
            Cache::put($healthStatusKey, $statusData, self::HEALTH_CHECK_TTL);
            
        } catch (\Exception $e) {
            // Don't throw exception here to avoid infinite loops
            Log::warning('Failed to store cache health status', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Log performance metrics
     *
     * @param string $operation
     * @param int|null $manifestId
     * @param float $startTime
     * @param bool $cacheHit
     * @return void
     */
    protected function logPerformanceMetrics(string $operation, ?int $manifestId, float $startTime, bool $cacheHit): void
    {
        try {
            $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            
            Log::info('Cache operation performance', [
                'operation' => $operation,
                'manifest_id' => $manifestId ?? 'unknown',
                'execution_time_ms' => round($executionTime, 2),
                'cache_hit' => $cacheHit,
                'cache_healthy' => $this->healthStatus['is_healthy'],
                'circuit_open' => $this->healthStatus['circuit_open']
            ]);
            
        } catch (\Exception $e) {
            // Don't throw exception for logging failures
            Log::warning('Failed to log performance metrics', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Warm up cache for critical manifests
     *
     * @param array $manifestIds
     * @return array
     */
    public function warmUpCriticalManifests(array $manifestIds): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'skipped' => []
        ];
        
        foreach ($manifestIds as $manifestId) {
            try {
                $manifest = Manifest::find($manifestId);
                
                if (!$manifest) {
                    $results['skipped'][] = [
                        'manifest_id' => $manifestId,
                        'reason' => 'Manifest not found'
                    ];
                    continue;
                }
                
                // Check if cache is healthy before warming
                if (!$this->isCacheHealthy()) {
                    $results['skipped'][] = [
                        'manifest_id' => $manifestId,
                        'reason' => 'Cache unhealthy'
                    ];
                    continue;
                }
                
                // Warm up cache
                $this->warmUpCache($manifest);
                
                $results['success'][] = [
                    'manifest_id' => $manifestId,
                    'warmed_at' => Carbon::now()->toISOString()
                ];
                
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'manifest_id' => $manifestId,
                    'error' => $e->getMessage(),
                    'error_type' => get_class($e)
                ];
                
                Log::error('Failed to warm up cache for critical manifest', [
                    'manifest_id' => $manifestId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        Log::info('Critical manifest cache warming completed', [
            'total_manifests' => count($manifestIds),
            'successful' => count($results['success']),
            'failed' => count($results['failed']),
            'skipped' => count($results['skipped'])
        ]);
        
        return $results;
    }
    
    /**
     * Get comprehensive cache health report
     *
     * @return array
     */
    public function getCacheHealthReport(): array
    {
        try {
            $healthStatusKey = self::HEALTH_CHECK_PREFIX . 'status';
            $circuitStatusKey = self::CIRCUIT_BREAKER_PREFIX . 'status';
            
            $healthData = Cache::get($healthStatusKey, []);
            $circuitData = Cache::get($circuitStatusKey, []);
            
            return [
                'cache_available' => $this->isCacheAvailable(),
                'cache_healthy' => $this->isCacheHealthy(),
                'circuit_breaker' => [
                    'is_open' => $this->isCircuitOpen(),
                    'failure_threshold' => self::FAILURE_THRESHOLD,
                    'recovery_timeout' => self::RECOVERY_TIMEOUT,
                    'current_failures' => $this->healthStatus['consecutive_failures'],
                    'circuit_data' => $circuitData
                ],
                'health_monitoring' => [
                    'check_interval' => self::HEALTH_CHECK_INTERVAL,
                    'last_check' => $this->healthStatus['last_check'],
                    'health_data' => $healthData
                ],
                'cache_statistics' => $this->getCacheStatistics(),
                'performance_metrics' => [
                    'cache_ttl' => self::CACHE_TTL,
                    'supports_tags' => $this->supportsTags(),
                    'cache_driver' => config('cache.default')
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to generate cache health report', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'error' => 'Failed to generate health report',
                'cache_available' => false,
                'cache_healthy' => false
            ];
        }
    }

    /**
     * Invalidate cache for a specific manifest
     *
     * @param Manifest $manifest
     * @return bool
     */
    public function invalidateManifestCache(Manifest $manifest): bool
    {
        try {
            $summaryKey = $this->generateCacheKey($manifest);
            $displaySummaryKey = $this->generateDisplayCacheKey($manifest);
            
            // Remove both summary and display summary from cache
            $summaryRemoved = Cache::forget($summaryKey);
            $displaySummaryRemoved = Cache::forget($displaySummaryKey);
            
            Log::info('Invalidated manifest cache', [
                'manifest_id' => $manifest->id,
                'summary_removed' => $summaryRemoved,
                'display_summary_removed' => $displaySummaryRemoved
            ]);
            
            return $summaryRemoved || $displaySummaryRemoved;
            
        } catch (\Exception $e) {
            Log::error('Failed to invalidate manifest cache', [
                'manifest_id' => $manifest->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}