<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use MailerSend\LaravelDriver\MailerSendTrait;
use App\Models\ConsolidatedPackage;
use App\Enums\PackageStatus;

class ConsolidatedPackageStatusNotification extends Notification
{
    use Queueable, MailerSendTrait;

    public $user;
    public $consolidatedPackage;
    public $newStatus;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user, ConsolidatedPackage $consolidatedPackage, PackageStatus $newStatus)
    {
        $this->user = $user;
        $this->consolidatedPackage = $consolidatedPackage;
        $this->newStatus = $newStatus;
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
        $statusTitle = $this->getStatusTitle($this->newStatus);
        
        return (new MailMessage)
                    ->subject("Consolidated Package {$statusTitle}")
                    ->view('emails.packages.consolidated-package-status', [
                        'user' => $this->user,
                        'consolidatedPackage' => $this->consolidatedPackage,
                        'newStatus' => $this->newStatus,
                        'statusTitle' => $statusTitle,
                        'individualPackages' => $this->consolidatedPackage->packages,
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
            'consolidated_package_id' => $this->consolidatedPackage->id,
            'consolidated_tracking_number' => $this->consolidatedPackage->consolidated_tracking_number,
            'new_status' => $this->newStatus->value,
            'individual_packages_count' => $this->consolidatedPackage->packages->count(),
        ];
    }

    /**
     * Get human-readable status title
     */
    private function getStatusTitle(PackageStatus $status): string
    {
        // Use the standard label from the enum to match individual package notifications
        return $status->getLabel();
    }
}