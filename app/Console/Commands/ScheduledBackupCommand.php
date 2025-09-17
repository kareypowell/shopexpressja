<?php

namespace App\Console\Commands;

use App\Models\BackupSchedule;
use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScheduledBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:scheduled 
                            {--schedule-id= : Run specific schedule by ID}
                            {--dry-run : Show what would be executed without running}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute scheduled backup operations';

    /**
     * The backup service instance.
     *
     * @var BackupService
     */
    protected $backupService;

    /**
     * Create a new command instance.
     *
     * @param BackupService $backupService
     */
    public function __construct(BackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $scheduleId = $this->option('schedule-id');
        $dryRun = $this->option('dry-run');

        if ($scheduleId) {
            return $this->runSpecificSchedule($scheduleId, $dryRun);
        }

        return $this->runDueSchedules($dryRun);
    }

    /**
     * Run a specific schedule by ID.
     *
     * @param int $scheduleId
     * @param bool $dryRun
     * @return int
     */
    protected function runSpecificSchedule($scheduleId, $dryRun = false)
    {
        $schedule = BackupSchedule::find($scheduleId);

        if (!$schedule) {
            $this->error("Schedule with ID {$scheduleId} not found.");
            return 1;
        }

        if (!$schedule->is_active) {
            $this->warn("Schedule '{$schedule->name}' is not active.");
            return 0;
        }

        if ($dryRun) {
            $this->info("Would execute schedule: {$schedule->name} ({$schedule->type})");
            return 0;
        }

        return $this->executeSchedule($schedule);
    }

    /**
     * Run all due schedules.
     *
     * @param bool $dryRun
     * @return int
     */
    protected function runDueSchedules($dryRun = false)
    {
        $dueSchedules = BackupSchedule::due()->get();

        if ($dueSchedules->isEmpty()) {
            $this->info('No scheduled backups are due.');
            return 0;
        }

        $this->info("Found {$dueSchedules->count()} due schedule(s).");

        if ($dryRun) {
            foreach ($dueSchedules as $schedule) {
                $this->info("Would execute: {$schedule->name} ({$schedule->type}) - Due: {$schedule->next_run_at}");
            }
            return 0;
        }

        $successCount = 0;
        $failureCount = 0;

        foreach ($dueSchedules as $schedule) {
            $result = $this->executeSchedule($schedule);
            
            if ($result === 0) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        $this->info("Scheduled backup execution completed.");
        $this->info("Successful: {$successCount}, Failed: {$failureCount}");

        return $failureCount > 0 ? 1 : 0;
    }

    /**
     * Execute a specific backup schedule.
     *
     * @param BackupSchedule $schedule
     * @return int
     */
    protected function executeSchedule(BackupSchedule $schedule)
    {
        $this->info("Executing scheduled backup: {$schedule->name}");

        try {
            // Prepare backup options based on schedule type
            $options = [
                'type' => $schedule->type,
                'name' => $this->generateBackupName($schedule),
                'retention_days' => $schedule->retention_days,
                'scheduled' => true,
                'schedule_id' => $schedule->id,
            ];

            // Execute the backup
            $result = $this->backupService->createManualBackup($options);

            if ($result->isSuccess()) {
                $this->info("✓ Backup completed successfully: {$result->getFilePath()}");
                $this->info("  Size: " . $this->formatBytes($result->getFileSize()));
                
                // Mark schedule as run and calculate next run time
                $schedule->markAsRun();
                
                Log::info('Scheduled backup completed successfully', [
                    'schedule_id' => $schedule->id,
                    'schedule_name' => $schedule->name,
                    'backup_type' => $schedule->type,
                    'file_path' => $result->getFilePath(),
                    'file_size' => $result->getFileSize(),
                    'next_run' => $schedule->next_run_at,
                ]);

                return 0;
            } else {
                $this->error("✗ Backup failed: {$result->getErrorMessage()}");
                
                Log::error('Scheduled backup failed', [
                    'schedule_id' => $schedule->id,
                    'schedule_name' => $schedule->name,
                    'backup_type' => $schedule->type,
                    'error' => $result->getErrorMessage(),
                ]);

                return 1;
            }
        } catch (\Exception $e) {
            $this->error("✗ Backup failed with exception: {$e->getMessage()}");
            
            Log::error('Scheduled backup failed with exception', [
                'schedule_id' => $schedule->id,
                'schedule_name' => $schedule->name,
                'backup_type' => $schedule->type,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    /**
     * Generate a backup name for scheduled backups.
     *
     * @param BackupSchedule $schedule
     * @return string
     */
    protected function generateBackupName(BackupSchedule $schedule)
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $schedule->name);
        
        return "scheduled_{$cleanName}_{$timestamp}";
    }

    /**
     * Format bytes into human readable format.
     *
     * @param int $bytes
     * @return string
     */
    protected function formatBytes($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}