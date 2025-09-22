<?php

namespace App\Console\Commands;

use App\Services\ExportJobMonitorService;
use Illuminate\Console\Command;

class MonitorExportJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reports:monitor-exports 
                            {--reset-stuck : Reset stuck export jobs}
                            {--health-check : Perform system health check}
                            {--send-alerts : Send health alerts if issues detected}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor export job health and performance';

    protected $monitorService;

    /**
     * Create a new command instance.
     */
    public function __construct(ExportJobMonitorService $monitorService)
    {
        parent::__construct();
        $this->monitorService = $monitorService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting export job monitoring...');

        try {
            // Reset stuck jobs if requested
            if ($this->option('reset-stuck')) {
                $this->resetStuckJobs();
            }

            // Perform health check if requested
            if ($this->option('health-check')) {
                $this->performHealthCheck();
            }

            // Send alerts if requested
            if ($this->option('send-alerts')) {
                $this->checkAndSendAlerts();
            }

            // If no specific options, do basic monitoring
            if (!$this->option('reset-stuck') && !$this->option('health-check') && !$this->option('send-alerts')) {
                $this->performBasicMonitoring();
            }

        } catch (\Exception $e) {
            $this->error("Monitoring failed: " . $e->getMessage());
            return 1;
        }

        $this->info('Export job monitoring completed successfully');
        return 0;
    }

    /**
     * Reset stuck export jobs
     */
    protected function resetStuckJobs(): void
    {
        $this->info('Checking for stuck export jobs...');
        
        $results = $this->monitorService->monitorStuckJobs();
        
        if ($results['stuck_jobs_found'] > 0) {
            $this->warn("Found {$results['stuck_jobs_found']} stuck jobs");
            $this->info("Reset {$results['jobs_reset']} jobs");
            $this->info("Sent {$results['notifications_sent']} notifications");
        } else {
            $this->info('No stuck jobs found');
        }
    }

    /**
     * Perform comprehensive health check
     */
    protected function performHealthCheck(): void
    {
        $this->info('Performing system health check...');
        
        $health = $this->monitorService->getSystemHealth();
        
        $this->displayHealthResults($health);
    }

    /**
     * Check and send health alerts
     */
    protected function checkAndSendAlerts(): void
    {
        $this->info('Checking system health for alerts...');
        
        $this->monitorService->checkAndSendHealthAlert();
        
        $this->info('Health alert check completed');
    }

    /**
     * Perform basic monitoring (default action)
     */
    protected function performBasicMonitoring(): void
    {
        $this->info('Performing basic export job monitoring...');
        
        // Reset stuck jobs
        $stuckResults = $this->monitorService->monitorStuckJobs();
        if ($stuckResults['stuck_jobs_found'] > 0) {
            $this->warn("Reset {$stuckResults['jobs_reset']} stuck jobs");
        }
        
        // Quick health overview
        $health = $this->monitorService->getSystemHealth();
        $this->displayQuickHealth($health);
    }

    /**
     * Display comprehensive health results
     */
    protected function displayHealthResults(array $health): void
    {
        // Queue Health
        $this->line("\n<info>Queue Health:</info>");
        $queueHealth = $health['queue_health'];
        $this->line("  Status: " . $this->colorizeStatus($queueHealth['queue_status']));
        $this->line("  Queued Jobs: {$queueHealth['queued_jobs']}");
        $this->line("  Processing Jobs: {$queueHealth['processing_jobs']}");
        $this->line("  Oldest Queued Age: {$queueHealth['oldest_queued_age']} minutes");

        // Job Statistics
        $this->line("\n<info>Job Statistics (24h):</info>");
        $stats = $health['job_statistics'];
        $this->line("  Total Jobs: {$stats['total_jobs']}");
        $this->line("  Completed: {$stats['completed_jobs']}");
        $this->line("  Failed: {$stats['failed_jobs']}");
        $this->line("  Success Rate: " . number_format($stats['success_rate'], 1) . "%");

        // Performance Metrics
        $this->line("\n<info>Performance Metrics:</info>");
        $perf = $health['performance_metrics'];
        $this->line("  Avg Processing Time: " . number_format($perf['avg_processing_time'], 1) . " seconds");
        $this->line("  Max Processing Time: " . number_format($perf['max_processing_time'], 1) . " seconds");

        // System Load
        $this->line("\n<info>System Load:</info>");
        $load = $health['system_load'];
        $this->line("  Jobs per Hour: {$load['jobs_per_hour']}");
        $this->line("  Concurrent Jobs: {$load['concurrent_jobs']}");
        $this->line("  Load Level: " . $this->colorizeLoadLevel($load['load_level']));

        // Storage Usage
        $this->line("\n<info>Storage Usage:</info>");
        $storage = $health['storage_usage'];
        $this->line("  Total Size: " . $this->formatBytes($storage['total_size']));
        $this->line("  File Count: {$storage['file_count']}");
        $this->line("  Avg File Size: " . $this->formatBytes($storage['avg_file_size']));

        // Error Analysis
        if ($health['error_analysis']['total_failures'] > 0) {
            $this->line("\n<comment>Error Analysis:</comment>");
            $errors = $health['error_analysis'];
            $this->line("  Total Failures: {$errors['total_failures']}");
            if ($errors['most_common_error']) {
                $this->line("  Most Common Error: {$errors['most_common_error']}");
            }
        }
    }

    /**
     * Display quick health overview
     */
    protected function displayQuickHealth(array $health): void
    {
        $queueStatus = $health['queue_health']['queue_status'];
        $successRate = $health['job_statistics']['success_rate'];
        $loadLevel = $health['system_load']['load_level'];

        $this->line("Queue: " . $this->colorizeStatus($queueStatus) . 
                   " | Success Rate: " . number_format($successRate, 1) . "%" .
                   " | Load: " . $this->colorizeLoadLevel($loadLevel));
    }

    /**
     * Colorize status for display
     */
    protected function colorizeStatus(string $status): string
    {
        switch($status) {
            case 'healthy':
                return "<info>{$status}</info>";
            case 'busy':
                return "<comment>{$status}</comment>";
            case 'delayed':
            case 'overloaded':
                return "<error>{$status}</error>";
            default:
                return $status;
        }
    }

    /**
     * Colorize load level for display
     */
    protected function colorizeLoadLevel(string $level): string
    {
        switch($level) {
            case 'low':
                return "<info>{$level}</info>";
            case 'medium':
                return "<comment>{$level}</comment>";
            case 'high':
                return "<error>{$level}</error>";
            default:
                return $level;
        }
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}