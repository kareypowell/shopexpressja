<?php

namespace App\Notifications;

use App\Models\Manifest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use MailerSend\LaravelDriver\MailerSendTrait;

class ManifestUnlockedNotification extends Notification implements ShouldQueue
{
    use Queueable, MailerSendTrait;

    public Manifest $manifest;
    public User $unlockedBy;
    public string $reason;
    public \Carbon\Carbon $unlockedAt;

    /**
     * Create a new notification instance.
     */
    public function __construct(Manifest $manifest, User $unlockedBy, string $reason, \Carbon\Carbon $unlockedAt)
    {
        $this->manifest = $manifest;
        $this->unlockedBy = $unlockedBy;
        $this->reason = $reason;
        $this->unlockedAt = $unlockedAt;
        
        // Set queue configuration
        $this->onQueue('notifications');
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
            ->subject('Manifest Unlocked - ' . $this->manifest->name)
            ->view('emails.admin.manifest-unlocked', [
                'manifest' => $this->manifest,
                'unlockedBy' => $this->unlockedBy,
                'reason' => $this->reason,
                'unlockedAt' => $this->unlockedAt,
                'recipient' => $notifiable,
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'manifest_unlocked',
            'manifest_id' => $this->manifest->id,
            'manifest_name' => $this->manifest->name,
            'manifest_number' => $this->manifest->manifest_number,
            'unlocked_by_id' => $this->unlockedBy->id,
            'unlocked_by_name' => $this->unlockedBy->full_name,
            'unlocked_by_email' => $this->unlockedBy->email,
            'reason' => $this->reason,
            'unlocked_at' => $this->unlockedAt->toISOString(),
            'message' => "Manifest '{$this->manifest->name}' was unlocked by {$this->unlockedBy->full_name}",
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Manifest unlock notification failed', [
            'manifest_id' => $this->manifest->id,
            'manifest_name' => $this->manifest->name,
            'unlocked_by_id' => $this->unlockedBy->id,
            'unlocked_by_name' => $this->unlockedBy->full_name,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}