<?php

namespace App\Services;

use App\Models\Backup;
use App\Mail\BackupSuccessNotification;
use App\Mail\BackupFailureNotification;
use App\Mail\BackupSystemHealthAlert;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class BackupNotificationService
{
    protected $monitorService;

    public function __construct(BackupMonitorService $monitorService)
    {
        $this->monitorService = $monitorService;
    }

    /**
     * Send backup success notification
     */
    public function notifyBackupSuccess(Backup $backup): bool
    {
        if (!$this->shouldNotifyOnSuccess()) {
            return false;
        }

        try {
            $systemHealth = $this->monitorService->getSystemHealth();
            
            Mail::to($this->getNotificationEmail())
                ->send(new BackupSuccessNotification($backup, $systemHealth));

            Log::info('Backup success notification sent', [
                'backup_id' => $backup->id,
                'backup_name' => $backup->name,
                'recipient' => $this->getNotificationEmail(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send backup success notification', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send backup failure notification
     */
    public function notifyBackupFailure(Backup $backup, string $errorMessage = null): bool
    {
        if (!$this->shouldNotifyOnFailure()) {
            return false;
        }

        try {
            $systemHealth = $this->monitorService->getSystemHealth();
            
            Mail::to($this->getNotificationEmail())
                ->send(new BackupFailureNotification($backup, $errorMessage, $systemHealth));

            Log::warning('Backup failure notification sent', [
                'backup_id' => $backup->id,
                'backup_name' => $backup->name,
                'error_message' => $errorMessage,
                'recipient' => $this->getNotificationEmail(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send backup failure notification', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send system health alert
     */
    public function notifySystemHealthAlert(): bool
    {
        try {
            $systemHealth = $this->monitorService->getSystemHealth();
            $warnings = $systemHealth['warnings'] ?? [];

            if (empty($warnings)) {
                return false;
            }

            Mail::to($this->getNotificationEmail())
                ->send(new BackupSystemHealthAlert($systemHealth, $warnings));

            Log::warning('Backup system health alert sent', [
                'warning_count' => count($warnings),
                'overall_status' => $systemHealth['overall_status'],
                'recipient' => $this->getNotificationEmail(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send backup system health alert', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check and send health alerts if needed
     */
    public function checkAndSendHealthAlerts(): bool
    {
        if ($this->monitorService->shouldSendAlert()) {
            return $this->notifySystemHealthAlert();
        }

        return false;
    }

    /**
     * Send daily health summary
     */
    public function sendDailyHealthSummary(): bool
    {
        try {
            $systemHealth = $this->monitorService->getSystemHealth();
            
            // Only send if there are warnings or if explicitly configured
            if (empty($systemHealth['warnings']) && !$this->shouldSendDailySummary()) {
                return false;
            }

            Mail::to($this->getNotificationEmail())
                ->send(new BackupSystemHealthAlert($systemHealth, $systemHealth['warnings']));

            Log::info('Daily backup health summary sent', [
                'overall_status' => $systemHealth['overall_status'],
                'warning_count' => count($systemHealth['warnings']),
                'recipient' => $this->getNotificationEmail(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send daily backup health summary', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get notification email address
     */
    protected function getNotificationEmail(): string
    {
        return config('backup.notifications.email') 
            ?: config('mail.from.address') 
            ?: 'admin@localhost';
    }

    /**
     * Check if should notify on success
     */
    protected function shouldNotifyOnSuccess(): bool
    {
        return config('backup.notifications.notify_on_success', false);
    }

    /**
     * Check if should notify on failure
     */
    protected function shouldNotifyOnFailure(): bool
    {
        return config('backup.notifications.notify_on_failure', true);
    }

    /**
     * Check if should send daily summary
     */
    protected function shouldSendDailySummary(): bool
    {
        return config('backup.notifications.daily_summary', false);
    }

    /**
     * Get notification preferences
     */
    public function getNotificationPreferences(): array
    {
        return [
            'email' => $this->getNotificationEmail(),
            'notify_on_success' => $this->shouldNotifyOnSuccess(),
            'notify_on_failure' => $this->shouldNotifyOnFailure(),
            'daily_summary' => $this->shouldSendDailySummary(),
        ];
    }

    /**
     * Test notification system
     */
    public function testNotifications(): array
    {
        $results = [];

        // Test email configuration
        try {
            $email = $this->getNotificationEmail();
            $results['email_config'] = [
                'status' => 'success',
                'email' => $email,
                'message' => 'Email configuration is valid',
            ];
        } catch (\Exception $e) {
            $results['email_config'] = [
                'status' => 'error',
                'message' => 'Email configuration error: ' . $e->getMessage(),
            ];
        }

        // Test system health retrieval
        try {
            $health = $this->monitorService->getSystemHealth();
            $results['health_check'] = [
                'status' => 'success',
                'overall_status' => $health['overall_status'],
                'message' => 'System health check successful',
            ];
        } catch (\Exception $e) {
            $results['health_check'] = [
                'status' => 'error',
                'message' => 'Health check error: ' . $e->getMessage(),
            ];
        }

        return $results;
    }
}