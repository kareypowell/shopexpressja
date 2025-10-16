<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReportMonitoringService
{
    const HEALTH_CHECK_CACHE_KEY = 'report_system_health';
    const PERFORMANCE_METRICS_KEY = 'report_performance_metrics';
    const ALERT_THRESHOLD_ERROR_RATE = 5; // errors per hour
    const ALERT_THRESHOLD_RESPONSE_TIME = 30; // seconds

    /**
     * Perform comprehensive health check of report system
     */
    public function performHealthCheck(): array
    {
        $healthStatus = [
            'overall_status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'checks' => []
        ];

        // Database connectivity check
        $healthStatus['checks']['database'] = $this->checkDatabaseHealth();
        
        // Cache system check
        $healthStatus['checks']['cache'] = $this->checkCacheHealth();
        
        // Report generation performance check
        $healthStatus['checks']['performance'] = $this->checkPerformanceHealth();
        
        // Error rate check
        $healthStatus['checks']['error_rate'] = $this->checkErrorRateHealth();
        
        // Queue system check (for exports)
        $healthStatus['checks']['queue'] = $this->checkQueueHealth();
        
        // Determine overall status
        $healthStatus['overall_status'] = $this->determineOverallHealth($healthStatus['checks']);
        
        // Cache the health status
        Cache::put(self::HEALTH_CHECK_CACHE_KEY, $healthStatus, 300); // 5 minutes
        
        // Log health status if there are issues
        if ($healthStatus['overall_status'] !== 'healthy') {
            Log::warning('Report System Health Check Failed', $healthStatus);
        }
        
        return $healthStatus;
    }

    /**
     * Check database health for report queries
     */
    protected function checkDatabaseHealth(): array
    {
        try {
            $startTime = microtime(true);
            
            // Test basic connectivity
            DB::select('SELECT 1');
            
            // Test report-related tables
            $packageCount = DB::table('packages')->count();
            $manifestCount = DB::table('manifests')->count();
            $userCount = DB::table('users')->count();
            
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            return [
                'status' => 'healthy',
                'response_time_ms' => round($responseTime, 2),
                'package_count' => $packageCount,
                'manifest_count' => $manifestCount,
                'user_count' => $userCount
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'response_time_ms' => null
            ];
        }
    }

    /**
     * Check cache system health
     */
    protected function checkCacheHealth(): array
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';
            
            // Test cache write
            Cache::put($testKey, $testValue, 60);
            
            // Test cache read
            $retrievedValue = Cache::get($testKey);
            
            // Clean up
            Cache::forget($testKey);
            
            $isWorking = $retrievedValue === $testValue;
            
            return [
                'status' => $isWorking ? 'healthy' : 'unhealthy',
                'read_write_test' => $isWorking,
                'driver' => config('cache.default')
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'driver' => config('cache.default')
            ];
        }
    }

    /**
     * Check report generation performance
     */
    protected function checkPerformanceHealth(): array
    {
        $metrics = Cache::get(self::PERFORMANCE_METRICS_KEY, []);
        
        if (empty($metrics)) {
            return [
                'status' => 'unknown',
                'message' => 'No performance data available'
            ];
        }

        $avgResponseTime = collect($metrics)->avg('response_time');
        $maxResponseTime = collect($metrics)->max('response_time');
        $recentMetrics = collect($metrics)->where('timestamp', '>', now()->subHour());
        
        $status = 'healthy';
        if ($avgResponseTime > self::ALERT_THRESHOLD_RESPONSE_TIME) {
            $status = 'degraded';
        }
        if ($maxResponseTime > self::ALERT_THRESHOLD_RESPONSE_TIME * 2) {
            $status = 'unhealthy';
        }

        return [
            'status' => $status,
            'avg_response_time' => round($avgResponseTime, 2),
            'max_response_time' => round($maxResponseTime, 2),
            'recent_requests' => $recentMetrics->count(),
            'threshold_seconds' => self::ALERT_THRESHOLD_RESPONSE_TIME
        ];
    }

    /**
     * Check error rate health
     */
    protected function checkErrorRateHealth(): array
    {
        $errorHandlingService = app(ReportErrorHandlingService::class);
        
        $reportTypes = ['sales', 'manifest', 'customer', 'financial'];
        $totalErrors = 0;
        $errorsByType = [];
        
        foreach ($reportTypes as $type) {
            $stats = $errorHandlingService->getErrorStatistics($type, 1);
            $errorsByType[$type] = $stats['total_errors'];
            $totalErrors += $stats['total_errors'];
        }
        
        $status = 'healthy';
        if ($totalErrors > self::ALERT_THRESHOLD_ERROR_RATE) {
            $status = $totalErrors > self::ALERT_THRESHOLD_ERROR_RATE * 2 ? 'unhealthy' : 'degraded';
        }
        
        return [
            'status' => $status,
            'total_errors_last_hour' => $totalErrors,
            'errors_by_type' => $errorsByType,
            'threshold' => self::ALERT_THRESHOLD_ERROR_RATE
        ];
    }

    /**
     * Check queue system health for report exports
     */
    protected function checkQueueHealth(): array
    {
        try {
            // Check failed jobs
            $failedJobs = DB::table('failed_jobs')
                ->where('payload', 'like', '%ReportExport%')
                ->where('failed_at', '>', now()->subDay())
                ->count();
            
            // Check pending export jobs
            $pendingExports = DB::table('report_export_jobs')
                ->where('status', 'pending')
                ->where('created_at', '>', now()->subHour())
                ->count();
            
            $status = 'healthy';
            if ($failedJobs > 5 || $pendingExports > 10) {
                $status = 'degraded';
            }
            if ($failedJobs > 20 || $pendingExports > 50) {
                $status = 'unhealthy';
            }
            
            return [
                'status' => $status,
                'failed_jobs_24h' => $failedJobs,
                'pending_exports' => $pendingExports,
                'queue_connection' => config('queue.default')
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Determine overall system health
     */
    protected function determineOverallHealth(array $checks): string
    {
        $statuses = collect($checks)->pluck('status');
        
        if ($statuses->contains('unhealthy')) {
            return 'unhealthy';
        }
        
        if ($statuses->contains('degraded')) {
            return 'degraded';
        }
        
        if ($statuses->contains('unknown')) {
            return 'unknown';
        }
        
        return 'healthy';
    }

    /**
     * Record performance metrics for monitoring
     */
    public function recordPerformanceMetric(string $reportType, float $responseTime, array $context = []): void
    {
        $metric = [
            'report_type' => $reportType,
            'response_time' => $responseTime,
            'timestamp' => now()->toISOString(),
            'memory_usage' => memory_get_usage(true),
            'context' => $context
        ];
        
        try {
            // Get existing metrics
            $metrics = Cache::get(self::PERFORMANCE_METRICS_KEY, []);
            
            // Add new metric
            $metrics[] = $metric;
            
            // Keep only last 100 metrics
            if (count($metrics) > 100) {
                $metrics = array_slice($metrics, -100);
            }
            
            // Store back to cache
            Cache::put(self::PERFORMANCE_METRICS_KEY, $metrics, 3600); // 1 hour
        } catch (\Exception $e) {
            // If cache fails, just log the metric directly
            Log::info("Performance Metric (Cache Failed)", $metric);
        }
        
        // Log slow queries
        if ($responseTime > self::ALERT_THRESHOLD_RESPONSE_TIME) {
            Log::warning("Slow Report Generation Detected", $metric);
        }
    }

    /**
     * Get system performance statistics
     */
    public function getPerformanceStatistics(int $hours = 24): array
    {
        $metrics = Cache::get(self::PERFORMANCE_METRICS_KEY, []);
        $cutoff = now()->subHours($hours);
        
        $recentMetrics = collect($metrics)
            ->filter(fn($metric) => Carbon::parse($metric['timestamp'])->gt($cutoff));
        
        if ($recentMetrics->isEmpty()) {
            return [
                'total_requests' => 0,
                'avg_response_time' => 0,
                'max_response_time' => 0,
                'min_response_time' => 0,
                'by_report_type' => []
            ];
        }
        
        $byType = $recentMetrics->groupBy('report_type')->map(function ($typeMetrics) {
            return [
                'count' => $typeMetrics->count(),
                'avg_response_time' => $typeMetrics->avg('response_time'),
                'max_response_time' => $typeMetrics->max('response_time'),
                'min_response_time' => $typeMetrics->min('response_time')
            ];
        });
        
        return [
            'total_requests' => $recentMetrics->count(),
            'avg_response_time' => round($recentMetrics->avg('response_time'), 2),
            'max_response_time' => round($recentMetrics->max('response_time'), 2),
            'min_response_time' => round($recentMetrics->min('response_time'), 2),
            'by_report_type' => $byType->toArray()
        ];
    }

    /**
     * Check if system should trigger alerts
     */
    public function shouldTriggerAlert(): array
    {
        $health = $this->performHealthCheck();
        $alerts = [];
        
        // Check overall health
        if ($health['overall_status'] === 'unhealthy') {
            $alerts[] = [
                'type' => 'critical',
                'message' => 'Report system is unhealthy',
                'details' => $health['checks']
            ];
        } elseif ($health['overall_status'] === 'degraded') {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Report system performance is degraded',
                'details' => $health['checks']
            ];
        }
        
        // Check specific thresholds
        if (isset($health['checks']['error_rate']) && 
            $health['checks']['error_rate']['total_errors_last_hour'] > self::ALERT_THRESHOLD_ERROR_RATE) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'High error rate detected in report system',
                'details' => $health['checks']['error_rate']
            ];
        }
        
        return $alerts;
    }

    /**
     * Get cached health status
     */
    public function getCachedHealthStatus(): ?array
    {
        return Cache::get(self::HEALTH_CHECK_CACHE_KEY);
    }
}