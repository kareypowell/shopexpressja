<?php

namespace App\Console\Commands;

use App\Services\BackupMonitorService;
use App\Services\BackupNotificationService;
use Illuminate\Console\Command;

class BackupHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:health-check 
                            {--send-alerts : Send alerts if issues are found}
                            {--daily-summary : Send daily health summary}
                            {--json : Output results as JSON}';

    /**
     * The console command description.
     */
    protected $description = 'Check backup system health and optionally send alerts';

    protected BackupMonitorService $monitorService;
    protected BackupNotificationService $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(
        BackupMonitorService $monitorService,
        BackupNotificationService $notificationService
    ) {
        parent::__construct();
        $this->monitorService = $monitorService;
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking backup system health...');

        try {
            $systemHealth = $this->monitorService->getSystemHealth();

            if ($this->option('json')) {
                $this->line(json_encode($systemHealth, JSON_PRETTY_PRINT));
                return 0;
            }

            $this->displayHealthStatus($systemHealth);

            // Send alerts if requested and needed
            if ($this->option('send-alerts')) {
                $this->handleAlerts($systemHealth);
            }

            // Send daily summary if requested
            if ($this->option('daily-summary')) {
                $this->handleDailySummary();
            }

            return $this->getExitCode($systemHealth);

        } catch (\Exception $e) {
            $this->error('Health check failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Display health status in console
     */
    protected function displayHealthStatus(array $systemHealth): void
    {
        $overallStatus = $systemHealth['overall_status'];
        
        // Display overall status with color coding
        $statusColor = match($overallStatus) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'critical' => 'red',
            default => 'white'
        };

        $this->line('');
        $this->line('<fg=' . $statusColor . '>Overall Status: ' . strtoupper($overallStatus) . '</>');
        $this->line('');

        // Display recent backups
        $recentBackups = $systemHealth['recent_backups'];
        $this->info('Recent Backups (7 days):');
        $this->line("  Total: {$recentBackups['total']}");
        $this->line("  Successful: {$recentBackups['successful']}");
        $this->line("  Failed: {$recentBackups['failed']}");
        $this->line("  Success Rate: {$recentBackups['success_rate']}%");
        
        if ($recentBackups['last_successful']) {
            $lastSuccess = $recentBackups['last_successful'];
            $this->line("  Last Successful: {$lastSuccess['name']} ({$lastSuccess['created_at']->diffForHumans()})");
        } else {
            $this->line("  Last Successful: None");
        }

        $this->line('');

        // Display storage usage
        $storage = $systemHealth['storage_usage'];
        $storageColor = $storage['is_critical'] ? 'red' : ($storage['is_warning'] ? 'yellow' : 'green');
        $this->info('Storage Usage:');
        $this->line("  <fg={$storageColor}>Usage: {$storage['usage_percentage']}% ({$storage['total_size_mb']} MB)</>");
        $this->line("  Files: {$storage['file_count']}");
        $this->line("  Max Size: {$storage['max_size_mb']} MB");

        $this->line('');

        // Display schedule health
        $scheduleHealth = $systemHealth['schedule_health'];
        $this->info('Backup Schedules:');
        $this->line("  Total: {$scheduleHealth['total_schedules']}");
        $this->line("  Healthy: {$scheduleHealth['healthy_schedules']}");
        $this->line("  Health: {$scheduleHealth['health_percentage']}%");

        if (!empty($scheduleHealth['overdue_schedules'])) {
            $this->line('  <fg=yellow>Overdue Schedules:</>');
            foreach ($scheduleHealth['overdue_schedules'] as $overdue) {
                $this->line("    - {$overdue['name']} (overdue by {$overdue['overdue_hours']} hours)");
            }
        }

        $this->line('');

        // Display warnings
        $warnings = $systemHealth['warnings'];
        if (!empty($warnings)) {
            $this->warn('System Warnings:');
            foreach ($warnings as $warning) {
                $warningColor = $warning['severity'] === 'critical' ? 'red' : 'yellow';
                $icon = $warning['severity'] === 'critical' ? '🚨' : '⚠️';
                $this->line("  <fg={$warningColor}>{$icon} {$warning['message']}</>");
            }
        } else {
            $this->info('No warnings detected.');
        }

        // Display failed backups
        $failedBackups = $systemHealth['failed_backups'];
        if ($failedBackups->isNotEmpty()) {
            $this->line('');
            $this->warn('Recent Failed Backups:');
            foreach ($failedBackups->take(5) as $failed) {
                $this->line("  - {$failed['name']} ({$failed['type']}) - {$failed['created_at']->diffForHumans()}");
                $this->line("    Error: {$failed['error_message']}");
            }
        }
    }

    /**
     * Handle sending alerts
     */
    protected function handleAlerts(array $systemHealth): void
    {
        if ($this->monitorService->shouldSendAlert()) {
            $this->info('Sending health alerts...');
            
            if ($this->notificationService->notifySystemHealthAlert()) {
                $this->info('Health alerts sent successfully.');
            } else {
                $this->warn('Failed to send health alerts.');
            }
        } else {
            $this->info('No alerts needed at this time.');
        }
    }

    /**
     * Handle sending daily summary
     */
    protected function handleDailySummary(): void
    {
        $this->info('Sending daily health summary...');
        
        if ($this->notificationService->sendDailyHealthSummary()) {
            $this->info('Daily summary sent successfully.');
        } else {
            $this->info('Daily summary not sent (no issues or disabled).');
        }
    }

    /**
     * Get appropriate exit code based on system health
     */
    protected function getExitCode(array $systemHealth): int
    {
        return match($systemHealth['overall_status']) {
            'critical' => 2,
            'warning' => 1,
            'healthy' => 0,
            default => 0
        };
    }
}