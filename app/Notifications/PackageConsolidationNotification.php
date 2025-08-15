<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use MailerSend\LaravelDriver\MailerSendTrait;
use App\Models\ConsolidatedPackage;

class PackageConsolidationNotification extends Notification
{
    use Queueable, MailerSendTrait;

    public $user;
    public $consolidatedPackage;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user, ConsolidatedPackage $consolidatedPackage)
    {
        $this->user = $user;
        $this->consolidatedPackage = $consolidatedPackage;
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
        $packageCount = $this->consolidatedPackage->packages->count();
        
        return (new MailMessage)
                    ->subject('Your Packages Have Been Consolidated')
                    ->markdown('emails.packages.package-consolidation', [
                        'user' => $this->user,
                        'consolidatedPackage' => $this->consolidatedPackage,
                        'individualPackages' => $this->consolidatedPackage->packages,
                        'packageCount' => $packageCount,
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
            'individual_packages_count' => $this->consolidatedPackage->packages->count(),
            'individual_tracking_numbers' => $this->consolidatedPackage->packages->pluck('tracking_number')->toArray(),
        ];
    }
}