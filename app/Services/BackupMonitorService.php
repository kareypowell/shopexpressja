<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\BackupSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BackupMonitorService
{
    /**
     * Get overall backup system health status
     */
    public function getSystemHealth(): array
    {
        return [
            'overall_status' => $this->getOverallStatus(),
            'recent_backups' => $this->getRecentBackupStatus(),
            'storage_usage' => $this->getStorageUsage(),
            'schedule_health' => $this->getScheduleHealth(),
            'failed_backups' => $this->getFailedBackups(),
            'warnings' => $this->getSystemWarnings(),
        ];
    }

    /**
     * Get recent backup status for dashboard
     */
    public function getRecentBackupStatus(int $days = 7): array
    {
        $recentBackups = Backup::where('created_at', '>=', Carbon::now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();

        $successful = $recentBackups->where('status', 'completed')->count();
        $failed = $recentBackups->where('status', 'failed')->count();
        $total = $recentBackups->count();

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
            'last_successful' => $this->getLastSuccessfulBackup(),
            'last_failed' => $this->getLastFailedBackup(),
        ];
    }

    /**
     * Get storage usage information
     */
    public function getStorageUsage(): array
    {
        $backupPath = config('backup.storage.path', 'storage/app/backups');
        $maxSize = config('backup.storage.max_file_size', 2048) * 1024 * 1024; // Convert MB to bytes
        
        $totalSize = 0;
        $fileCount = 0;
        
        // Handle both absolute and relative paths
        if (str_starts_with($backupPath, '/')) {
            // Absolute path - use direct file operations
            if (file_exists($backupPath)) {
                $files = glob($backupPath . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $totalSize += filesize($file);
                        $fileCount++;
                    }
                }
            }
        } else {
            // Relative path - use Storage facade
            if (Storage::exists($backupPath)) {
                $files = Storage::allFiles($backupPath);
                foreach ($files as $file) {
                    $totalSize += Storage::size($file);
                    $fileCount++;
                }
            }
        }

        $usagePercentage = $maxSize > 0 ? round(($totalSize / $maxSize) * 100, 2) : 0;

        return [
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / (1024 * 1024), 2),
            'file_count' => $fileCount,
            'max_size_mb' => $maxSize / (1024 * 1024),
            'usage_percentage' => $usagePercentage,
            'is_critical' => $usagePercentage > 90,
            'is_warning' => $usagePercentage > 75,
        ];
    }

    /**
     * Check backup schedule health
     */
    public function getScheduleHealth(): array
    {
        $schedules = BackupSchedule::where('is_active', true)->get();
        $healthySchedules = 0;
        $overdueSchedules = [];

        foreach ($schedules as $schedule) {
            if ($this->isScheduleHealthy($schedule)) {
                $healthySchedules++;
            } else {
                $overdueSchedules[] = [
                    'id' => $schedule->id,
                    'name' => $schedule->name,
                    'last_run' => $schedule->last_run_at,
                    'next_run' => $schedule->next_run_at,
                    'overdue_hours' => $this->getOverdueHours($schedule),
                ];
            }
        }

        return [
            'total_schedules' => $schedules->count(),
            'healthy_schedules' => $healthySchedules,
            'overdue_schedules' => $overdueSchedules,
            'health_percentage' => $schedules->count() > 0 ? 
                round(($healthySchedules / $schedules->count()) * 100, 2) : 100,
        ];
    }

    /**
     * Get failed backups that need attention
     */
    public function getFailedBackups(int $days = 7): Collection
    {
        return Backup::where('status', 'failed')
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($backup) {
                return [
                    'id' => $backup->id,
                    'name' => $backup->name,
                    'type' => $backup->type,
                    'created_at' => $backup->created_at,
                    'error_message' => $backup->metadata['error_message'] ?? 'Unknown error',
                ];
            });
    }

    /**
     * Get system warnings
     */
    public function getSystemWarnings(): array
    {
        $warnings = [];
        
        // Storage warnings
        $storage = $this->getStorageUsage();
        if ($storage['is_critical']) {
            $warnings[] = [
                'type' => 'storage_critical',
                'message' => "Backup storage is {$storage['usage_percentage']}% full",
                'severity' => 'critical',
            ];
        } elseif ($storage['is_warning']) {
            $warnings[] = [
                'type' => 'storage_warning',
                'message' => "Backup storage is {$storage['usage_percentage']}% full",
                'severity' => 'warning',
            ];
        }

        // Schedule warnings
        $scheduleHealth = $this->getScheduleHealth();
        if (!empty($scheduleHealth['overdue_schedules'])) {
            $warnings[] = [
                'type' => 'schedule_overdue',
                'message' => count($scheduleHealth['overdue_schedules']) . ' backup schedule(s) are overdue',
                'severity' => 'warning',
            ];
        }

        // Recent failure warnings
        $recentStatus = $this->getRecentBackupStatus(1);
        if ($recentStatus['failed'] > 0) {
            $warnings[] = [
                'type' => 'recent_failures',
                'message' => "{$recentStatus['failed']} backup(s) failed in the last 24 hours",
                'severity' => 'warning',
            ];
        }

        return $warnings;
    }

    /**
     * Check if backup monitoring should send alerts
     */
    public function shouldSendAlert(): bool
    {
        $warnings = $this->getSystemWarnings();
        $criticalWarnings = collect($warnings)->where('severity', 'critical');
        
        return $criticalWarnings->isNotEmpty() || $this->hasRecentFailures();
    }

    /**
     * Get overall system status
     */
    private function getOverallStatus(): string
    {
        $warnings = $this->getSystemWarnings();
        $criticalWarnings = collect($warnings)->where('severity', 'critical');
        
        if ($criticalWarnings->isNotEmpty()) {
            return 'critical';
        }
        
        if (!empty($warnings)) {
            return 'warning';
        }
        
        return 'healthy';
    }

    /**
     * Get last successful backup
     */
    private function getLastSuccessfulBackup(): ?array
    {
        $backup = Backup::where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$backup) {
            return null;
        }

        return [
            'id' => $backup->id,
            'name' => $backup->name,
            'type' => $backup->type,
            'created_at' => $backup->created_at,
            'file_size' => $backup->file_size,
        ];
    }

    /**
     * Get last failed backup
     */
    private function getLastFailedBackup(): ?array
    {
        $backup = Backup::where('status', 'failed')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$backup) {
            return null;
        }

        return [
            'id' => $backup->id,
            'name' => $backup->name,
            'type' => $backup->type,
            'created_at' => $backup->created_at,
            'error_message' => $backup->metadata['error_message'] ?? 'Unknown error',
        ];
    }

    /**
     * Check if a schedule is healthy
     */
    private function isScheduleHealthy(BackupSchedule $schedule): bool
    {
        if (!$schedule->last_run_at) {
            return false;
        }

        $overdueHours = $this->getOverdueHours($schedule);
        return $overdueHours <= 2; // Allow 2 hours grace period
    }

    /**
     * Get overdue hours for a schedule
     */
    private function getOverdueHours(BackupSchedule $schedule): float
    {
        if (!$schedule->next_run_at) {
            return 0;
        }

        $now = Carbon::now();
        if ($now->lt($schedule->next_run_at)) {
            return 0;
        }

        return $now->diffInHours($schedule->next_run_at);
    }

    /**
     * Check if there are recent failures
     */
    private function hasRecentFailures(): bool
    {
        return Backup::where('status', 'failed')
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->exists();
    }
}