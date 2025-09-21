<?php

namespace App\Console\Commands;

use App\Services\AuditRetentionService;
use Illuminate\Console\Command;

class AuditCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'audit:cleanup 
                            {--preview : Show what would be deleted without actually deleting}
                            {--event-type= : Clean up specific event type only}
                            {--days= : Override retention days for all event types}
                            {--archive : Archive logs before deletion}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old audit logs based on retention policies';

    /**
     * The audit retention service.
     */
    protected $auditRetentionService;

    /**
     * Create a new command instance.
     */
    public function __construct(AuditRetentionService $auditRetentionService)
    {
        parent::__construct();
        $this->auditRetentionService = $auditRetentionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Audit Log Cleanup Tool');
        $this->info('====================');

        // Preview mode
        if ($this->option('preview')) {
            return $this->showPreview();
        }

        // Archive before cleanup if requested
        if ($this->option('archive')) {
            $this->info('Archiving logs before cleanup...');
            $archiveResults = $this->auditRetentionService->archiveOldLogs();
            
            if (!empty($archiveResults['errors'])) {
                $this->warn('Archive completed with errors:');
                foreach ($archiveResults['errors'] as $error) {
                    $this->warn("  - {$error}");
                }
            } else {
                $this->info("Archived {$archiveResults['total_archived']} entries before cleanup.");
            }
            $this->newLine();
        }

        // Specific event type cleanup
        if ($eventType = $this->option('event-type')) {
            return $this->cleanupEventType($eventType);
        }

        // Override retention days
        if ($days = $this->option('days')) {
            return $this->cleanupByDays((int) $days);
        }

        // Full automated cleanup
        return $this->runFullCleanup();
    }

    /**
     * Show cleanup preview without deleting
     */
    protected function showPreview()
    {
        $this->info('Generating cleanup preview...');
        
        $preview = $this->auditRetentionService->getCleanupPreview();
        
        if ($preview['total_to_delete'] === 0) {
            $this->info('No audit logs need cleanup based on current retention policies.');
            return 0;
        }

        $this->info("Total records to be deleted: {$preview['total_to_delete']}");
        $this->newLine();

        $headers = ['Event Type', 'Records to Delete', 'Retention (Days)', 'Cutoff Date', 'Oldest Record'];
        $rows = [];

        foreach ($preview['by_event_type'] as $eventType => $data) {
            if ($data['count'] > 0) {
                $rows[] = [
                    $eventType,
                    number_format($data['count']),
                    $data['retention_days'],
                    $data['cutoff_date'],
                    $data['oldest_record'] ?? 'N/A',
                ];
            }
        }

        $this->table($headers, $rows);
        
        $this->newLine();
        $this->info('Run without --preview flag to perform actual cleanup.');
        
        return 0;
    }

    /**
     * Clean up specific event type
     */
    protected function cleanupEventType(string $eventType)
    {
        $this->info("Cleaning up audit logs for event type: {$eventType}");
        
        // Get retention days for this event type
        $retentionDays = \App\Models\AuditSetting::getRetentionDays($eventType);
        
        if (!$this->option('force')) {
            if (!$this->confirm("Delete {$eventType} audit logs older than {$retentionDays} days?")) {
                $this->info('Cleanup cancelled.');
                return 1;
            }
        }

        try {
            $deleted = $this->auditRetentionService->cleanupEventType($eventType, $retentionDays);
            
            $this->info("Successfully deleted {$deleted} audit log entries for {$eventType}.");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to cleanup {$eventType}: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Clean up by specific number of days
     */
    protected function cleanupByDays(int $days)
    {
        $this->info("Cleaning up audit logs older than {$days} days...");
        
        if (!$this->option('force')) {
            if (!$this->confirm("Delete ALL audit logs older than {$days} days?")) {
                $this->info('Cleanup cancelled.');
                return 1;
            }
        }

        try {
            $deleted = $this->auditRetentionService->cleanupOlderThan($days);
            
            $this->info("Successfully deleted {$deleted} audit log entries older than {$days} days.");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to cleanup audit logs: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Run full automated cleanup
     */
    protected function runFullCleanup()
    {
        $this->info('Running automated audit log cleanup based on retention policies...');
        
        if (!$this->option('force')) {
            $preview = $this->auditRetentionService->getCleanupPreview();
            
            if ($preview['total_to_delete'] === 0) {
                $this->info('No audit logs need cleanup based on current retention policies.');
                return 0;
            }

            $this->warn("This will delete {$preview['total_to_delete']} audit log entries.");
            
            if (!$this->confirm('Continue with cleanup?')) {
                $this->info('Cleanup cancelled.');
                return 1;
            }
        }

        try {
            $results = $this->auditRetentionService->runAutomatedCleanup();
            
            if (!empty($results['errors'])) {
                $this->error('Cleanup completed with errors:');
                foreach ($results['errors'] as $error) {
                    $this->error("  - {$error}");
                }
            }

            $this->info("Cleanup completed successfully!");
            $this->info("Total deleted: {$results['total_deleted']} entries");
            
            if (!empty($results['deleted_by_type'])) {
                $this->newLine();
                $this->info('Deleted by event type:');
                foreach ($results['deleted_by_type'] as $eventType => $count) {
                    if ($count > 0) {
                        $this->info("  - {$eventType}: {$count} entries");
                    }
                }
            }

            $duration = $results['completed_at']->diffInSeconds($results['started_at']);
            $this->info("Cleanup duration: {$duration} seconds");
            
            return empty($results['errors']) ? 0 : 1;
        } catch (\Exception $e) {
            $this->error("Failed to run automated cleanup: " . $e->getMessage());
            return 1;
        }
    }
}