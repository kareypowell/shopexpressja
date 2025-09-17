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

    public function __construct(BackupService $backupService, DatabaseBackupHandler $databaseHandler)
    {
        $this->backupService = $backupService;
        $this->databaseHandler = $databaseHandler;
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
                'status' => 'started',
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
                'restore_log_id' => $restoreLog?->id
            ]);

            return new RestoreResult(false, $e->getMessage(), [
                'restore_log_id' => $restoreLog?->id,
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
    private function createPreRestoreBackup(): string
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
    private function performDatabaseRestore(string $backupPath): void
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
    private function verifyDatabaseIntegrity(): void
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
    public function enableMaintenanceMode(): void
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
    public function disableMaintenanceMode(): void
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
    public function rollbackRestore(string $preRestoreBackup): bool
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
     * Get restoration history
     */
    public function getRestorationHistory(int $limit = 50): array
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