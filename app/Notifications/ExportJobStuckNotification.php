<?php

namespace App\Notifications;

use App\Models\ReportExportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExportJobStuckNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $exportJob;

    /**
     * Create a new notification instance.
     */
    public function __construct(ReportExportJob $exportJob)
    {
        $this->exportJob = $exportJob;
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
            ->subject('Report Export Processing Issue')
            ->error()
            ->greeting('Hello!')
            ->line('We encountered an issue with your report export that was taking longer than expected to process.')
            ->line("Report Type: {$this->getReportTypeLabel()}")
            ->line("Export Format: {$this->exportJob->export_format}")
            ->line("Started: {$this->exportJob->started_at->format('M j, Y g:i A')}")
            ->line('The export has been automatically cancelled and marked as failed.')
            ->line('You can try generating the report again, or contact support if the problem persists.')
            ->action('Try Again', route('reports.dashboard'))
            ->line('We apologize for the inconvenience and are working to improve our export processing.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'export_job_stuck',
            'export_job_id' => $this->exportJob->id,
            'report_type' => $this->exportJob->report_type,
            'export_format' => $this->exportJob->export_format,
            'started_at' => $this->exportJob->started_at,
            'processing_time' => $this->exportJob->started_at->diffInMinutes(now()),
            'message' => "Your {$this->getReportTypeLabel()} export was cancelled due to processing timeout."
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
}