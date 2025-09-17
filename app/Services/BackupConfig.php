<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

class BackupConfig
{
    /**
     * Get storage configuration.
     *
     * @return array
     */
    public function getStorageConfig(): array
    {
        return Config::get('backup.storage', []);
    }

    /**
     * Get backup storage path.
     *
     * @return string
     */
    public function getStoragePath(): string
    {
        return Config::get('backup.storage.path') ?: storage_path('app/backups');
    }

    /**
     * Get maximum backup file size in MB.
     *
     * @return int
     */
    public function getMaxFileSize(): int
    {
        return Config::get('backup.storage.max_file_size') ?: 2048;
    }

    /**
     * Get maximum storage size in bytes.
     *
     * @return int
     */
    public function getMaxStorageSize(): int
    {
        $maxSizeMB = Config::get('backup.storage.max_storage_size', 0);
        return $maxSizeMB * 1024 * 1024; // Convert MB to bytes
    }

    /**
     * Get backup storage disk.
     *
     * @return string
     */
    public function getStorageDisk(): string
    {
        return Config::get('backup.storage.disk') ?: 'local';
    }

    /**
     * Get database backup configuration.
     *
     * @return array
     */
    public function getDatabaseConfig(): array
    {
        return Config::get('backup.database', []);
    }

    /**
     * Get database backup timeout in seconds.
     *
     * @return int
     */
    public function getDatabaseTimeout(): int
    {
        return Config::get('backup.database.timeout') ?: 300;
    }

    /**
     * Check if single transaction mode is enabled for database backups.
     *
     * @return bool
     */
    public function isDatabaseSingleTransaction(): bool
    {
        return Config::get('backup.database.single_transaction', true);
    }

    /**
     * Check if routines should be included in database backups.
     *
     * @return bool
     */
    public function includeDatabaseRoutines(): bool
    {
        return Config::get('backup.database.routines', true);
    }

    /**
     * Check if triggers should be included in database backups.
     *
     * @return bool
     */
    public function includeDatabaseTriggers(): bool
    {
        return Config::get('backup.database.triggers', true);
    }

    /**
     * Get file backup configuration.
     *
     * @return array
     */
    public function getFilesConfig(): array
    {
        return Config::get('backup.files', []);
    }

    /**
     * Get directories to backup.
     *
     * @return array
     */
    public function getBackupDirectories(): array
    {
        return Config::get('backup.files.directories', []);
    }

    /**
     * Get compression level for file backups.
     *
     * @return int
     */
    public function getCompressionLevel(): int
    {
        $level = Config::get('backup.files.compression_level') ?: 6;
        
        if ($level < 0 || $level > 9) {
            throw new InvalidArgumentException('Compression level must be between 0 and 9');
        }
        
        return $level;
    }

    /**
     * Get file patterns to exclude from backups.
     *
     * @return array
     */
    public function getExcludePatterns(): array
    {
        return Config::get('backup.files.exclude_patterns', []);
    }

    /**
     * Get retention policy configuration.
     *
     * @return array
     */
    public function getRetentionConfig(): array
    {
        return Config::get('backup.retention', []);
    }

    /**
     * Get database backup retention period in days.
     *
     * @return int
     */
    public function getDatabaseRetentionDays(): int
    {
        return Config::get('backup.retention.database_days') ?: 30;
    }

    /**
     * Get file backup retention period in days.
     *
     * @return int
     */
    public function getFilesRetentionDays(): int
    {
        return Config::get('backup.retention.files_days') ?: 14;
    }

    /**
     * Check if automatic cleanup is enabled.
     *
     * @return bool
     */
    public function isCleanupEnabled(): bool
    {
        return Config::get('backup.retention.cleanup_enabled', true);
    }

    /**
     * Get minimum number of backups to keep during cleanup.
     *
     * @return int
     */
    public function getMinBackupsToKeep(): int
    {
        $value = Config::get('backup.retention.min_backups_to_keep');
        return $value !== null ? $value : 3;
    }

    /**
     * Get notification configuration.
     *
     * @return array
     */
    public function getNotificationConfig(): array
    {
        return Config::get('backup.notifications', []);
    }

    /**
     * Get notification email address.
     *
     * @return string|null
     */
    public function getNotificationEmail(): ?string
    {
        return Config::get('backup.notifications.email');
    }

    /**
     * Check if success notifications are enabled.
     *
     * @return bool
     */
    public function shouldNotifyOnSuccess(): bool
    {
        return Config::get('backup.notifications.notify_on_success', false);
    }

    /**
     * Check if failure notifications are enabled.
     *
     * @return bool
     */
    public function shouldNotifyOnFailure(): bool
    {
        return Config::get('backup.notifications.notify_on_failure', true);
    }

    /**
     * Check if cleanup notifications are enabled.
     *
     * @return bool
     */
    public function shouldNotifyOnCleanup(): bool
    {
        return Config::get('backup.notifications.notify_on_cleanup', false);
    }

    /**
     * Get notification channels.
     *
     * @return array
     */
    public function getNotificationChannels(): array
    {
        return Config::get('backup.notifications.channels', ['mail']);
    }

