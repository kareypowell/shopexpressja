<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExportSystemHealthNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $healthData;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $healthData)
    {
        $this->healthData = $healthData;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Report Export System Health Alert')
            ->error()
            ->greeting('System Administrator Alert')
            ->line('The report export system is experiencing performance issues that require attention.');

        // Add specific health issues
        $issues = $this->identifyHealthIssues();
        foreach ($issues as $issue) {
            $message->line("• {$issue}");
        }

        $message->line('Please review the system status and take appropriate action.')
            ->action('View System Dashboard', route('admin.reports.health'))
            ->line('This alert will not be sent again for 1 hour to prevent spam.');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'export_system_health_alert',
            'health_data' => $this->healthData,
            'issues' => $this->identifyHealthIssues(),
            'severity' => $this->determineSeverity(),
            'timestamp' => now(),
            'message' => 'Report export system health alert - immediate attention required.'
        ];
    }

    /**
     * Identify specific health issues
     */
    protected function identifyHealthIssues(): array
    {
        $issues = [];

        // Queue health issues
        if ($this->healthData['queue_health']['queue_status'] === 'overloaded') {
            $queuedCount = $this->healthData['queue_health']['queued_jobs'];
            $issues[] = "Export queue is overloaded with {$queuedCount} pending jobs";
        }

        if ($this->healthData['queue_health']['queue_status'] === 'delayed') {
            $oldestAge = $this->healthData['queue_health']['oldest_queued_age'];
            $issues[] = "Oldest queued job has been waiting {$oldestAge} minutes";
        }

        // Success rate issues
        if ($this->healthData['job_statistics']['success_rate'] < 80) {
            $rate = number_format($this->healthData['job_statistics']['success_rate'], 1);
            $issues[] = "Export success rate has dropped to {$rate}%";
        }

        // System load issues
        if ($this->healthData['system_load']['load_level'] === 'high') {
            $jobsPerHour = $this->healthData['system_load']['jobs_per_hour'];
            $concurrent = $this->healthData['system_load']['concurrent_jobs'];
            $issues[] = "High system load: {$jobsPerHour} jobs/hour, {$concurrent} concurrent jobs";
        }

        // Storage issues
        $storageSize = $this->healthData['storage_usage']['total_size'];
        if ($storageSize > 5 * 1024 * 1024 * 1024) { // 5GB
            $sizeFormatted = $this->formatBytes($storageSize);
            $issues[] = "Export storage usage is high: {$sizeFormatted}";
        }

        // Error pattern issues
        if (isset($this->healthData['error_analysis']['total_failures']) && 
            $this->healthData['error_analysis']['total_failures'] > 10) {
            $failures = $this->healthData['error_analysis']['total_failures'];
            $commonError = $this->healthData['error_analysis']['most_common_error'] ?? 'unknown';
            $issues[] = "{$failures} export failures in last 24 hours (most common: {$commonError})";
        }

        return $issues;
    }

    /**
     * Determine alert severity
     */
    protected function determineSeverity(): string
    {
        $criticalConditions = [
            $this->healthData['queue_health']['queue_status'] === 'overloaded',
            $this->healthData['job_statistics']['success_rate'] < 50,
            $this->healthData['system_load']['concurrent_jobs'] > 20
        ];

        if (array_filter($criticalConditions)) {
            return 'critical';
        }

        $warningConditions = [
            $this->healthData['queue_health']['queue_status'] === 'delayed',
            $this->healthData['job_statistics']['success_rate'] < 80,
            $this->healthData['system_load']['load_level'] === 'high'
        ];

        if (array_filter($warningConditions)) {
            return 'warning';
        }

        return 'info';
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