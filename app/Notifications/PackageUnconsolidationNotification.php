<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use MailerSend\LaravelDriver\MailerSendTrait;
use App\Models\User;
use Illuminate\Support\Collection;

class PackageUnconsolidationNotification extends Notification
{
    use Queueable, MailerSendTrait;

    public $user;
    public $packages;
    public $formerConsolidatedTrackingNumber;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user, Collection $packages, string $formerConsolidatedTrackingNumber)
    {
        $this->user = $user;
        $this->packages = $packages;
        $this->formerConsolidatedTrackingNumber = $formerConsolidatedTrackingNumber;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $packageCount = $this->packages->count();
        
        return (new MailMessage)
                    ->subject('Your Consolidated Package Has Been Separated')
                    ->markdown('emails.packages.package-unconsolidation', [
                        'user' => $this->user,
                        'packages' => $this->packages,
                        'packageCount' => $packageCount,
                        'formerConsolidatedTrackingNumber' => $this->formerConsolidatedTrackingNumber,
                    ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'former_consolidated_tracking_number' => $this->formerConsolidatedTrackingNumber,
            'individual_packages_count' => $this->packages->count(),
            'individual_tracking_numbers' => $this->packages->pluck('tracking_number')->toArray(),
        ];
    }
}