    /**
     * Get monitoring configuration.
     *
     * @return array
     */
    public function getMonitoringConfig(): array
    {
        return Config::get('backup.monitoring', []);
    }

    /**
     * Check if monitoring is enabled.
     *
     * @return bool
     */
    public function isMonitoringEnabled(): bool
    {
        return Config::get('backup.monitoring.enabled', true);
    }

    /**
     * Get storage warning threshold percentage.
     *
     * @return int
     */
    public function getStorageWarningThreshold(): int
    {
        return Config::get('backup.monitoring.storage_warning_threshold', 80);
    }

    /**
     * Get maximum backup age in hours before warning.
     *
     * @return int
     */
    public function getMaxBackupAgeHours(): int
    {
        return Config::get('backup.monitoring.max_backup_age_hours', 48);
    }

    /**
     * Check if health checks are enabled.
     *
     * @return bool
     */
    public function isHealthCheckEnabled(): bool
    {
        return Config::get('backup.monitoring.health_check_enabled', true);
    }

    /**
     * Get security configuration.
     *
     * @return array
     */
    public function getSecurityConfig(): array
    {
        return Config::get('backup.security', []);
    }

    /**
     * Check if backup encryption is enabled.
     *
     * @return bool
     */
    public function isEncryptionEnabled(): bool
    {
        return Config::get('backup.security.encrypt_backups', false);
    }

    /**
     * Get backup encryption key.
     *
     * @return string|null
     */
    public function getEncryptionKey(): ?string
    {
        return Config::get('backup.security.encryption_key');
    }

    /**
     * Get file permissions for backup files.
     *
     * @return int
     */
    public function getFilePermissions(): int
    {
        return Config::get('backup.security.file_permissions', 0600);
    }

    /**
     * Get directory permissions for backup directories.
     *
     * @return int
     */
    public function getDirectoryPermissions(): int
    {
        return Config::get('backup.security.directory_permissions', 0700);
    }

    /**
     * Get download link TTL in seconds.
     *
     * @return int
     */
    public function getDownloadLinkTtl(): int
    {
        return Config::get('backup.security.download_link_ttl', 3600);
    }

    /**
     * Get schedule configuration.
     *
     * @return array
     */
    public function getScheduleConfig(): array
    {
        return Config::get('backup.schedule', []);
    }

    /**
     * Get default backup frequency.
     *
     * @return string
     */
    public function getDefaultFrequency(): string
    {
        return Config::get('backup.schedule.default_frequency', 'daily');
    }

    /**
     * Get default backup time.
     *
     * @return string
     */
    public function getDefaultTime(): string
    {
        return Config::get('backup.schedule.default_time', '02:00');
    }

    /**
     * Get maximum concurrent backups allowed.
     *
     * @return int
     */
    public function getMaxConcurrentBackups(): int
    {
        return Config::get('backup.schedule.max_concurrent_backups', 1);
    }

    /**
     * Get number of retry attempts for failed backups.
     *
     * @return int
     */
    public function getRetryAttempts(): int
    {
        return Config::get('backup.schedule.retry_attempts', 1);
    }

    /**
     * Get retry delay in seconds.
     *
     * @return int
     */
    public function getRetryDelay(): int
    {
        return Config::get('backup.schedule.retry_delay', 300);
    }

    /**
     * Validate configuration values.
     *
     * @return array Array of validation errors, empty if valid
     */
    public function validateConfig(): array
    {
        $errors = [];

        // Validate storage path
        $storagePath = Config::get('backup.storage.path');
        if (empty($storagePath)) {
            $errors[] = 'Backup storage path is not configured';
        }

        // Validate compression level
        try {
            $this->getCompressionLevel();
        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        }

        // Validate retention days
        $databaseRetentionDays = Config::get('backup.retention.database_days');
        if ($databaseRetentionDays !== null && $databaseRetentionDays < 1) {
            $errors[] = 'Database retention days must be at least 1';
        }

        $filesRetentionDays = Config::get('backup.retention.files_days');
        if ($filesRetentionDays !== null && $filesRetentionDays < 1) {
            $errors[] = 'Files retention days must be at least 1';
        }

        // Validate minimum backups to keep
        $minBackupsToKeep = Config::get('backup.retention.min_backups_to_keep');
        if ($minBackupsToKeep !== null && $minBackupsToKeep < 1) {
            $errors[] = 'Minimum backups to keep must be at least 1';
        }

        // Validate notification email if notifications are enabled
        if (($this->shouldNotifyOnSuccess() || $this->shouldNotifyOnFailure() || $this->shouldNotifyOnCleanup()) 
            && empty($this->getNotificationEmail())) {
            $errors[] = 'Notification email is required when notifications are enabled';
        }

        // Validate storage warning threshold
        $threshold = Config::get('backup.monitoring.storage_warning_threshold');
        if ($threshold !== null && ($threshold < 1 || $threshold > 100)) {
            $errors[] = 'Storage warning threshold must be between 1 and 100';
        }

        return $errors;
    }

    /**
     * Get all configuration as array.
     *
     * @return array
     */
    public function getAllConfig(): array
    {
        return Config::get('backup', []);
    }
}