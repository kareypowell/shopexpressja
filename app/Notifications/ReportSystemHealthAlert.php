<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportSystemHealthAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $healthStatus;
    protected array $alerts;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $healthStatus, array $alerts)
    {
        $this->healthStatus = $healthStatus;
        $this->alerts = $alerts;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $overallStatus = $this->healthStatus['overall_status'];
        $subject = 'Report System Health Alert - ' . strtoupper($overallStatus);
        
        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Report System Health Alert')
            ->line("The report system status is currently: **{$overallStatus}**")
            ->line("Timestamp: {$this->healthStatus['timestamp']}");

        // Add alert details
        if (!empty($this->alerts)) {
            $message->line('**Alerts:**');
            foreach ($this->alerts as $alert) {
                $message->line("- [{$alert['type']}] {$alert['message']}");
            }
        }

        // Add health check details
        $message->line('**Health Check Details:**');
        foreach ($this->healthStatus['checks'] as $checkName => $checkResult) {
            $status = strtoupper($checkResult['status']);
            $checkDisplayName = ucfirst(str_replace('_', ' ', $checkName));
            $message->line("- {$checkDisplayName}: {$status}");
            
            if (isset($checkResult['error'])) {
                $message->line("  Error: {$checkResult['error']}");
            }
        }

        $message->line('Please check the report system and take appropriate action if necessary.')
                ->action('View System Dashboard', url('/admin/reports'))
                ->line('This is an automated alert from the ShipSharkLtd report monitoring system.');

        // Set priority based on severity
        if ($overallStatus === 'unhealthy') {
            $message->priority('high');
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'overall_status' => $this->healthStatus['overall_status'],
            'timestamp' => $this->healthStatus['timestamp'],
            'alerts_count' => count($this->alerts),
            'checks' => $this->healthStatus['checks']
        ];
    }
}