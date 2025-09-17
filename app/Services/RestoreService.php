<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\RestoreLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class RestoreService
{
    private BackupService $backupService;
    private DatabaseBackupHandler $databaseHandler;
    private FileBackupHandler $fileHandler;

    public function __construct(BackupService $backupService, DatabaseBackupHandler $databaseHandler, FileBackupHandler $fileHandler)
    {
        $this->backupService = $backupService;
        $this->databaseHandler = $databaseHandler;
        $this->fileHandler = $fileHandler;
    }

    /**
     * Restore database from backup file
     */
    public function restoreDatabase(string $backupPath): RestoreResult
    {
        $restoreLog = null;
        $preRestoreBackupPath = null;

        try {
            // Validate backup file exists and is readable
            if (!file_exists($backupPath) || !is_readable($backupPath)) {
                throw new Exception("Backup file not found or not readable: {$backupPath}");
            }

            // Validate backup file integrity
            if (!$this->databaseHandler->validateDump($backupPath)) {
                throw new Exception("Backup file validation failed: {$backupPath}");
            }

            // Find backup record
            $backup = Backup::where('file_path', $backupPath)->first();
            if (!$backup) {
                throw new Exception("Backup record not found for file: {$backupPath}");
            }

            // Create restore log entry
            $restoreLog = RestoreLog::create([
                'backup_id' => $backup->id,
                'restored_by' => auth()->id(),
                'restore_type' => 'database',
                'status' => 'pending',
                'started_at' => now(),
                'metadata' => [
                    'backup_file' => $backupPath,
                    'backup_size' => filesize($backupPath),
                ]
            ]);

            Log::info("Starting database restoration", [
                'backup_path' => $backupPath,
                'restore_log_id' => $restoreLog->id,
                'user_id' => auth()->id()
            ]);

            // Create pre-restore backup
            $preRestoreBackupPath = $this->createPreRestoreBackup();
            
            $restoreLog->update([
                'pre_restore_backup_path' => $preRestoreBackupPath
            ]);

            // Enable maintenance mode
            $this->enableMaintenanceMode();

            try {
                // Perform database restoration
                $this->performDatabaseRestore($backupPath);

                // Verify database integrity after restoration
                $this->verifyDatabaseIntegrity();

                // Update restore log as completed
                $restoreLog->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);

                Log::info("Database restoration completed successfully", [
                    'backup_path' => $backupPath,
                    'restore_log_id' => $restoreLog->id
                ]);

                return new RestoreResult(true, 'Database restored successfully', [
                    'restore_log_id' => $restoreLog->id,
                    'pre_restore_backup' => $preRestoreBackupPath
                ]);

            } catch (Exception $e) {
                Log::error("Database restoration failed, attempting rollback", [
                    'error' => $e->getMessage(),
                    'backup_path' => $backupPath,
                    'restore_log_id' => $restoreLog->id
                ]);

                // Attempt rollback
                if ($preRestoreBackupPath && $this->rollbackRestore($preRestoreBackupPath)) {
                    $errorMessage = "Database restoration failed and was rolled back: " . $e->getMessage();
                } else {
                    $errorMessage = "Database restoration failed and rollback also failed: " . $e->getMessage();
                }

                throw new Exception($errorMessage);
            }

        } catch (Exception $e) {
            // Update restore log with error
            if ($restoreLog) {
                $restoreLog->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now()
                ]);
            }

            Log::error("Database restoration failed", [
                'error' => $e->getMessage(),
                'backup_path' => $backupPath,
                'restore_log_id' => $restoreLog ? $restoreLog->id : null
            ]);

            return new RestoreResult(false, $e->getMessage(), [
                'restore_log_id' => $restoreLog ? $restoreLog->id : null,
                'pre_restore_backup' => $preRestoreBackupPath
            ]);

        } finally {
            // Always disable maintenance mode
            $this->disableMaintenanceMode();
        }
    }

    /**
     * Create pre-restore backup
     */
    private function createPreRestoreBackup()
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "pre-restore-backup_{$timestamp}.sql";
        
        $backupPath = $this->databaseHandler->createDump($filename);
        
        Log::info("Pre-restore backup created", [
            'backup_path' => $backupPath,
            'size' => filesize($backupPath)
        ]);

        return $backupPath;
    }

    /**
     * Perform the actual database restoration
     */
    private function performDatabaseRestore(string $backupPath)
    {
        $config = config('database.connections.' . config('database.default'));
        
        $command = sprintf(
            'mysql -h%s -P%s -u%s -p%s %s < %s',
            escapeshellarg($config['host']),
            escapeshellarg($config['port'] ?? 3306),
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['database']),
            escapeshellarg($backupPath)
        );

        $output = [];
        $returnCode = 0;
        
        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception("MySQL restore failed with return code {$returnCode}: " . implode("\n", $output));
        }

        Log::info("Database restoration command executed successfully", [
            'backup_path' => $backupPath
        ]);
    }

    /**
     * Verify database integrity after restoration
     */
    private function verifyDatabaseIntegrity()
    {
        try {
            // Test database connection
            DB::connection()->getPdo();
            
            // Run basic integrity checks
            $userCount = DB::table('users')->count();
            $packageCount = DB::table('packages')->count();
            
            Log::info("Database integrity verification passed", [
                'user_count' => $userCount,
                'package_count' => $packageCount
            ]);
            
        } catch (Exception $e) {
            throw new Exception("Database integrity verification failed: " . $e->getMessage());
        }
    }

    /**
     * Enable maintenance mode
     */
    public function enableMaintenanceMode()
    {
        try {
            Artisan::call('down', [
                '--message' => 'System maintenance in progress - Database restoration',
                '--retry' => 60
            ]);
            
            Log::info("Maintenance mode enabled for database restoration");
            
        } catch (Exception $e) {
            Log::warning("Failed to enable maintenance mode", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Disable maintenance mode
     */
    public function disableMaintenanceMode()
    {
        try {
            Artisan::call('up');
            
            Log::info("Maintenance mode disabled after database restoration");
            
        } catch (Exception $e) {
            Log::warning("Failed to disable maintenance mode", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Rollback restore using pre-restore backup
     */
    public function rollbackRestore(string $preRestoreBackup)
    {
        try {
            if (!file_exists($preRestoreBackup)) {
                Log::error("Pre-restore backup file not found for rollback", [
                    'backup_path' => $preRestoreBackup
                ]);
                return false;
            }

            Log::info("Starting rollback restoration", [
                'pre_restore_backup' => $preRestoreBackup
            ]);

            // Perform rollback restoration
            $this->performDatabaseRestore($preRestoreBackup);
            
            // Verify rollback integrity
            $this->verifyDatabaseIntegrity();

            Log::info("Rollback restoration completed successfully", [
                'pre_restore_backup' => $preRestoreBackup
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("Rollback restoration failed", [
                'error' => $e->getMessage(),
                'pre_restore_backup' => $preRestoreBackup
            ]);
            
            return false;
        }
    }

    /**
     * Restore files from backup archive
     */
    public function restoreFiles(string $backupPath, array $directories): RestoreResult
    {
        $restoreLog = null;
        $preRestoreBackupPath = null;

        try {
            // Validate backup file exists and is readable
            if (!file_exists($backupPath) || !is_readable($backupPath)) {
                throw new Exception("Backup file not found or not readable: {$backupPath}");
            }

            // Validate backup file integrity
            if (!$this->fileHandler->validateArchive($backupPath)) {
                throw new Exception("Backup file validation failed: {$backupPath}");
            }

            // Find backup record
            $backup = Backup::where('file_path', $backupPath)->first();
            if (!$backup) {
                throw new Exception("Backup record not found for file: {$backupPath}");
            }

            // Create restore log entry
            $restoreLog = RestoreLog::create([
                'backup_id' => $backup->id,
                'restored_by' => auth()->id(),
                'restore_type' => 'files',
                'status' => 'pending',
                'started_at' => now(),
                'metadata' => [
                    'backup_file' => $backupPath,
                    'backup_size' => filesize($backupPath),
                    'directories' => $directories,
                ]
            ]);

            Log::info("Starting file restoration", [
                'backup_path' => $backupPath,
                'directories' => $directories,
                'restore_log_id' => $restoreLog->id,
                'user_id' => auth()->id()
            ]);

            // Create pre-restore backup of existing files
            $preRestoreBackupPath = $this->createPreRestoreFileBackup($directories);
            
            $restoreLog->update([
                'pre_restore_backup_path' => $preRestoreBackupPath
            ]);

            try {
                // Perform file restoration
                $this->performFileRestore($backupPath, $directories);

                // Verify file restoration
                $this->verifyFileRestoration($directories);

                // Update restore log as completed
                $restoreLog->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);

                Log::info("File restoration completed successfully", [
                    'backup_path' => $backupPath,
                    'directories' => $directories,
                    'restore_log_id' => $restoreLog->id
                ]);

                return new RestoreResult(true, 'Files restored successfully', [
                    'restore_log_id' => $restoreLog->id,
                    'pre_restore_backup' => $preRestoreBackupPath,
                    'restored_directories' => $directories
                ]);

            } catch (Exception $e) {
                Log::error("File restoration failed, attempting rollback", [
                    'error' => $e->getMessage(),
                    'backup_path' => $backupPath,
                    'directories' => $directories,
                    'restore_log_id' => $restoreLog->id
                ]);

                // Attempt rollback
                if ($preRestoreBackupPath && $this->rollbackFileRestore($preRestoreBackupPath, $directories)) {
                    $errorMessage = "File restoration failed and was rolled back: " . $e->getMessage();
                } else {
                    $errorMessage = "File restoration failed and rollback also failed: " . $e->getMessage();
                }

                throw new Exception($errorMessage);
            }

        } catch (Exception $e) {
            // Update restore log with error
            if ($restoreLog) {
                $restoreLog->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now()
                ]);
            }

            Log::error("File restoration failed", [
                'error' => $e->getMessage(),
                'backup_path' => $backupPath,
                'directories' => $directories,
                'restore_log_id' => $restoreLog ? $restoreLog->id : null
            ]);

            return new RestoreResult(false, $e->getMessage(), [
                'restore_log_id' => $restoreLog ? $restoreLog->id : null,
                'pre_restore_backup' => $preRestoreBackupPath,
                'directories' => $directories
            ]);
        }
    }

    /**
     * Create pre-restore backup of files
     */
    private function createPreRestoreFileBackup(array $directories)
    {
        $existingDirectories = array_filter($directories, function ($directory) {
            return file_exists($directory) && is_dir($directory);
        });

        if (empty($existingDirectories)) {
            Log::info("No existing directories to backup for pre-restore");
            return '';
        }

        $preRestoreBackupPath = $this->fileHandler->createPreRestoreBackup($existingDirectories);
        
        Log::info("Pre-restore file backup created", [
            'backup_path' => $preRestoreBackupPath,
            'directories' => $existingDirectories,
            'size' => filesize($preRestoreBackupPath)
        ]);

        return $preRestoreBackupPath;
    }

    /**
     * Perform the actual file restoration
     */
    private function performFileRestore(string $backupPath, array $directories)
    {
        // Create temporary extraction directory
        $tempDir = storage_path('app/temp/restore_' . uniqid());
        
        try {
            // Extract archive to temporary directory
            if (!$this->fileHandler->extractArchive($backupPath, $tempDir)) {
                throw new Exception("Failed to extract backup archive");
            }

            // Get archive contents to understand structure
            $archiveContents = $this->fileHandler->getArchiveContents($backupPath);
            
            // Restore each directory
            foreach ($directories as $targetDirectory) {
                $this->restoreDirectoryFromExtraction($tempDir, $targetDirectory, $archiveContents);
            }

            Log::info("File restoration completed", [
                'backup_path' => $backupPath,
                'directories' => $directories,
                'temp_dir' => $tempDir
            ]);

        } finally {
            // Clean up temporary directory
            if (file_exists($tempDir)) {
                $this->removeDirectory($tempDir);
            }
        }
    }

    /**
     * Restore a specific directory from extracted files
     */
    private function restoreDirectoryFromExtraction(string $tempDir, string $targetDirectory, array $archiveContents)
    {
        $targetBasename = basename($targetDirectory);
        
        // Find matching directory in extracted files
        $sourceDir = null;
        foreach ($archiveContents as $content) {
            if (substr($content['name'], 0, strlen($targetBasename . '/')) === $targetBasename . '/') {
                $sourceDir = $tempDir . '/' . $targetBasename;
                break;
            }
        }

        if (!$sourceDir || !file_exists($sourceDir)) {
            Log::warning("Source directory not found in backup", [
                'target_directory' => $targetDirectory,
                'expected_source' => $sourceDir,
                'temp_dir' => $tempDir
            ]);
            return;
        }

        // Remove existing target directory if it exists
        if (file_exists($targetDirectory)) {
            $this->removeDirectory($targetDirectory);
        }

        // Create parent directory if it doesn't exist
        $parentDir = dirname($targetDirectory);
        if (!file_exists($parentDir)) {
            mkdir($parentDir, 0755, true);
        }

        // Move extracted directory to target location
        if (!rename($sourceDir, $targetDirectory)) {
            throw new Exception("Failed to move restored directory from {$sourceDir} to {$targetDirectory}");
        }

        // Restore file permissions
        $this->restoreFilePermissions($targetDirectory);

        Log::info("Directory restored successfully", [
            'source' => $sourceDir,
            'target' => $targetDirectory
        ]);
    }

    /**
     * Restore file permissions for a directory
     */
    private function restoreFilePermissions(string $directory)
    {
        try {
            // Set directory permissions
            chmod($directory, 0755);

            // Recursively set permissions for all files and subdirectories
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    chmod($item->getRealPath(), 0755);
                } else {
                    chmod($item->getRealPath(), 0644);
                }
            }

            Log::info("File permissions restored", ['directory' => $directory]);

        } catch (Exception $e) {
            Log::warning("Failed to restore file permissions", [
                'directory' => $directory,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Verify file restoration
     */
    private function verifyFileRestoration(array $directories)
    {
        foreach ($directories as $directory) {
            if (!file_exists($directory)) {
                throw new Exception("Restored directory does not exist: {$directory}");
            }

            if (!is_dir($directory)) {
                throw new Exception("Restored path is not a directory: {$directory}");
            }

            if (!is_readable($directory)) {
                throw new Exception("Restored directory is not readable: {$directory}");
            }
        }

        Log::info("File restoration verification passed", [
            'directories' => $directories
        ]);
    }

    /**
     * Rollback file restore using pre-restore backup
     */
    public function rollbackFileRestore(string $preRestoreBackup, array $directories)
    {
        try {
            if (!file_exists($preRestoreBackup)) {
                Log::error("Pre-restore backup file not found for rollback", [
                    'backup_path' => $preRestoreBackup
                ]);
                return false;
            }

            Log::info("Starting file restoration rollback", [
                'pre_restore_backup' => $preRestoreBackup,
                'directories' => $directories
            ]);

            // Remove current directories
            foreach ($directories as $directory) {
                if (file_exists($directory)) {
                    $this->removeDirectory($directory);
                }
            }

            // Restore from pre-restore backup
            $this->performFileRestore($preRestoreBackup, $directories);
            
            // Verify rollback
            $this->verifyFileRestoration($directories);

            Log::info("File restoration rollback completed successfully", [
                'pre_restore_backup' => $preRestoreBackup,
                'directories' => $directories
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("File restoration rollback failed", [
                'error' => $e->getMessage(),
                'pre_restore_backup' => $preRestoreBackup,
                'directories' => $directories
            ]);
            
            return false;
        }
    }

    /**
     * Recursively remove a directory and all its contents
     */
    private function removeDirectory(string $directory)
    {
        if (!file_exists($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($directory);
    }

    /**
     * Get restoration history
     */
    public function getRestorationHistory(int $limit = 50)
    {
        $restoreLogs = RestoreLog::with('backup')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $restoreLogs->map(function ($log) {
            return [
                'id' => $log->id,
                'backup_name' => $log->backup->name ?? 'Unknown',
                'restore_type' => $log->restore_type,
                'status' => $log->status,
                'restored_by' => $log->restored_by,
                'started_at' => $log->started_at,
                'completed_at' => $log->completed_at,
                'error_message' => $log->error_message,
                'metadata' => $log->metadata
            ];
        })->toArray();
    }
}