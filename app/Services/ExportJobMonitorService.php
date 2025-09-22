<?php

namespace App\Services;

use App\Models\ReportExportJob;
use App\Notifications\ExportJobStuckNotification;
use App\Notifications\ExportSystemHealthNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExportJobMonitorService
{
    /**
     * Monitor stuck export jobs and send alerts
     */
    public function monitorStuckJobs(): array
    {
        $stuckThreshold = now()->subMinutes(30); // Jobs stuck for more than 30 minutes
        
        $stuckJobs = ReportExportJob::where('status', 'processing')
            ->where('started_at', '<', $stuckThreshold)
            ->get();

        $results = [
            'stuck_jobs_found' => $stuckJobs->count(),
            'jobs_reset' => 0,
            'notifications_sent' => 0
        ];

        foreach ($stuckJobs as $job) {
            try {
                // Reset job to failed status
                $job->update([
                    'status' => 'failed',
                    'error_message' => 'Job exceeded maximum processing time and was automatically reset',
                    'completed_at' => now()
                ]);

                $results['jobs_reset']++;

                // Notify user
                if ($job->user) {
                    $job->user->notify(new ExportJobStuckNotification($job));
                    $results['notifications_sent']++;
                }

                Log::warning("Reset stuck export job", [
                    'job_id' => $job->id,
                    'user_id' => $job->user_id,
                    'started_at' => $job->started_at,
                    'processing_time' => $job->started_at->diffInMinutes(now())
                ]);

            } catch (\Exception $e) {
                Log::error("Failed to reset stuck export job {$job->id}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Get export system health metrics
     */
    public function getSystemHealth(): array
    {
        $now = now();
        $last24Hours = $now->copy()->subHours(24);
        $lastHour = $now->copy()->subHour();

        return [
            'queue_health' => $this->getQueueHealth(),
            'job_statistics' => $this->getJobStatistics($last24Hours),
            'performance_metrics' => $this->getPerformanceMetrics($last24Hours),
            'error_analysis' => $this->getErrorAnalysis($last24Hours),
            'storage_usage' => $this->getStorageUsage(),
            'system_load' => $this->getSystemLoad($lastHour)
        ];
    }

    /**
     * Get queue health status
     */
    protected function getQueueHealth(): array
    {
        $queuedJobs = ReportExportJob::where('status', 'queued')->count();
        $processingJobs = ReportExportJob::where('status', 'processing')->count();
        $oldestQueued = ReportExportJob::where('status', 'queued')
            ->orderBy('created_at')
            ->first();

        return [
            'queued_jobs' => $queuedJobs,
            'processing_jobs' => $processingJobs,
            'oldest_queued_age' => $oldestQueued ? $oldestQueued->created_at->diffInMinutes(now()) : 0,
            'queue_status' => $this->determineQueueStatus($queuedJobs, $processingJobs, $oldestQueued)
        ];
    }

    /**
     * Get job statistics for time period
     */
    protected function getJobStatistics(Carbon $since): array
    {
        $jobs = ReportExportJob::where('created_at', '>=', $since);

        return [
            'total_jobs' => $jobs->count(),
            'completed_jobs' => $jobs->where('status', 'completed')->count(),
            'failed_jobs' => $jobs->where('status', 'failed')->count(),
            'cancelled_jobs' => $jobs->where('status', 'cancelled')->count(),
            'success_rate' => $this->calculateSuccessRate($jobs),
            'jobs_by_type' => $this->getJobsByType($jobs),
            'jobs_by_format' => $this->getJobsByFormat($jobs)
        ];
    }

    /**
     * Get performance metrics
     */
    protected function getPerformanceMetrics(Carbon $since): array
    {
        $completedJobs = ReportExportJob::where('status', 'completed')
            ->where('created_at', '>=', $since)
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->get();

        if ($completedJobs->isEmpty()) {
            return [
                'avg_processing_time' => 0,
                'median_processing_time' => 0,
                'max_processing_time' => 0,
                'min_processing_time' => 0
            ];
        }

        $processingTimes = $completedJobs->map(function ($job) {
            return $job->started_at->diffInSeconds($job->completed_at);
        })->sort()->values();

        return [
            'avg_processing_time' => $processingTimes->avg(),
            'median_processing_time' => $processingTimes->median(),
            'max_processing_time' => $processingTimes->max(),
            'min_processing_time' => $processingTimes->min()
        ];
    }

    /**
     * Get error analysis
     */
    protected function getErrorAnalysis(Carbon $since): array
    {
        $failedJobs = ReportExportJob::where('status', 'failed')
            ->where('created_at', '>=', $since)
            ->whereNotNull('error_message')
            ->get();

        $errorPatterns = [];
        foreach ($failedJobs as $job) {
            $errorType = $this->categorizeError($job->error_message);
            $errorPatterns[$errorType] = ($errorPatterns[$errorType] ?? 0) + 1;
        }

        return [
            'total_failures' => $failedJobs->count(),
            'error_patterns' => $errorPatterns,
            'most_common_error' => $errorPatterns ? array_keys($errorPatterns, max($errorPatterns))[0] : null
        ];
    }

    /**
     * Get storage usage statistics
     */
    protected function getStorageUsage(): array
    {
        $exportPath = storage_path('app/exports');
        
        if (!is_dir($exportPath)) {
            return ['total_size' => 0, 'file_count' => 0, 'avg_file_size' => 0];
        }

        $totalSize = 0;
        $fileCount = 0;

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($exportPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $totalSize += $file->getSize();
                    $fileCount++;
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to calculate storage usage: " . $e->getMessage());
        }

        return [
            'total_size' => $totalSize,
            'file_count' => $fileCount,
            'avg_file_size' => $fileCount > 0 ? $totalSize / $fileCount : 0
        ];
    }

    /**
     * Get system load metrics
     */
    protected function getSystemLoad(Carbon $since): array
    {
        $recentJobs = ReportExportJob::where('created_at', '>=', $since)->count();
        $concurrentJobs = ReportExportJob::where('status', 'processing')->count();

        return [
            'jobs_per_hour' => $recentJobs,
            'concurrent_jobs' => $concurrentJobs,
            'load_level' => $this->determineLoadLevel($recentJobs, $concurrentJobs)
        ];
    }

    /**
     * Calculate success rate
     */
    protected function calculateSuccessRate($jobs): float
    {
        $total = $jobs->count();
        if ($total === 0) return 100.0;

        $successful = $jobs->where('status', 'completed')->count();
        return ($successful / $total) * 100;
    }

    /**
     * Get jobs grouped by type
     */
    protected function getJobsByType($jobs): array
    {
        return $jobs->get()->groupBy('report_type')->map->count()->toArray();
    }

    /**
     * Get jobs grouped by format
     */
    protected function getJobsByFormat($jobs): array
    {
        return $jobs->get()->groupBy('export_format')->map->count()->toArray();
    }

    /**
     * Determine queue status
     */
    protected function determineQueueStatus(int $queued, int $processing, $oldestQueued): string
    {
        if ($queued > 50) return 'overloaded';
        if ($oldestQueued && $oldestQueued->created_at->diffInMinutes(now()) > 15) return 'delayed';
        if ($processing > 10) return 'busy';
        return 'healthy';
    }

    /**
     * Categorize error message
     */
    protected function categorizeError(string $errorMessage): string
    {
        $lowerError = strtolower($errorMessage);

        if (str_contains($lowerError, 'memory')) return 'memory_error';
        if (str_contains($lowerError, 'timeout')) return 'timeout_error';
        if (str_contains($lowerError, 'database')) return 'database_error';
        if (str_contains($lowerError, 'file') || str_contains($lowerError, 'storage')) return 'file_error';
        if (str_contains($lowerError, 'pdf')) return 'pdf_generation_error';
        if (str_contains($lowerError, 'csv') || str_contains($lowerError, 'excel')) return 'csv_generation_error';

        return 'unknown_error';
    }

    /**
     * Determine system load level
     */
    protected function determineLoadLevel(int $jobsPerHour, int $concurrentJobs): string
    {
        if ($jobsPerHour > 100 || $concurrentJobs > 20) return 'high';
        if ($jobsPerHour > 50 || $concurrentJobs > 10) return 'medium';
        return 'low';
    }

    /**
     * Send health alert if needed
     */
    public function checkAndSendHealthAlert(): void
    {
        $health = $this->getSystemHealth();
        
        $alertConditions = [
            $health['queue_health']['queue_status'] === 'overloaded',
            $health['job_statistics']['success_rate'] < 80,
            $health['system_load']['load_level'] === 'high',
            $health['storage_usage']['total_size'] > 5 * 1024 * 1024 * 1024 // 5GB
        ];

        if (array_filter($alertConditions)) {
            // Send alert to administrators
            $admins = \App\Models\User::whereHas('role', function ($query) {
                $query->where('name', 'admin');
            })->get();

            foreach ($admins as $admin) {
                $admin->notify(new ExportSystemHealthNotification($health));
            }

            // Cache the alert to prevent spam
            Cache::put('export_health_alert_sent', true, now()->addHours(1));
        }
    }
}