<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecurityAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @var array */
    protected $alertData;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $alertData)
    {
        $this->alertData = $alertData;
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
        $riskLevel = $this->alertData['risk_level'];
        $riskScore = $this->alertData['risk_score'];
        
        $subject = $this->getSubjectByRiskLevel($riskLevel);
        $priority = $this->getPriorityByRiskLevel($riskLevel);

        $message = (new MailMessage)
            ->subject($subject)
            ->priority($priority)
            ->greeting('Security Alert')
            ->line("A {$riskLevel} risk security event has been detected.")
            ->line("Risk Score: {$riskScore}/100");

        // Add alert details
        if (!empty($this->alertData['alerts'])) {
            $message->line('Alert Details:');
            foreach ($this->alertData['alerts'] as $alert) {
                $message->line("• {$alert}");
            }
        }

        // Add context information
        if (isset($this->alertData['user_id'])) {
            $message->line("User ID: {$this->alertData['user_id']}");
        }

        if (isset($this->alertData['ip_address'])) {
            $message->line("IP Address: {$this->alertData['ip_address']}");
        }

        $message->line('Please review the audit logs for more details.')
            ->action('View Audit Logs', url('/admin/audit-logs'))
            ->line('This is an automated security alert from ShipSharkLtd.');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'security_alert',
            'risk_level' => $this->alertData['risk_level'],
            'risk_score' => $this->alertData['risk_score'],
            'alerts' => $this->alertData['alerts'],
            'user_id' => $this->alertData['user_id'] ?? null,
            'ip_address' => $this->alertData['ip_address'] ?? null,
            'analysis_time' => $this->alertData['analysis_time'] ?? now(),
        ];
    }

    /**
     * Get email subject based on risk level
     */
    private function getSubjectByRiskLevel(string $riskLevel): string
    {
        switch ($riskLevel) {
            case 'critical':
                return '[CRITICAL] Security Alert - Immediate Action Required';
            case 'high':
                return '[HIGH] Security Alert - Review Required';
            case 'medium':
                return '[MEDIUM] Security Alert - Monitoring Required';
            case 'low':
                return '[LOW] Security Alert - Information Only';
            default:
                return 'Security Alert - ShipSharkLtd';
        }
    }

    /**
     * Get email priority based on risk level
     */
    private function getPriorityByRiskLevel(string $riskLevel): int
    {
        switch ($riskLevel) {
            case 'critical':
                return 1; // High priority
            case 'high':
                return 2; // High priority
            case 'medium':
                return 3; // Normal priority
            case 'low':
                return 4; // Low priority
            default:
                return 3; // Normal priority
        }
    }
}