<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReportMonitoringService;
use App\Services\ReportErrorHandlingService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MonitorReportSystemHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:monitor-health 
                            {--alert : Send alerts if issues are detected}
                            {--email= : Email address to send alerts to}
                            {--verbose : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor the health of the report system and optionally send alerts';

    protected ReportMonitoringService $monitoringService;
    protected ReportErrorHandlingService $errorHandlingService;

    public function __construct(
        ReportMonitoringService $monitoringService,
        ReportErrorHandlingService $errorHandlingService
    ) {
        parent::__construct();
        $this->monitoringService = $monitoringService;
        $this->errorHandlingService = $errorHandlingService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting Report System Health Check...');
        
        // Perform health check
        $healthStatus = $this->monitoringService->performHealthCheck();
        
        // Display results
        $this->displayHealthStatus($healthStatus);
        
        // Check for alerts
        if ($this->option('alert')) {
            $this->checkAndSendAlerts($healthStatus);
        }
        
        // Show performance statistics if verbose
        if ($this->option('verbose')) {
            $this->displayPerformanceStatistics();
            $this->displayErrorStatistics();
        }
        
        return $healthStatus['overall_status'] === 'healthy' ? 0 : 1;
    }

    /**
     * Display health status information
     */
    protected function displayHealthStatus(array $healthStatus): void
    {
        $overallStatus = $healthStatus['overall_status'];
        
        // Display overall status with color coding
        switch ($overallStatus) {
            case 'healthy':
                $this->info("✅ Overall Status: HEALTHY");
                break;
            case 'degraded':
                $this->warn("⚠️  Overall Status: DEGRADED");
                break;
            case 'unhealthy':
                $this->error("❌ Overall Status: UNHEALTHY");
                break;
            default:
                $this->line("❓ Overall Status: UNKNOWN");
        }
        
        $this->line("Last Check: {$healthStatus['timestamp']}");
        $this->newLine();
        
        // Display individual check results
        $this->line('Individual Health Checks:');
        $this->line('========================');
        
        foreach ($healthStatus['checks'] as $checkName => $checkResult) {
            $status = $checkResult['status'];
            $icon = $this->getStatusIcon($status);
            
            $this->line("{$icon} " . ucfirst(str_replace('_', ' ', $checkName)) . ": " . strtoupper($status));
            
            // Show additional details if available
            if (isset($checkResult['response_time_ms'])) {
                $this->line("   Response Time: {$checkResult['response_time_ms']}ms");
            }
            
            if (isset($checkResult['error'])) {
                $this->line("   Error: {$checkResult['error']}");
            }
            
            if (isset($checkResult['total_errors_last_hour'])) {
                $this->line("   Errors (last hour): {$checkResult['total_errors_last_hour']}");
            }
            
            if (isset($checkResult['failed_jobs_24h'])) {
                $this->line("   Failed Jobs (24h): {$checkResult['failed_jobs_24h']}");
            }
        }
        
        $this->newLine();
    }

    /**
     * Display performance statistics
     */
    protected function displayPerformanceStatistics(): void
    {
        $this->line('Performance Statistics (Last 24 Hours):');
        $this->line('=======================================');
        
        $stats = $this->monitoringService->getPerformanceStatistics(24);
        
        $this->line("Total Requests: {$stats['total_requests']}");
        $this->line("Average Response Time: {$stats['avg_response_time']}s");
        $this->line("Max Response Time: {$stats['max_response_time']}s");
        $this->line("Min Response Time: {$stats['min_response_time']}s");
        
        if (!empty($stats['by_report_type'])) {
            $this->newLine();
            $this->line('By Report Type:');
            
            foreach ($stats['by_report_type'] as $type => $typeStats) {
                $this->line("  {$type}:");
                $this->line("    Requests: {$typeStats['count']}");
                $this->line("    Avg Time: " . round($typeStats['avg_response_time'], 2) . "s");
                $this->line("    Max Time: " . round($typeStats['max_response_time'], 2) . "s");
            }
        }
        
        $this->newLine();
    }

    /**
     * Display error statistics
     */
    protected function displayErrorStatistics(): void
    {
        $this->line('Error Statistics (Last 24 Hours):');
        $this->line('=================================');
        
        $reportTypes = ['sales', 'manifest', 'customer', 'financial'];
        
        foreach ($reportTypes as $type) {
            $stats = $this->errorHandlingService->getErrorStatistics($type, 24);
            $this->line("{$type}: {$stats['total_errors']} errors (Rate: " . round($stats['error_rate'], 2) . "/hour)");
        }
        
        $this->newLine();
    }

    /**
     * Check and send alerts if necessary
     */
    protected function checkAndSendAlerts(array $healthStatus): void
    {
        $alerts = $this->monitoringService->shouldTriggerAlert();
        
        if (empty($alerts)) {
            $this->info('No alerts triggered.');
            return;
        }
        
        $this->warn('Alerts detected:');
        
        foreach ($alerts as $alert) {
            $this->line("- [{$alert['type']}] {$alert['message']}");
            
            // Log the alert
            Log::channel('reports')->warning('Report System Alert', $alert);
        }
        
        // Send email alert if email is provided
        $email = $this->option('email');
        if ($email) {
            $this->sendEmailAlert($email, $alerts, $healthStatus);
        }
    }

    /**
     * Send email alert
     */
    protected function sendEmailAlert(string $email, array $alerts, array $healthStatus): void
    {
        try {
            // In a real implementation, you would create a proper Mailable class
            $subject = 'Report System Health Alert - ' . strtoupper($healthStatus['overall_status']);
            $body = $this->formatEmailBody($alerts, $healthStatus);
            
            // For now, just log that we would send an email
            Log::channel('reports')->info('Email alert would be sent', [
                'to' => $email,
                'subject' => $subject,
                'alerts_count' => count($alerts)
            ]);
            
            $this->info("Email alert logged for: {$email}");
            
        } catch (\Exception $e) {
            $this->error("Failed to send email alert: {$e->getMessage()}");
        }
    }

    /**
     * Format email body for alerts
     */
    protected function formatEmailBody(array $alerts, array $healthStatus): string
    {
        $body = "Report System Health Alert\n";
        $body .= "==========================\n\n";
        $body .= "Overall Status: " . strtoupper($healthStatus['overall_status']) . "\n";
        $body .= "Timestamp: {$healthStatus['timestamp']}\n\n";
        
        $body .= "Alerts:\n";
        foreach ($alerts as $alert) {
            $body .= "- [{$alert['type']}] {$alert['message']}\n";
        }
        
        $body .= "\nPlease check the report system and take appropriate action.\n";
        
        return $body;
    }

    /**
     * Get status icon for display
     */
    protected function getStatusIcon(string $status): string
    {
        switch ($status) {
            case 'healthy':
                return '✅';
            case 'degraded':
                return '⚠️';
            case 'unhealthy':
                return '❌';
            default:
                return '❓';
        }
    }
}