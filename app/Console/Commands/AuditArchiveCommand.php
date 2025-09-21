<?php

namespace App\Console\Commands;

use App\Services\AuditRetentionService;
use Illuminate\Console\Command;

class AuditArchiveCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'audit:archive 
                            {--event-type= : Archive specific event type only}
                            {--days= : Archive logs older than specified days}
                            {--list : List available archive files}
                            {--restore= : Restore from specific archive file}
                            {--preview : Show what would be archived without actually archiving}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     */
    protected $description = 'Archive old audit logs for long-term storage';

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
        $this->info('Audit Log Archive Tool');
        $this->info('=====================');

        // List archive files
        if ($this->option('list')) {
            return $this->listArchiveFiles();
        }

        // Restore from archive
        if ($restoreFile = $this->option('restore')) {
            return $this->restoreFromArchive($restoreFile);
        }

        // Preview mode
        if ($this->option('preview')) {
            return $this->showArchivePreview();
        }

        // Specific event type archival
        if ($eventType = $this->option('event-type')) {
            return $this->archiveEventType($eventType);
        }

        // Archive by days
        if ($days = $this->option('days')) {
            return $this->archiveByDays((int) $days);
        }

        // Full automated archival
        return $this->runFullArchival();
    }

    /**
     * List available archive files
     */
    protected function listArchiveFiles()
    {
        $this->info('Available Archive Files:');
        $this->newLine();

        $files = $this->auditRetentionService->listArchiveFiles();

        if (empty($files)) {
            $this->info('No archive files found.');
            return 0;
        }

        $headers = ['Filename', 'Event Type', 'Period', 'Size (MB)', 'Modified'];
        $rows = [];

        foreach ($files as $file) {
            $rows[] = [
                $file['filename'],
                $file['event_type'],
                $file['period'],
                $file['size_mb'],
                $file['modified_at'],
            ];
        }

        $this->table($headers, $rows);
        
        $totalSize = array_sum(array_column($files, 'size_mb'));
        $this->info("Total archive size: {$totalSize} MB");

        return 0;
    }

    /**
     * Restore from archive file
     */
    protected function restoreFromArchive(string $filename)
    {
        $this->info("Restoring audit logs from archive: {$filename}");

        if (!$this->option('force')) {
            if (!$this->confirm("Restore audit logs from {$filename}? This may create duplicate entries.")) {
                $this->info('Restore cancelled.');
                return 1;
            }
        }

        try {
            $results = $this->auditRetentionService->restoreFromArchive($filename);

            if (!empty($results['errors'])) {
                $this->error('Restore completed with errors:');
                foreach ($results['errors'] as $error) {
                    $this->error("  - {$error}");
                }
                return 1;
            }

            $this->info("Successfully restored {$results['total_restored']} audit log entries.");

            if (!empty($results['restored_by_type'])) {
                $this->newLine();
                $this->info('Restored by event type:');
                foreach ($results['restored_by_type'] as $eventType => $count) {
                    $this->info("  - {$eventType}: {$count} entries");
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to restore from archive: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show archive preview
     */
    protected function showArchivePreview()
    {
        $this->info('Generating archive preview...');
        
        // Get logs that would be archived (older than retention - 30 days)
        $stats = $this->auditRetentionService->getStorageStatistics();
        
        $this->info("Current audit log statistics:");
        $this->info("Total records: " . number_format($stats['total_records']));
        $this->info("Estimated storage: {$stats['estimated_storage_mb']} MB");
        
        if (!empty($stats['records_by_type'])) {
            $this->newLine();
            $this->info('Records by event type:');
            foreach ($stats['records_by_type'] as $eventType => $count) {
                $retentionDays = \App\Models\AuditSetting::getRetentionDays($eventType);
                $archiveDays = max(30, $retentionDays - 30);
                $this->info("  - {$eventType}: " . number_format($count) . " (archive after {$archiveDays} days)");
            }
        }

        $this->newLine();
        $this->info('Run without --preview flag to perform actual archival.');
        
        return 0;
    }

    /**
     * Archive specific event type
     */
    protected function archiveEventType(string $eventType)
    {
        $this->info("Archiving audit logs for event type: {$eventType}");

        if (!$this->option('force')) {
            if (!$this->confirm("Archive old {$eventType} audit logs?")) {
                $this->info('Archive cancelled.');
                return 1;
            }
        }

        try {
            $results = $this->auditRetentionService->archiveByEventType($eventType);

            if (!empty($results['errors'])) {
                $this->error('Archive completed with errors:');
                foreach ($results['errors'] as $error) {
                    $this->error("  - {$error}");
                }
            }

            $this->info("Successfully archived {$results['total_archived']} audit log entries for {$eventType}.");

            if (!empty($results['archive_files'])) {
                $this->newLine();
                $this->info('Created archive files:');
                foreach ($results['archive_files'] as $file) {
                    $this->info("  - {$file}");
                }
            }

            return empty($results['errors']) ? 0 : 1;
        } catch (\Exception $e) {
            $this->error("Failed to archive {$eventType}: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Archive by specific number of days
     */
    protected function archiveByDays(int $days)
    {
        $this->info("Archiving audit logs older than {$days} days...");

        if (!$this->option('force')) {
            if (!$this->confirm("Archive ALL audit logs older than {$days} days?")) {
                $this->info('Archive cancelled.');
                return 1;
            }
        }

        try {
            $results = $this->auditRetentionService->archiveOldLogs($days);

            if (!empty($results['errors'])) {
                $this->error('Archive completed with errors:');
                foreach ($results['errors'] as $error) {
                    $this->error("  - {$error}");
                }
            }

            $this->info("Successfully archived {$results['total_archived']} audit log entries older than {$days} days.");

            if (!empty($results['archived_by_type'])) {
                $this->newLine();
                $this->info('Archived by event type:');
                foreach ($results['archived_by_type'] as $eventType => $count) {
                    if ($count > 0) {
                        $this->info("  - {$eventType}: {$count} entries");
                    }
                }
            }

            if (!empty($results['archive_files'])) {
                $this->newLine();
                $this->info('Created archive files:');
                foreach ($results['archive_files'] as $file) {
                    $this->info("  - {$file}");
                }
            }

            return empty($results['errors']) ? 0 : 1;
        } catch (\Exception $e) {
            $this->error("Failed to archive audit logs: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Run full automated archival
     */
    protected function runFullArchival()
    {
        $this->info('Running automated audit log archival...');

        if (!$this->option('force')) {
            if (!$this->confirm('Continue with automated archival based on retention policies?')) {
                $this->info('Archive cancelled.');
                return 1;
            }
        }

        try {
            $results = $this->auditRetentionService->archiveOldLogs();

            if (!empty($results['errors'])) {
                $this->error('Archive completed with errors:');
                foreach ($results['errors'] as $error) {
                    $this->error("  - {$error}");
                }
            }

            if ($results['total_archived'] === 0) {
                $this->info('No audit logs needed archival based on current policies.');
                return 0;
            }

            $this->info("Archive completed successfully!");
            $this->info("Total archived: {$results['total_archived']} entries");

            if (!empty($results['archived_by_type'])) {
                $this->newLine();
                $this->info('Archived by event type:');
                foreach ($results['archived_by_type'] as $eventType => $count) {
                    if ($count > 0) {
                        $this->info("  - {$eventType}: {$count} entries");
                    }
                }
            }

            if (!empty($results['archive_files'])) {
                $this->newLine();
                $this->info('Created archive files:');
                foreach ($results['archive_files'] as $file) {
                    $this->info("  - {$file}");
                }
            }

            return empty($results['errors']) ? 0 : 1;
        } catch (\Exception $e) {
            $this->error("Failed to run automated archival: " . $e->getMessage());
            return 1;
        }
    }
}