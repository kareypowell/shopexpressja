<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ReportMonitoringService;
use App\Notifications\ReportSystemHealthAlert;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class MonitorReportSystemHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(ReportMonitoringService $monitoringService): void
    {
        try {
            Log::channel('reports')->info('Starting automated report system health check');
            
            // Perform health check
            $healthStatus = $monitoringService->performHealthCheck();
            
            // Check if alerts should be triggered
            $alerts = $monitoringService->shouldTriggerAlert();
            
            if (!empty($alerts)) {
                $this->sendHealthAlerts($healthStatus, $alerts);
            }
            
            // Log completion
            Log::channel('reports')->info('Automated health check completed', [
                'overall_status' => $healthStatus['overall_status'],
                'alerts_triggered' => count($alerts)
            ]);
            
        } catch (\Exception $e) {
            Log::channel('reports')->error('Health monitoring job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw to trigger job retry
            throw $e;
        }
    }

    /**
     * Send health alerts to administrators
     */
    protected function sendHealthAlerts(array $healthStatus, array $alerts): void
    {
        try {
            // Get admin users who should receive alerts
            $adminUsers = User::whereHas('role', function($query) {
                $query->whereIn('name', ['admin', 'superadmin']);
            })->get();
            
            if ($adminUsers->isEmpty()) {
                Log::channel('reports')->warning('No admin users found to send health alerts');
                return;
            }
            
            // Send notification to all admin users
            Notification::send($adminUsers, new ReportSystemHealthAlert($healthStatus, $alerts));
            
            Log::channel('reports')->info('Health alerts sent to admin users', [
                'recipient_count' => $adminUsers->count(),
                'alert_count' => count($alerts)
            ]);
            
        } catch (\Exception $e) {
            Log::channel('reports')->error('Failed to send health alerts', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('reports')->critical('Report system health monitoring job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts()
        ]);
    }
}