<?php

namespace App\Notifications;

use App\Models\ReportExportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportExportFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $exportJob;
    protected $errorMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct(ReportExportJob $exportJob, string $errorMessage)
    {
        $this->exportJob = $exportJob;
        $this->errorMessage = $errorMessage;
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
        return (new MailMessage)
            ->subject('Report Export Failed')
            ->error()
            ->greeting('Hello!')
            ->line('Unfortunately, your report export has failed.')
            ->line("Report Type: {$this->getReportTypeLabel()}")
            ->line("Export Format: {$this->exportJob->export_format}")
            ->line("Failed: {$this->exportJob->completed_at->format('M j, Y g:i A')}")
            ->line("Error: {$this->getSafeErrorMessage()}")
            ->line('You can try generating the report again, or contact support if the problem persists.')
            ->action('Try Again', route('reports.dashboard'))
            ->line('We apologize for the inconvenience.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'report_export_failed',
            'export_job_id' => $this->exportJob->id,
            'report_type' => $this->exportJob->report_type,
            'export_format' => $this->exportJob->export_format,
            'failed_at' => $this->exportJob->completed_at,
            'error_message' => $this->getSafeErrorMessage(),
            'message' => "Your {$this->getReportTypeLabel()} export failed to generate."
        ];
    }

    /**
     * Get human-readable report type label
     */
    protected function getReportTypeLabel(): string
    {
        switch($this->exportJob->report_type) {
            case 'sales_collections':
                return 'Sales & Collections Report';
            case 'manifest_performance':
                return 'Manifest Performance Report';
            case 'customer_analytics':
                return 'Customer Analytics Report';
            case 'financial_summary':
                return 'Financial Summary Report';
            default:
                return 'Business Report';
        }
    }

    /**
     * Get safe error message for user display
     */
    protected function getSafeErrorMessage(): string
    {
        // Sanitize error message to avoid exposing sensitive information
        $safeMessage = $this->errorMessage;
        
        // Remove file paths
        $safeMessage = preg_replace('/\/[^\s]+/', '[file path]', $safeMessage);
        
        // Remove SQL details
        $safeMessage = preg_replace('/SQLSTATE\[[^\]]+\]/', '[database error]', $safeMessage);
        
        // Limit length
        if (strlen($safeMessage) > 200) {
            $safeMessage = substr($safeMessage, 0, 197) . '...';
        }
        
        return $safeMessage ?: 'An unexpected error occurred during export processing.';
    }
}