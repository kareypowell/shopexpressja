<?php

namespace App\Services;

use App\Models\Backup;
use Carbon\Carbon;

class BackupStatus
{
    private array $stats;

    public function __construct(array $stats)
    {
        $this->stats = $stats;
    }

    /**
     * Get total number of backups
     *
     * @return int
     */
    public function getTotalBackups(): int
    {
        return $this->stats['total_backups'] ?? 0;
    }

    /**
     * Get number of recent backups (last 7 days)
     *
     * @return int
     */
    public function getRecentBackups(): int
    {
        return $this->stats['recent_backups'] ?? 0;
    }

    /**
     * Get number of successful backups in recent period
     *
     * @return int
     */
    public function getSuccessfulBackups(): int
    {
        return $this->stats['successful_backups'] ?? 0;
    }

    /**
     * Get number of failed backups in recent period
     *
     * @return int
     */
    public function getFailedBackups(): int
    {
        return $this->stats['failed_backups'] ?? 0;
    }

    /**
     * Get number of pending backups
     *
     * @return int
     */
    public function getPendingBackups(): int
    {
        return $this->stats['pending_backups'] ?? 0;
    }

    /**
     * Get the last successful backup
     *
     * @return Backup|null
     */
    public function getLastBackup(): ?Backup
    {
        return $this->stats['last_backup'] ?? null;
    }

    /**
     * Get storage usage information
     *
     * @return array
     */
    public function getStorageUsage(): array
    {
        return $this->stats['storage_usage'] ?? [];
    }

    /**
     * Get storage path
     *
     * @return string
     */
    public function getStoragePath(): string
    {
        return $this->stats['storage_path'] ?? '';
    }

    /**
     * Get retention policy information
     *
     * @return array
     */
    public function getRetentionPolicy(): array
    {
        return $this->stats['retention_policy'] ?? [];
    }

    /**
     * Calculate success rate percentage
     *
     * @return float
     */
    public function getSuccessRate(): float
    {
        $total = $this->getRecentBackups();
        if ($total === 0) {
            return 0.0;
        }

        return round(($this->getSuccessfulBackups() / $total) * 100, 2);
    }

    /**
     * Check if backup system is healthy
     *
     * @return bool
     */
    public function isHealthy(): bool
    {
        // Consider system healthy if:
        // 1. No pending backups older than 1 hour
        // 2. Success rate is above 80%
        // 3. Last backup was within 48 hours (configurable)
        
        if ($this->getPendingBackups() > 0) {
            return false;
        }

        if ($this->getSuccessRate() < 80.0) {
            return false;
        }

        $lastBackup = $this->getLastBackup();
        if ($lastBackup && $lastBackup->created_at->diffInHours(now()) > 48) {
            return false;
        }

        return true;
    }

    /**
     * Get health status message
     *
     * @return string
     */
    public function getHealthMessage(): string
    {
        if ($this->isHealthy()) {
            return 'Backup system is operating normally';
        }

        $issues = [];

        if ($this->getPendingBackups() > 0) {
            $issues[] = $this->getPendingBackups() . ' backup(s) are still pending';
        }

        if ($this->getSuccessRate() < 80.0) {
            $issues[] = 'Success rate is low (' . $this->getSuccessRate() . '%)';
        }

        $lastBackup = $this->getLastBackup();
        if ($lastBackup && $lastBackup->created_at->diffInHours(now()) > 48) {
            $issues[] = 'Last backup was ' . $lastBackup->created_at->diffForHumans();
        }

        return 'Issues detected: ' . implode(', ', $issues);
    }

    /**
     * Get formatted storage usage
     *
     * @return string
     */
    public function getFormattedStorageUsage(): string
    {
        $usage = $this->getStorageUsage();
        return $usage['formatted_size'] ?? '0 B';
    }

    /**
     * Get storage file count
     *
     * @return int
     */
    public function getStorageFileCount(): int
    {
        $usage = $this->getStorageUsage();
        return $usage['file_count'] ?? 0;
    }

    /**
     * Check if storage usage is high
     *
     * @param int $thresholdMB Storage threshold in MB
     * @return bool
     */
    public function isStorageUsageHigh(int $thresholdMB = 1024): bool
    {
        $usage = $this->getStorageUsage();
        $totalSizeMB = ($usage['total_size'] ?? 0) / (1024 * 1024);
        
        return $totalSizeMB > $thresholdMB;
    }

    /**
     * Get time since last backup
     *
     * @return string|null
     */
    public function getTimeSinceLastBackup(): ?string
    {
        $lastBackup = $this->getLastBackup();
        if (!$lastBackup) {
            return null;
        }

        return $lastBackup->created_at->diffForHumans();
    }

    /**
     * Get all statistics as array
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge($this->stats, [
            'success_rate' => $this->getSuccessRate(),
            'is_healthy' => $this->isHealthy(),
            'health_message' => $this->getHealthMessage(),
            'formatted_storage_usage' => $this->getFormattedStorageUsage(),
            'time_since_last_backup' => $this->getTimeSinceLastBackup()
        ]);
    }

    /**
     * Get the date of the last backup
     *
     * @return Carbon|null
     */
    public function getLastBackupDate(): ?Carbon
    {
        $lastBackup = $this->getLastBackup();
        return $lastBackup ? $lastBackup->created_at : null;
    }

    /**
     * Get the date of the last successful backup
     *
     * @return Carbon|null
     */
    public function getLastSuccessfulBackupDate(): ?Carbon
    {
        return $this->stats['last_successful_backup_date'] ?? null;
    }

    /**
     * Get number of recent failures (last 7 days)
     *
     * @return int
     */
    public function getRecentFailures(): int
    {
        return $this->getFailedBackups();
    }

    /**
     * Get health issues as array
     *
     * @return array
     */
    public function getHealthIssues(): array
    {
        if ($this->isHealthy()) {
            return [];
        }

        $issues = [];

        if ($this->getPendingBackups() > 0) {
            $issues[] = $this->getPendingBackups() . ' backup(s) are still pending';
        }

        if ($this->getSuccessRate() < 80.0) {
            $issues[] = 'Success rate is low (' . $this->getSuccessRate() . '%)';
        }

        $lastBackup = $this->getLastBackup();
        if ($lastBackup && $lastBackup->created_at->diffInHours(now()) > 48) {
            $issues[] = 'Last backup was ' . $lastBackup->created_at->diffForHumans();
        }

        return $issues;
    }

    /**
     * Convert status to JSON
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}