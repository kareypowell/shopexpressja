<?php

namespace App\Services;

use App\Models\Backup;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupStorageManager
{
    private BackupConfig $config;
    private string $storagePath;

    public function __construct(BackupConfig $config)
    {
        $this->config = $config;
        $this->storagePath = $this->config->getStoragePath();
        $this->ensureStorageDirectoryExists();
    }

    /**
     * Organize backup file into appropriate directory structure
     */
    public function organizeBackupFile(string $filePath, string $type): string
    {
        $filename = basename($filePath);
        $date = Carbon::now()->format('Y/m/d');
        $organizedPath = "{$this->storagePath}/{$type}/{$date}/{$filename}";
        
        $this->ensureDirectoryExists(dirname($organizedPath));
        
        if (File::exists($filePath) && $filePath !== $organizedPath) {
            File::move($filePath, $organizedPath);
        }
        
        return $organizedPath;
    }

    /**
     * Enforce retention policy by removing old backup files
     */
    public function enforceRetentionPolicy(): array
    {
        $results = [
            'database' => $this->cleanupBackupType('database', $this->config->getDatabaseRetentionDays()),
            'files' => $this->cleanupBackupType('files', $this->config->getFilesRetentionDays()),
        ];

        Log::info('Retention policy enforcement completed', $results);
        
        return $results;
    }

    /**
     * Clean up old backup files for a specific type
     */
    private function cleanupBackupType(string $type, int $retentionDays): array
    {
        $cutoffDate = Carbon::now()->subDays($retentionDays);
        $typePath = "{$this->storagePath}/{$type}";
        
        // Don't skip cleanup just because the directory doesn't exist
        // We still want to clean up database records

        $removedCount = 0;
        $freedSpace = 0;
        $errors = [];

        // Get total count of completed backups for this type
        $totalBackups = Backup::where('type', $type)
            ->where('status', 'completed')
            ->count();

        $minBackupsToKeep = $this->config->getMinBackupsToKeep();

        // Get backups from database that are older than retention period, ordered by creation date
        $oldBackups = Backup::where('type', $type)
            ->where('created_at', '<', $cutoffDate)
            ->where('status', 'completed')
            ->orderBy('created_at', 'asc')
            ->get();
            

            


        foreach ($oldBackups as $backup) {
            // Check if we would go below minimum backups to keep
            if ($minBackupsToKeep > 0 && ($totalBackups - $removedCount) <= $minBackupsToKeep) {
                break;
            }

            try {
                if (File::exists($backup->file_path)) {
                    $fileSize = File::size($backup->file_path);
                    
                    if (File::delete($backup->file_path)) {
                        $freedSpace += $fileSize;
                        $removedCount++;
                        
                        // Update backup record to mark as cleaned up
                        $backup->update(['status' => 'cleaned_up']);
                        
                        Log::info("Removed old backup file", [
                            'file' => $backup->file_path,
                            'size' => $fileSize,
                            'created_at' => $backup->created_at
                        ]);
                    }
                } else {
                    // File doesn't exist, mark backup as cleaned up
                    $backup->update(['status' => 'cleaned_up']);
                    $removedCount++;
                }
            } catch (\Exception $e) {
                $errors[] = "Failed to remove {$backup->file_path}: " . $e->getMessage();
                Log::error("Failed to remove backup file", [
                    'file' => $backup->file_path,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'removed' => $removedCount,
            'freed_space' => $freedSpace,
            'errors' => $errors
        ];
    }

    /**
     * Get storage usage information
     */
    public function getStorageUsage(): array
    {
        $totalSize = 0;
        $fileCount = 0;
        $typeBreakdown = [];

        foreach (['database', 'files'] as $type) {
            $typePath = "{$this->storagePath}/{$type}";
            $typeSize = 0;
            $typeCount = 0;

            if (File::exists($typePath)) {
                $files = File::allFiles($typePath);
                foreach ($files as $file) {
                    $size = $file->getSize();
                    $typeSize += $size;
                    $totalSize += $size;
                    $typeCount++;
                    $fileCount++;
                }
            }

            $typeBreakdown[$type] = [
                'size' => $typeSize,
                'count' => $typeCount,
                'formatted_size' => $this->formatBytes($typeSize)
            ];
        }

        return [
            'total_size' => $totalSize,
            'total_files' => $fileCount,
            'formatted_total_size' => $this->formatBytes($totalSize),
            'breakdown' => $typeBreakdown,
            'storage_path' => $this->storagePath
        ];
    }

    /**
     * Check if storage usage exceeds warning threshold
     */
    public function checkStorageWarnings(): array
    {
        $usage = $this->getStorageUsage();
        $warnings = [];
        
        $maxSize = $this->config->getMaxStorageSize();
        if ($maxSize > 0) {
            if ($usage['total_size'] > $maxSize) {
                $warnings[] = [
                    'type' => 'storage_exceeded',
                    'message' => "Backup storage ({$usage['formatted_total_size']}) exceeds maximum allowed size ({$this->formatBytes($maxSize)})",
                    'current_size' => $usage['total_size'],
                    'max_size' => $maxSize
                ];
            } else {
                $warningThreshold = $maxSize * 0.8; // 80% of max size
                if ($usage['total_size'] > $warningThreshold) {
                    $warnings[] = [
                        'type' => 'storage_warning',
                        'message' => "Backup storage ({$usage['formatted_total_size']}) is approaching maximum capacity",
                        'current_size' => $usage['total_size'],
                        'threshold' => $warningThreshold
                    ];
                }
            }
        }

        return $warnings;
    }

    /**
     * Get available disk space for backup storage
     */
    public function getAvailableDiskSpace(): int
    {
        return disk_free_space($this->storagePath) ?: 0;
    }

    /**
     * Check if there's enough space for a backup of given size
     */
    public function hasEnoughSpace(int $requiredBytes): bool
    {
        $availableSpace = $this->getAvailableDiskSpace();
        $buffer = 100 * 1024 * 1024; // 100MB buffer
        
        return $availableSpace > ($requiredBytes + $buffer);
    }

    /**
     * Clean up orphaned backup files (files without database records)
     */
    public function cleanupOrphanedFiles(): array
    {
        $removedCount = 0;
        $freedSpace = 0;
        $errors = [];

        foreach (['database', 'files'] as $type) {
            $typePath = "{$this->storagePath}/{$type}";
            
            if (!File::exists($typePath)) {
                continue;
            }

            $files = File::allFiles($typePath);
            foreach ($files as $file) {
                $filePath = $file->getPathname();
                
                // Check if this file has a corresponding database record
                $backupExists = Backup::where('file_path', $filePath)->exists();
                
                if (!$backupExists) {
                    try {
                        $fileSize = $file->getSize();
                        
                        if (File::delete($filePath)) {
                            $freedSpace += $fileSize;
                            $removedCount++;
                            
                            Log::info("Removed orphaned backup file", [
                                'file' => $filePath,
                                'size' => $fileSize
                            ]);
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Failed to remove orphaned file {$filePath}: " . $e->getMessage();
                        Log::error("Failed to remove orphaned backup file", [
                            'file' => $filePath,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        return [
            'removed' => $removedCount,
            'freed_space' => $freedSpace,
            'errors' => $errors
        ];
    }

    /**
     * Ensure storage directory exists
     */
    private function ensureStorageDirectoryExists(): void
    {
        $this->ensureDirectoryExists($this->storagePath);
        $this->ensureDirectoryExists("{$this->storagePath}/database");
        $this->ensureDirectoryExists("{$this->storagePath}/files");
    }

    /**
     * Ensure a directory exists with proper permissions
     */
    private function ensureDirectoryExists(string $path): void
    {
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    /**
     * Clean up old backups with detailed results for command interface
     */
    public function cleanupOldBackups(bool $dryRun = false): array
    {
        $deletedFiles = [];
        $errors = [];
        $totalFreedSpace = 0;

        foreach (['database', 'files'] as $type) {
            $retentionDays = $type === 'database' 
                ? $this->config->getDatabaseRetentionDays() 
                : $this->config->getFilesRetentionDays();
            
            $cutoffDate = Carbon::now()->subDays($retentionDays);
            
            $oldBackups = Backup::where('type', $type)
                ->where('created_at', '<', $cutoffDate)
                ->where('status', 'completed')
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($oldBackups as $backup) {
                try {
                    $fileSize = 0;
                    $ageInDays = Carbon::now()->diffInDays($backup->created_at);
                    
                    if (File::exists($backup->file_path)) {
                        $fileSize = File::size($backup->file_path);
                        
                        if (!$dryRun) {
                            if (File::delete($backup->file_path)) {
                                $backup->update(['status' => 'cleaned_up']);
                                $totalFreedSpace += $fileSize;
                            } else {
                                throw new \Exception("Failed to delete file");
                            }
                        } else {
                            $totalFreedSpace += $fileSize;
                        }
                    } else {
                        if (!$dryRun) {
                            $backup->update(['status' => 'cleaned_up']);
                        }
                    }

                    $deletedFiles[] = [
                        'name' => basename($backup->file_path),
                        'type' => $backup->type,
                        'size' => $fileSize,
                        'age_days' => $ageInDays,
                        'path' => $backup->file_path
                    ];

                } catch (\Exception $e) {
                    $errors[] = "Failed to " . ($dryRun ? "analyze" : "delete") . " {$backup->file_path}: " . $e->getMessage();
                }
            }
        }

        return [
            'success' => true,
            'deleted_files' => $deletedFiles,
            'errors' => $errors,
            'total_freed_space' => $totalFreedSpace,
            'retention' => [
                'database' => $this->config->getDatabaseRetentionDays(),
                'files' => $this->config->getFilesRetentionDays()
            ]
        ];
    }

    /**
     * Get comprehensive storage information for status display
     */
    public function getStorageInfo(): array
    {
        $usage = $this->getStorageUsage();
        $availableSpace = $this->getAvailableDiskSpace();
        $totalDiskSpace = disk_total_space($this->storagePath) ?: 0;
        
        $diskUsagePercent = $totalDiskSpace > 0 
            ? round(($totalDiskSpace - $availableSpace) / $totalDiskSpace * 100, 1)
            : 0;

        return [
            'path' => $this->storagePath,
            'total_files' => $usage['total_files'],
            'total_size' => $usage['total_size'],
            'available_space' => $availableSpace,
            'disk_usage_percent' => $diskUsagePercent,
            'retention' => [
                'database' => $this->config->getDatabaseRetentionDays(),
                'files' => $this->config->getFilesRetentionDays()
            ],
            'breakdown' => $usage['breakdown']
        ];
    }

    /**
     * Format bytes into human readable format
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}