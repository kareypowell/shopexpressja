<?php

namespace App\Notifications;

use App\Models\ReportExportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportExportCompletedNotification extends Notification implements ShouldQueue
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
        $downloadUrl = route('reports.download', [
            'job' => $this->exportJob->id,
            'token' => $this->generateDownloadToken()
        ]);

        return (new MailMessage)
            ->subject('Report Export Completed')
            ->greeting('Hello!')
            ->line('Your report export has been completed successfully.')
            ->line("Report Type: {$this->getReportTypeLabel()}")
            ->line("Export Format: {$this->exportJob->export_format}")
            ->line("Generated: {$this->exportJob->completed_at->format('M j, Y g:i A')}")
            ->action('Download Report', $downloadUrl)
            ->line('This download link will expire in 24 hours.')
            ->line('Thank you for using our reporting system!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'report_export_completed',
            'export_job_id' => $this->exportJob->id,
            'report_type' => $this->exportJob->report_type,
            'export_format' => $this->exportJob->export_format,
            'completed_at' => $this->exportJob->completed_at,
            'file_size' => $this->getFileSize(),
            'message' => "Your {$this->getReportTypeLabel()} export is ready for download."
        ];
    }

    /**
     * Generate secure download token
     */
    protected function generateDownloadToken(): string
    {
        return hash_hmac('sha256', $this->exportJob->id . $this->exportJob->file_path, config('app.key'));
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
     * Get file size in human readable format
     */
    protected function getFileSize(): string
    {
        if (!$this->exportJob->file_path) {
            return 'Unknown';
        }

        try {
            $bytes = \Storage::disk('local')->size($this->exportJob->file_path);
            $units = ['B', 'KB', 'MB', 'GB'];
            
            for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
                $bytes /= 1024;
            }
            
            return round($bytes, 2) . ' ' . $units[$i];
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }
}