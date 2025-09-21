<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\AuditSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuditRetentionService
{
    /**
     * Run automated cleanup based on retention policies
     */
    public function runAutomatedCleanup(): array
    {
        $results = [
            'total_deleted' => 0,
            'deleted_by_type' => [],
            'errors' => [],
            'started_at' => now(),
            'completed_at' => null,
        ];

        try {
            $retentionPolicies = AuditSetting::get('retention_policy', AuditSetting::DEFAULT_SETTINGS['retention_policy']);
            
            foreach ($retentionPolicies as $eventType => $retentionDays) {
                try {
                    $deleted = $this->cleanupEventType($eventType, $retentionDays);
                    $results['deleted_by_type'][$eventType] = $deleted;
                    $results['total_deleted'] += $deleted;
                    
                    Log::info("Audit cleanup completed for event type: {$eventType}", [
                        'event_type' => $eventType,
                        'retention_days' => $retentionDays,
                        'deleted_count' => $deleted
                    ]);
                } catch (\Exception $e) {
                    $error = "Failed to cleanup {$eventType}: " . $e->getMessage();
                    $results['errors'][] = $error;
                    Log::error($error, ['exception' => $e]);
                }
            }
            
            $results['completed_at'] = now();
            
            Log::info('Audit retention cleanup completed', $results);
            
        } catch (\Exception $e) {
            $results['errors'][] = 'General cleanup failure: ' . $e->getMessage();
            Log::error('Audit retention cleanup failed', ['exception' => $e]);
        }

        return $results;
    }

    /**
     * Clean up audit logs for a specific event type
     */
    public function cleanupEventType(string $eventType, int $retentionDays): int
    {
        $cutoffDate = now()->subDays($retentionDays);
        
        return AuditLog::where('event_type', $eventType)
            ->where('created_at', '<', $cutoffDate)
            ->delete();
    }

    /**
     * Get cleanup preview without actually deleting
     */
    public function getCleanupPreview(): array
    {
        $preview = [
            'total_to_delete' => 0,
            'by_event_type' => [],
            'oldest_records' => [],
        ];

        try {
            $retentionPolicies = AuditSetting::get('retention_policy', AuditSetting::DEFAULT_SETTINGS['retention_policy']);
            
            foreach ($retentionPolicies as $eventType => $retentionDays) {
                $cutoffDate = now()->subDays($retentionDays);
                
                $count = AuditLog::where('event_type', $eventType)
                    ->where('created_at', '<', $cutoffDate)
                    ->count();
                
                $oldest = AuditLog::where('event_type', $eventType)
                    ->where('created_at', '<', $cutoffDate)
                    ->oldest()
                    ->first();
                
                $preview['by_event_type'][$eventType] = [
                    'count' => $count,
                    'retention_days' => $retentionDays,
                    'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s'),
                    'oldest_record' => $oldest ? $oldest->created_at->format('Y-m-d H:i:s') : null,
                ];
                
                $preview['total_to_delete'] += $count;
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to generate cleanup preview', ['exception' => $e]);
        }

        return $preview;
    }

    /**
     * Archive old audit logs to a separate table or storage
     */
    public function archiveOldLogs(int $archiveAfterDays = 365): array
    {
        $results = [
            'total_archived' => 0,
            'archived_by_type' => [],
            'errors' => [],
            'archive_files' => [],
        ];

        try {
            $cutoffDate = now()->subDays($archiveAfterDays);
            
            // Get logs to archive in batches to avoid memory issues
            $batchSize = AuditSetting::getBatchSize();
            $totalProcessed = 0;
            
            AuditLog::where('created_at', '<', $cutoffDate)
                ->orderBy('created_at')
                ->chunk($batchSize, function ($logs) use (&$results, &$totalProcessed) {
                    $this->processBatchForArchival($logs, $results);
                    $totalProcessed += $logs->count();
                });
            
            if ($results['total_archived'] === 0) {
                return $results;
            }

            Log::info('Audit logs archived', [
                'total_archived' => $results['total_archived'],
                'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s'),
                'archived_by_type' => $results['archived_by_type'],
                'archive_files' => $results['archive_files']
            ]);
            
        } catch (\Exception $e) {
            $results['errors'][] = 'Archive operation failed: ' . $e->getMessage();
            Log::error('Audit log archival failed', ['exception' => $e]);
        }

        return $results;
    }

    /**
     * Process a batch of logs for archival
     */
    protected function processBatchForArchival($logs, array &$results): void
    {
        try {
            // Group by event type and date for organized archival
            $groupedLogs = $logs->groupBy(function ($log) {
                return $log->event_type . '_' . $log->created_at->format('Y-m');
            });

            foreach ($groupedLogs as $groupKey => $groupLogs) {
                [$eventType, $yearMonth] = explode('_', $groupKey, 2);
                
                // Create archive file
                $archiveFile = $this->createArchiveFile($eventType, $yearMonth, $groupLogs);
                
                if ($archiveFile) {
                    $results['archive_files'][] = $archiveFile;
                    $results['archived_by_type'][$eventType] = 
                        ($results['archived_by_type'][$eventType] ?? 0) + $groupLogs->count();
                    $results['total_archived'] += $groupLogs->count();
                }
            }
        } catch (\Exception $e) {
            $results['errors'][] = 'Batch archival failed: ' . $e->getMessage();
        }
    }

    /**
     * Create an archive file for a group of logs
     */
    protected function createArchiveFile(string $eventType, string $yearMonth, $logs): ?string
    {
        try {
            $archiveDir = storage_path('app/audit-archives');
            
            // Create directory if it doesn't exist
            if (!is_dir($archiveDir)) {
                mkdir($archiveDir, 0755, true);
            }

            $filename = "audit_{$eventType}_{$yearMonth}.json";
            $filepath = $archiveDir . '/' . $filename;

            // Prepare data for archival
            $archiveData = [
                'metadata' => [
                    'event_type' => $eventType,
                    'period' => $yearMonth,
                    'archived_at' => now()->toISOString(),
                    'record_count' => $logs->count(),
                    'version' => '1.0'
                ],
                'logs' => $logs->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'user_id' => $log->user_id,
                        'event_type' => $log->event_type,
                        'auditable_type' => $log->auditable_type,
                        'auditable_id' => $log->auditable_id,
                        'action' => $log->action,
                        'old_values' => $log->old_values,
                        'new_values' => $log->new_values,
                        'url' => $log->url,
                        'ip_address' => $log->ip_address,
                        'user_agent' => $log->user_agent,
                        'additional_data' => $log->additional_data,
                        'created_at' => $log->created_at->toISOString(),
                    ];
                })->toArray()
            ];

            // Write to file with compression
            $jsonData = json_encode($archiveData, JSON_PRETTY_PRINT);
            $compressedData = gzcompress($jsonData, 9);
            
            file_put_contents($filepath . '.gz', $compressedData);

            return $filename . '.gz';
        } catch (\Exception $e) {
            Log::error('Failed to create archive file', [
                'event_type' => $eventType,
                'year_month' => $yearMonth,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Archive logs by event type with configurable retention
     */
    public function archiveByEventType(string $eventType, ?int $archiveAfterDays = null): array
    {
        $results = [
            'event_type' => $eventType,
            'total_archived' => 0,
            'errors' => [],
            'archive_files' => [],
        ];

        try {
            // Use event-specific archival policy or default
            $archiveAfterDays = $archiveAfterDays ?? $this->getArchivalDays($eventType);
            $cutoffDate = now()->subDays($archiveAfterDays);
            
            $batchSize = AuditSetting::getBatchSize();
            
            AuditLog::where('event_type', $eventType)
                ->where('created_at', '<', $cutoffDate)
                ->orderBy('created_at')
                ->chunk($batchSize, function ($logs) use (&$results) {
                    $this->processBatchForArchival($logs, $results);
                });

            Log::info("Audit logs archived for event type: {$eventType}", $results);
            
        } catch (\Exception $e) {
            $results['errors'][] = 'Event type archival failed: ' . $e->getMessage();
            Log::error("Audit log archival failed for event type: {$eventType}", ['exception' => $e]);
        }

        return $results;
    }

    /**
     * Get archival days for event type (typically longer than retention)
     */
    protected function getArchivalDays(string $eventType): int
    {
        $retentionDays = AuditSetting::getRetentionDays($eventType);
        
        // Archive before deletion - typically 30 days before retention expires
        return max(30, $retentionDays - 30);
    }

    /**
     * Restore archived logs from file
     */
    public function restoreFromArchive(string $archiveFile): array
    {
        $results = [
            'total_restored' => 0,
            'errors' => [],
            'restored_by_type' => [],
        ];

        try {
            $archiveDir = storage_path('app/audit-archives');
            $filepath = $archiveDir . '/' . $archiveFile;

            if (!file_exists($filepath)) {
                $results['errors'][] = "Archive file not found: {$archiveFile}";
                return $results;
            }

            // Read and decompress file
            $compressedData = file_get_contents($filepath);
            $jsonData = gzuncompress($compressedData);
            $archiveData = json_decode($jsonData, true);

            if (!$archiveData || !isset($archiveData['logs'])) {
                $results['errors'][] = "Invalid archive file format: {$archiveFile}";
                return $results;
            }

            // Restore logs in batches
            $batchSize = AuditSetting::getBatchSize();
            $logs = collect($archiveData['logs']);
            
            foreach ($logs->chunk($batchSize) as $batch) {
                $restored = $this->restoreBatch($batch->toArray());
                $results['total_restored'] += $restored;
            }

            $eventType = $archiveData['metadata']['event_type'] ?? 'unknown';
            $results['restored_by_type'][$eventType] = $results['total_restored'];

            Log::info("Audit logs restored from archive: {$archiveFile}", $results);
            
        } catch (\Exception $e) {
            $results['errors'][] = 'Archive restoration failed: ' . $e->getMessage();
            Log::error("Audit log restoration failed for file: {$archiveFile}", ['exception' => $e]);
        }

        return $results;
    }

    /**
     * Restore a batch of logs
     */
    protected function restoreBatch(array $logs): int
    {
        $restored = 0;
        
        foreach ($logs as $logData) {
            try {
                // Check if log already exists
                if (!AuditLog::where('id', $logData['id'])->exists()) {
                    AuditLog::create([
                        'id' => $logData['id'],
                        'user_id' => $logData['user_id'],
                        'event_type' => $logData['event_type'],
                        'auditable_type' => $logData['auditable_type'],
                        'auditable_id' => $logData['auditable_id'],
                        'action' => $logData['action'],
                        'old_values' => $logData['old_values'],
                        'new_values' => $logData['new_values'],
                        'url' => $logData['url'],
                        'ip_address' => $logData['ip_address'],
                        'user_agent' => $logData['user_agent'],
                        'additional_data' => $logData['additional_data'],
                        'created_at' => $logData['created_at'],
                    ]);
                    $restored++;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to restore individual log entry', [
                    'log_id' => $logData['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $restored;
    }

    /**
     * List available archive files
     */
    public function listArchiveFiles(): array
    {
        $archiveDir = storage_path('app/audit-archives');
        $files = [];

        if (!is_dir($archiveDir)) {
            return $files;
        }

        foreach (glob($archiveDir . '/*.gz') as $filepath) {
            $filename = basename($filepath);
            $filesize = filesize($filepath);
            $modified = filemtime($filepath);

            // Try to extract metadata from filename
            if (preg_match('/audit_(.+)_(\d{4}-\d{2})\.json\.gz/', $filename, $matches)) {
                $eventType = $matches[1];
                $period = $matches[2];
            } else {
                $eventType = 'unknown';
                $period = 'unknown';
            }

            $files[] = [
                'filename' => $filename,
                'event_type' => $eventType,
                'period' => $period,
                'size_bytes' => $filesize,
                'size_mb' => round($filesize / 1024 / 1024, 2),
                'modified_at' => date('Y-m-d H:i:s', $modified),
            ];
        }

        // Sort by modification date (newest first)
        usort($files, function ($a, $b) {
            return strtotime($b['modified_at']) - strtotime($a['modified_at']);
        });

        return $files;
    }

    /**
     * Get storage statistics for audit logs
     */
    public function getStorageStatistics(): array
    {
        try {
            $stats = [
                'total_records' => AuditLog::count(),
                'records_by_type' => [],
                'records_by_age' => [],
                'estimated_storage_mb' => 0,
                'oldest_record' => null,
                'newest_record' => null,
            ];

            // Records by event type
            $stats['records_by_type'] = AuditLog::select('event_type', DB::raw('count(*) as count'))
                ->groupBy('event_type')
                ->pluck('count', 'event_type')
                ->toArray();

            // Records by age ranges
            $ageRanges = [
                'last_24h' => now()->subDay(),
                'last_7d' => now()->subDays(7),
                'last_30d' => now()->subDays(30),
                'last_90d' => now()->subDays(90),
                'last_365d' => now()->subDays(365),
            ];

            foreach ($ageRanges as $range => $date) {
                $stats['records_by_age'][$range] = AuditLog::where('created_at', '>=', $date)->count();
            }

            // Oldest and newest records
            $oldest = AuditLog::oldest()->first();
            $newest = AuditLog::latest()->first();
            
            $stats['oldest_record'] = $oldest ? $oldest->created_at->format('Y-m-d H:i:s') : null;
            $stats['newest_record'] = $newest ? $newest->created_at->format('Y-m-d H:i:s') : null;

            // Estimate storage usage (rough calculation)
            $avgRecordSize = 1024; // Approximate 1KB per record
            $stats['estimated_storage_mb'] = round(($stats['total_records'] * $avgRecordSize) / 1024 / 1024, 2);

            return $stats;
            
        } catch (\Exception $e) {
            Log::error('Failed to get audit storage statistics', ['exception' => $e]);
            return ['error' => 'Failed to calculate storage statistics'];
        }
    }

    /**
     * Clean up logs older than specified days regardless of type
     */
    public function cleanupOlderThan(int $days): int
    {
        $cutoffDate = now()->subDays($days);
        
        return AuditLog::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * Clean up logs by specific criteria
     */
    public function cleanupByCriteria(array $criteria): int
    {
        $query = AuditLog::query();

        if (isset($criteria['event_type'])) {
            $query->where('event_type', $criteria['event_type']);
        }

        if (isset($criteria['user_id'])) {
            $query->where('user_id', $criteria['user_id']);
        }

        if (isset($criteria['before_date'])) {
            $query->where('created_at', '<', $criteria['before_date']);
        }

        if (isset($criteria['after_date'])) {
            $query->where('created_at', '>', $criteria['after_date']);
        }

        if (isset($criteria['ip_address'])) {
            $query->where('ip_address', $criteria['ip_address']);
        }

        return $query->delete();
    }

    /**
     * Get retention policy recommendations based on current data
     */
    public function getRetentionRecommendations(): array
    {
        $recommendations = [];

        try {
            $stats = $this->getStorageStatistics();
            
            // Recommend shorter retention for high-volume event types
            foreach ($stats['records_by_type'] as $eventType => $count) {
                $currentRetention = AuditSetting::getRetentionDays($eventType);
                
                if ($count > 100000) { // High volume
                    $recommendations[] = [
                        'event_type' => $eventType,
                        'current_retention' => $currentRetention,
                        'recommended_retention' => max(30, $currentRetention - 30),
                        'reason' => 'High volume event type - consider shorter retention',
                        'record_count' => $count,
                    ];
                } elseif ($count < 1000 && $currentRetention < 365) { // Low volume
                    $recommendations[] = [
                        'event_type' => $eventType,
                        'current_retention' => $currentRetention,
                        'recommended_retention' => min(365, $currentRetention + 90),
                        'reason' => 'Low volume event type - can afford longer retention',
                        'record_count' => $count,
                    ];
                }
            }

            // Storage-based recommendations
            if ($stats['estimated_storage_mb'] > 1000) { // > 1GB
                $recommendations[] = [
                    'type' => 'storage',
                    'message' => 'Audit logs are using significant storage. Consider implementing archival.',
                    'storage_mb' => $stats['estimated_storage_mb'],
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to generate retention recommendations', ['exception' => $e]);
        }

        return $recommendations;
    }

    /**
     * Validate retention policy configuration
     */
    public function validateRetentionPolicy(array $policy): array
    {
        $errors = [];
        $warnings = [];

        foreach ($policy as $eventType => $days) {
            if (!is_numeric($days) || $days < 1) {
                $errors[] = "Invalid retention period for {$eventType}: must be at least 1 day";
            } elseif ($days > 3650) { // 10 years
                $warnings[] = "Very long retention period for {$eventType}: {$days} days";
            }

            // Special validation for financial transactions (compliance)
            if ($eventType === 'financial_transaction' && $days < 2555) { // 7 years
                $warnings[] = "Financial transaction retention is less than 7 years - may not meet compliance requirements";
            }

            // Security events should have reasonable retention
            if ($eventType === 'security_event' && $days < 90) {
                $warnings[] = "Security event retention is less than 90 days - consider longer retention for security analysis";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Get comprehensive retention and archival status
     */
    public function getRetentionStatus(): array
    {
        try {
            $stats = $this->getStorageStatistics();
            $retentionPolicies = AuditSetting::get('retention_policy', AuditSetting::DEFAULT_SETTINGS['retention_policy']);
            
            $status = [
                'total_records' => $stats['total_records'],
                'estimated_storage_mb' => $stats['estimated_storage_mb'],
                'by_event_type' => [],
                'cleanup_needed' => false,
                'archive_needed' => false,
                'recommendations' => [],
            ];

            foreach ($retentionPolicies as $eventType => $retentionDays) {
                $cutoffDate = now()->subDays($retentionDays);
                $archiveDate = now()->subDays(max(30, $retentionDays - 30));
                
                $totalCount = AuditLog::where('event_type', $eventType)->count();
                $expiredCount = AuditLog::where('event_type', $eventType)
                    ->where('created_at', '<', $cutoffDate)
                    ->count();
                $archiveCount = AuditLog::where('event_type', $eventType)
                    ->where('created_at', '<', $archiveDate)
                    ->where('created_at', '>=', $cutoffDate)
                    ->count();

                $status['by_event_type'][$eventType] = [
                    'total_records' => $totalCount,
                    'expired_records' => $expiredCount,
                    'archive_ready' => $archiveCount,
                    'retention_days' => $retentionDays,
                    'cutoff_date' => $cutoffDate->format('Y-m-d'),
                    'archive_date' => $archiveDate->format('Y-m-d'),
                ];

                if ($expiredCount > 0) {
                    $status['cleanup_needed'] = true;
                }
                
                if ($archiveCount > 0) {
                    $status['archive_needed'] = true;
                }
            }

            // Add recommendations
            if ($status['cleanup_needed']) {
                $status['recommendations'][] = 'Run audit:cleanup to remove expired logs';
            }
            
            if ($status['archive_needed']) {
                $status['recommendations'][] = 'Run audit:archive to archive old logs before cleanup';
            }

            if ($stats['estimated_storage_mb'] > 500) {
                $status['recommendations'][] = 'Consider reducing retention periods for high-volume event types';
            }

            return $status;
            
        } catch (\Exception $e) {
            Log::error('Failed to get retention status', ['exception' => $e]);
            return ['error' => 'Failed to calculate retention status'];
        }
    }

    /**
     * Optimize retention policies based on current usage
     */
    public function optimizeRetentionPolicies(): array
    {
        $results = [
            'optimizations' => [],
            'current_policies' => [],
            'recommended_policies' => [],
            'estimated_savings_mb' => 0,
        ];

        try {
            $stats = $this->getStorageStatistics();
            $currentPolicies = AuditSetting::get('retention_policy', AuditSetting::DEFAULT_SETTINGS['retention_policy']);
            $results['current_policies'] = $currentPolicies;

            foreach ($currentPolicies as $eventType => $currentDays) {
                $recordCount = $stats['records_by_type'][$eventType] ?? 0;
                $recommendedDays = $this->calculateOptimalRetention($eventType, $recordCount, $currentDays);
                
                $results['recommended_policies'][$eventType] = $recommendedDays;
                
                if ($recommendedDays !== $currentDays) {
                    $daysDiff = $currentDays - $recommendedDays;
                    $estimatedSavings = ($recordCount * $daysDiff / $currentDays) * 0.001; // Rough MB estimate
                    
                    $results['optimizations'][] = [
                        'event_type' => $eventType,
                        'current_days' => $currentDays,
                        'recommended_days' => $recommendedDays,
                        'change' => $daysDiff > 0 ? 'reduce' : 'increase',
                        'days_difference' => abs($daysDiff),
                        'record_count' => $recordCount,
                        'estimated_savings_mb' => max(0, $estimatedSavings),
                        'reason' => $this->getOptimizationReason($eventType, $recordCount, $currentDays, $recommendedDays),
                    ];
                    
                    $results['estimated_savings_mb'] += max(0, $estimatedSavings);
                }
            }

            return $results;
            
        } catch (\Exception $e) {
            Log::error('Failed to optimize retention policies', ['exception' => $e]);
            return ['error' => 'Failed to optimize retention policies'];
        }
    }

    /**
     * Calculate optimal retention period for an event type
     */
    protected function calculateOptimalRetention(string $eventType, int $recordCount, int $currentDays): int
    {
        // Base retention on compliance requirements and volume
        switch ($eventType) {
            case 'financial_transaction':
                $baseRetention = 2555; // 7 years for compliance
                break;
            case 'security_event':
                $baseRetention = 365; // 1 year for security analysis
                break;
            case 'authentication':
                $baseRetention = 180; // 6 months for audit trails
                break;
            case 'authorization':
                $baseRetention = 365; // 1 year for permission tracking
                break;
            case 'model_deleted':
                $baseRetention = 365; // 1 year for recovery purposes
                break;
            default:
                $baseRetention = 90; // 3 months default
                break;
        }

        // Adjust based on volume
        if ($recordCount > 100000) {
            // High volume - reduce retention if possible
            return max($baseRetention, min($currentDays, $baseRetention + 30));
        } elseif ($recordCount < 1000) {
            // Low volume - can afford longer retention
            return min(365, max($currentDays, $baseRetention));
        }

        return $baseRetention;
    }

    /**
     * Get reason for retention optimization
     */
    protected function getOptimizationReason(string $eventType, int $recordCount, int $currentDays, int $recommendedDays): string
    {
        if ($recommendedDays < $currentDays) {
            if ($recordCount > 100000) {
                return 'High volume event type - reducing retention to manage storage';
            }
            return 'Retention period longer than necessary for event type';
        } else {
            if ($recordCount < 1000) {
                return 'Low volume event type - can afford longer retention for better audit trail';
            }
            return 'Compliance or security requirements suggest longer retention';
        }
    }

    /**
     * Apply optimized retention policies
     */
    public function applyOptimizedPolicies(array $optimizedPolicies): bool
    {
        try {
            $validation = $this->validateRetentionPolicy($optimizedPolicies);
            
            if (!$validation['valid']) {
                Log::error('Cannot apply invalid retention policies', $validation);
                return false;
            }

            AuditSetting::set('retention_policy', $optimizedPolicies);
            
            Log::info('Applied optimized retention policies', [
                'new_policies' => $optimizedPolicies
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to apply optimized retention policies', ['exception' => $e]);
            return false;
        }
    }
}