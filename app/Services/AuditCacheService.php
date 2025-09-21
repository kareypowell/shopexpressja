<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuditCacheService
{
    /**
     * Cache TTL in minutes
     */
    const CACHE_TTL = 60; // 1 hour
    const STATS_CACHE_TTL = 30; // 30 minutes for statistics
    const SUMMARY_CACHE_TTL = 15; // 15 minutes for summaries

    /**
     * Get cached audit statistics for dashboard
     */
    public function getAuditStatistics(int $days = 7): array
    {
        $cacheKey = "audit_statistics_{$days}_days";
        
        return Cache::remember($cacheKey, self::STATS_CACHE_TTL, function () use ($days) {
            $since = now()->subDays($days);
            
            return [
                'total_entries' => AuditLog::where('created_at', '>=', $since)->count(),
                'entries_by_type' => $this->getEntriesByType($since),
                'entries_by_day' => $this->getEntriesByDay($since),
                'top_users' => $this->getTopUsers($since),
                'security_events' => $this->getSecurityEventsSummary($since),
                'performance_metrics' => $this->getPerformanceMetrics($since),
            ];
        });
    }

    /**
     * Get cached security events summary
     */
    public function getSecurityEventsSummary(?Carbon $since = null): array
    {
        $since = $since ?? now()->subHours(24);
        $cacheKey = "security_events_summary_" . $since->format('Y-m-d-H');
        
        return Cache::remember($cacheKey, self::SUMMARY_CACHE_TTL, function () use ($since) {
            $events = AuditLog::where('event_type', 'security_event')
                ->where('created_at', '>=', $since)
                ->get();

            return [
                'total_events' => $events->count(),
                'failed_logins' => $events->where('action', 'failed_authentication')->count(),
                'suspicious_activities' => $events->where('action', 'suspicious_activity_detected')->count(),
                'security_alerts' => $events->where('action', 'security_alert_generated')->count(),
                'unique_ips' => $events->pluck('ip_address')->filter()->unique()->count(),
                'events_by_severity' => $events->groupBy(function ($event) {
                    return $event->additional_data['severity'] ?? 'unknown';
                })->map->count(),
                'recent_events' => $events->sortByDesc('created_at')->take(10)->values()->toArray()
            ];
        });
    }

    /**
     * Get cached user activity summary
     */
    public function getUserActivitySummary(int $userId, int $days = 30): array
    {
        $cacheKey = "user_activity_summary_{$userId}_{$days}_days";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $days) {
            $since = now()->subDays($days);
            
            $activities = AuditLog::where('user_id', $userId)
                ->where('created_at', '>=', $since)
                ->get();

            return [
                'total_activities' => $activities->count(),
                'activities_by_type' => $activities->groupBy('event_type')->map->count(),
                'activities_by_day' => $activities->groupBy(function ($activity) {
                    return $activity->created_at->format('Y-m-d');
                })->map->count(),
                'recent_ips' => $activities->pluck('ip_address')->filter()->unique()->values()->toArray(),
                'last_activity' => $activities->max('created_at'),
                'most_common_actions' => $activities->groupBy('action')->map->count()->sortDesc()->take(5),
            ];
        });
    }

    /**
     * Get cached model audit trail summary
     */
    public function getModelAuditTrail(string $modelType, int $modelId, int $limit = 50): array
    {
        $cacheKey = "model_audit_trail_{$modelType}_{$modelId}_{$limit}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($modelType, $modelId, $limit) {
            return AuditLog::where('auditable_type', $modelType)
                ->where('auditable_id', $modelId)
                ->with('user:id,name,email')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    /**
     * Get cached audit log counts by event type
     */
    public function getEventTypeCounts(int $hours = 24): array
    {
        $cacheKey = "event_type_counts_{$hours}_hours";
        
        return Cache::remember($cacheKey, self::SUMMARY_CACHE_TTL, function () use ($hours) {
            $since = now()->subHours($hours);
            
            return AuditLog::where('created_at', '>=', $since)
                ->select('event_type', DB::raw('count(*) as count'))
                ->groupBy('event_type')
                ->pluck('count', 'event_type')
                ->toArray();
        });
    }

    /**
     * Get cached failed authentication attempts
     */
    public function getFailedAuthenticationAttempts(int $hours = 24): array
    {
        $cacheKey = "failed_auth_attempts_{$hours}_hours";
        
        return Cache::remember($cacheKey, self::SUMMARY_CACHE_TTL, function () use ($hours) {
            $since = now()->subHours($hours);
            
            return AuditLog::where('event_type', 'authentication')
                ->where('action', 'failed_authentication')
                ->where('created_at', '>=', $since)
                ->select('ip_address', DB::raw('count(*) as attempts'))
                ->groupBy('ip_address')
                ->orderByDesc('attempts')
                ->limit(20)
                ->get()
                ->toArray();
        });
    }

    /**
     * Get cached audit performance metrics
     */
    public function getPerformanceMetrics(?Carbon $since = null): array
    {
        $since = $since ?? now()->subHours(24);
        $cacheKey = "audit_performance_metrics_" . $since->format('Y-m-d-H');
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($since) {
            $totalLogs = AuditLog::where('created_at', '>=', $since)->count();
            $avgLogsPerHour = $totalLogs / max(1, $since->diffInHours(now()));
            
            return [
                'total_logs' => $totalLogs,
                'avg_logs_per_hour' => round($avgLogsPerHour, 2),
                'peak_hour' => $this->getPeakHour($since),
                'database_size_mb' => $this->getAuditTableSize(),
                'oldest_log_age_days' => $this->getOldestLogAge(),
            ];
        });
    }

    /**
     * Invalidate user-specific caches
     */
    public function invalidateUserCache(int $userId): void
    {
        $patterns = [
            "user_activity_summary_{$userId}_*",
        ];
        
        foreach ($patterns as $pattern) {
            $this->forgetCachePattern($pattern);
        }
    }

    /**
     * Invalidate model-specific caches
     */
    public function invalidateModelCache(string $modelType, int $modelId): void
    {
        $patterns = [
            "model_audit_trail_{$modelType}_{$modelId}_*",
        ];
        
        foreach ($patterns as $pattern) {
            $this->forgetCachePattern($pattern);
        }
    }

    /**
     * Invalidate all audit statistics caches
     */
    public function invalidateStatisticsCaches(): void
    {
        $patterns = [
            'audit_statistics_*',
            'security_events_summary_*',
            'event_type_counts_*',
            'failed_auth_attempts_*',
            'audit_performance_metrics_*',
        ];
        
        foreach ($patterns as $pattern) {
            $this->forgetCachePattern($pattern);
        }
    }

    /**
     * Warm up frequently accessed caches
     */
    public function warmUpCaches(): void
    {
        // Warm up common statistics
        $this->getAuditStatistics(7);
        $this->getAuditStatistics(30);
        
        // Warm up security summaries
        $this->getSecurityEventsSummary();
        
        // Warm up event type counts
        $this->getEventTypeCounts(24);
        $this->getEventTypeCounts(168); // 1 week
        
        // Warm up failed authentication attempts
        $this->getFailedAuthenticationAttempts(24);
    }

    /**
     * Get entries by type with caching optimization
     */
    private function getEntriesByType(Carbon $since): array
    {
        return AuditLog::where('created_at', '>=', $since)
            ->select('event_type', DB::raw('count(*) as count'))
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();
    }

    /**
     * Get entries by day with caching optimization
     */
    private function getEntriesByDay(Carbon $since): array
    {
        return AuditLog::where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('count', 'date')
            ->toArray();
    }

    /**
     * Get top users with caching optimization
     */
    private function getTopUsers(Carbon $since): array
    {
        return AuditLog::where('created_at', '>=', $since)
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('count(*) as count'))
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->limit(10)
            ->with('user:id,name,email')
            ->get()
            ->toArray();
    }

    /**
     * Get peak hour for audit logs
     */
    private function getPeakHour(Carbon $since): ?int
    {
        $hourCounts = AuditLog::where('created_at', '>=', $since)
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderByDesc('count')
            ->first();
            
        return $hourCounts ? $hourCounts->hour : null;
    }

    /**
     * Get audit table size in MB
     */
    private function getAuditTableSize(): float
    {
        $result = DB::select("
            SELECT 
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = 'audit_logs'
        ");
        
        return $result[0]->size_mb ?? 0;
    }

    /**
     * Get oldest log age in days
     */
    private function getOldestLogAge(): int
    {
        $oldestLog = AuditLog::orderBy('created_at')->first();
        
        return $oldestLog ? $oldestLog->created_at->diffInDays(now()) : 0;
    }

    /**
     * Forget cache keys matching a pattern
     */
    private function forgetCachePattern(string $pattern): void
    {
        try {
            // Check if we're using Redis cache
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $keys = Cache::getRedis()->keys($pattern);
                
                if (!empty($keys)) {
                    Cache::getRedis()->del($keys);
                }
            } else {
                // For other cache drivers, we'll use a simpler approach
                // This is less efficient but works with all cache drivers
                $this->forgetKnownCacheKeys($pattern);
            }
        } catch (\Exception $e) {
            // Silently handle cache invalidation errors
            // Cache invalidation should not break application flow
            \Log::warning('Cache pattern invalidation failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Forget known cache keys for non-Redis drivers
     */
    private function forgetKnownCacheKeys(string $pattern): void
    {
        // Define known cache key patterns and their variations
        $knownKeys = [
            'audit_statistics_7_days',
            'audit_statistics_30_days',
            'security_events_summary_' . now()->format('Y-m-d-H'),
            'event_type_counts_24_hours',
            'event_type_counts_168_hours',
            'failed_auth_attempts_24_hours',
            'audit_performance_metrics_' . now()->format('Y-m-d-H'),
        ];

        foreach ($knownKeys as $key) {
            if (str_contains($key, str_replace('*', '', $pattern))) {
                Cache::forget($key);
            }
        }
    }
}