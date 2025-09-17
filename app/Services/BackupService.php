<?php

namespace App\Services;

use App\Models\Backup;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackupService
{
    private DatabaseBackupHandler $databaseHandler;
    private FileBackupHandler $fileHandler;
    private BackupConfig $config;
    private BackupNotificationService $notificationService;

    public function __construct(
        DatabaseBackupHandler $databaseHandler,
        FileBackupHandler $fileHandler,
        BackupConfig $config,
        BackupNotificationService $notificationService = null
    ) {
        $this->databaseHandler = $databaseHandler;
        $this->fileHandler = $fileHandler;
        $this->config = $config;
        $this->notificationService = $notificationService;
    }

    /**
     * Create a manual backup with support for database and file backups
     *
     * @param array $options Backup options
     * @return BackupResult
     */
    public function createManualBackup(array $options = []): BackupResult
    {
        $backupType = $options['type'] ?? 'full';
        $customName = $options['name'] ?? null;
        $includeDatabase = $options['database'] ?? true;
        $includeFiles = $options['files'] ?? true;

        // Validate backup type
        if (!in_array($backupType, ['database', 'files', 'full'])) {
            return new BackupResult(false, 'Invalid backup type. Must be: database, files, or full');
        }

        // Override include flags based on backup type
        if ($backupType === 'database') {
            $includeDatabase = true;
            $includeFiles = false;
        } elseif ($backupType === 'files') {
            $includeDatabase = false;
            $includeFiles = true;
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupName = $customName ?: "manual_backup_{$backupType}_{$timestamp}";

        // Create backup record
        $backup = Backup::create([
            'name' => $backupName,
            'type' => $backupType,
            'file_path' => '', // Will be updated after backup creation
            'status' => 'pending',
            'created_by' => Auth::id(),
            'metadata' => [
                'include_database' => $includeDatabase,
                'include_files' => $includeFiles,
                'manual' => true,
                'options' => $options
            ]
        ]);

        Log::info('Starting manual backup', [
            'backup_id' => $backup->id,
            'type' => $backupType,
            'include_database' => $includeDatabase,
            'include_files' => $includeFiles
        ]);

        try {
            $backupPaths = [];
            $totalSize = 0;

            // Create database backup if requested
            if ($includeDatabase) {
                $dbBackupPath = $this->createDatabaseBackupWithRetry($backup);
                $backupPaths['database'] = $dbBackupPath;
                $totalSize += File::size($dbBackupPath);
            }

            // Create file backups if requested
            if ($includeFiles) {
                $fileBackupPaths = $this->createFileBackupsWithRetry($backup);
                $backupPaths['files'] = $fileBackupPaths;
                
                foreach ($fileBackupPaths as $path) {
                    $totalSize += File::size($path);
                }
            }

            // Update backup record with success
            $backup->update([
                'status' => 'completed',
                'file_path' => json_encode($backupPaths),
                'file_size' => $totalSize,
                'completed_at' => now(),
                'metadata' => array_merge($backup->metadata, [
                    'backup_paths' => $backupPaths,
                    'total_size' => $totalSize,
                    'completed_at' => now()->toISOString()
                ])
            ]);

            Log::info('Manual backup completed successfully', [
                'backup_id' => $backup->id,
                'total_size' => $totalSize,
                'paths' => $backupPaths
            ]);

            // Send success notification
            if ($this->notificationService) {
                $this->notificationService->notifyBackupSuccess($backup);
            }

            return new BackupResult(true, 'Backup completed successfully', $backup);

        } catch (Exception $e) {
            // Update backup record with failure
            $backup->update([
                'status' => 'failed',
                'metadata' => array_merge($backup->metadata, [
                    'error' => $e->getMessage(),
                    'failed_at' => now()->toISOString()
                ])
            ]);

            Log::error('Manual backup failed', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage()
            ]);

            // Send failure notification
            if ($this->notificationService) {
                $this->notificationService->notifyBackupFailure($backup, $e->getMessage());
            }

            return new BackupResult(false, 'Backup failed: ' . $e->getMessage(), $backup);
        }
    }

    /**
     * Get backup history with optional limit
     *
     * @param int $limit Maximum number of records to return
     * @return Collection
     */
    public function getBackupHistory(int $limit = 50): Collection
    {
        return Backup::with('creator')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get current backup status and statistics
     *
     * @return BackupStatus
     */
    public function getBackupStatus(): BackupStatus
    {
        $recentBackups = Backup::where('created_at', '>=', now()->subDays(7))->get();
        
        $lastSuccessfulBackup = Backup::where('status', 'completed')->latest()->first();
        
        $stats = [
            'total_backups' => Backup::count(),
            'recent_backups' => $recentBackups->count(),
            'successful_backups' => $recentBackups->where('status', 'completed')->count(),
            'failed_backups' => $recentBackups->where('status', 'failed')->count(),
            'pending_backups' => Backup::where('status', 'pending')->count(),
            'last_backup' => Backup::latest()->first(),
            'last_successful_backup_date' => $lastSuccessfulBackup ? $lastSuccessfulBackup->created_at : null,
            'storage_usage' => $this->calculateStorageUsage(),
            'storage_path' => $this->config->getStoragePath(),
            'retention_policy' => [
                'database_days' => $this->config->getDatabaseRetentionDays(),
                'files_days' => $this->config->getFilesRetentionDays()
            ]
        ];

        return new BackupStatus($stats);
    }

    /**
     * Validate backup file integrity
     *
     * @param string $backupPath Path to backup file or JSON string of paths
     * @return bool
     */
    public function validateBackupIntegrity(string $backupPath): bool
    {
        try {
            // Handle JSON encoded paths (for full backups)
            $paths = json_decode($backupPath, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($paths)) {
                return $this->validateMultipleBackupPaths($paths);
            }

            // Handle single file path
            return $this->validateSingleBackupPath($backupPath);

        } catch (Exception $e) {
            Log::error('Backup integrity validation failed', [
                'path' => $backupPath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create database backup with retry logic
     *
     * @param Backup $backup Backup model instance
     * @return string Path to created backup
     * @throws Exception
     */
    private function createDatabaseBackupWithRetry(Backup $backup): string
    {
        $maxRetries = $this->config->getRetryAttempts();
        $retryDelay = $this->config->getRetryDelay();
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries + 1; $attempt++) {
            try {
                Log::info('Creating database backup', [
                    'backup_id' => $backup->id,
                    'attempt' => $attempt
                ]);

                $dbBackupPath = $this->databaseHandler->createDump();
                
                // Validate the created backup
                if (!$this->databaseHandler->validateDump($dbBackupPath)) {
                    throw new Exception('Database backup validation failed');
                }

                return $dbBackupPath;

            } catch (Exception $e) {
                $lastException = $e;
                
                Log::warning('Database backup attempt failed', [
                    'backup_id' => $backup->id,
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);

                // If this is not the last attempt, wait before retrying
                if ($attempt <= $maxRetries) {
                    Log::info('Retrying database backup', [
                        'backup_id' => $backup->id,
                        'retry_delay' => $retryDelay
                    ]);
                    sleep($retryDelay);
                }
            }
        }

        throw new Exception('Database backup failed after ' . ($maxRetries + 1) . ' attempts: ' . $lastException->getMessage());
    }

    /**
     * Create file backups with retry logic
     *
     * @param Backup $backup Backup model instance
     * @return array Array of backup file paths
     * @throws Exception
     */
    private function createFileBackupsWithRetry(Backup $backup): array
    {
        $directories = $this->config->getBackupDirectories();
        $backupPaths = [];
        $maxRetries = $this->config->getRetryAttempts();
        $retryDelay = $this->config->getRetryDelay();

        foreach ($directories as $directory) {
            if (!File::exists($directory)) {
                Log::warning('Skipping non-existent directory', [
                    'backup_id' => $backup->id,
                    'directory' => $directory
                ]);
                continue;
            }

            $lastException = null;
            $success = false;

            for ($attempt = 1; $attempt <= $maxRetries + 1; $attempt++) {
                try {
                    Log::info('Creating file backup', [
                        'backup_id' => $backup->id,
                        'directory' => $directory,
                        'attempt' => $attempt
                    ]);

                    $archivePath = $this->fileHandler->backupDirectory($directory);
                    
                    // Validate the created archive
                    if (!$this->fileHandler->validateArchive($archivePath)) {
                        throw new Exception('File backup validation failed for: ' . $directory);
                    }

                    $backupPaths[] = $archivePath;
                    $success = true;
                    break;

                } catch (Exception $e) {
                    $lastException = $e;
                    
                    Log::warning('File backup attempt failed', [
                        'backup_id' => $backup->id,
                        'directory' => $directory,
                        'attempt' => $attempt,
                        'error' => $e->getMessage()
                    ]);

                    // If this is not the last attempt, wait before retrying
                    if ($attempt <= $maxRetries) {
                        Log::info('Retrying file backup', [
                            'backup_id' => $backup->id,
                            'directory' => $directory,
                            'retry_delay' => $retryDelay
                        ]);
                        sleep($retryDelay);
                    }
                }
            }

            if (!$success) {
                throw new Exception('File backup failed for directory ' . $directory . ' after ' . ($maxRetries + 1) . ' attempts: ' . $lastException->getMessage());
            }
        }

        return $backupPaths;
    }

    /**
     * Validate multiple backup paths
     *
     * @param array $paths Array of backup paths
     * @return bool
     */
    private function validateMultipleBackupPaths(array $paths): bool
    {
        foreach ($paths as $type => $typePaths) {
            if (is_array($typePaths)) {
                foreach ($typePaths as $path) {
                    if (!$this->validateSingleBackupPath($path)) {
                        return false;
                    }
                }
            } else {
                if (!$this->validateSingleBackupPath($typePaths)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate single backup path
     *
     * @param string $path Backup file path
     * @return bool
     */
    private function validateSingleBackupPath(string $path): bool
    {
        if (!File::exists($path)) {
            return false;
        }

        // Determine file type and validate accordingly
        if (str_ends_with($path, '.sql')) {
            return $this->databaseHandler->validateDump($path);
        } elseif (str_ends_with($path, '.zip')) {
            return $this->fileHandler->validateArchive($path);
        }

        return false;
    }

    /**
     * Calculate total storage usage for backups
     *
     * @return array Storage usage information
     */
    private function calculateStorageUsage(): array
    {
        $storagePath = $this->config->getStoragePath();
        $totalSize = 0;
        $fileCount = 0;

        if (File::exists($storagePath)) {
            $files = File::allFiles($storagePath);
            foreach ($files as $file) {
                $totalSize += $file->getSize();
                $fileCount++;
            }
        }

        return [
            'total_size' => $totalSize,
            'formatted_size' => $this->formatBytes($totalSize),
            'file_count' => $fileCount,
            'storage_path' => $storagePath
        ];
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}