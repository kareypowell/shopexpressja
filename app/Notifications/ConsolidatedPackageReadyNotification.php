<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use MailerSend\LaravelDriver\MailerSendTrait;
use App\Models\ConsolidatedPackage;
use App\Models\User;

class ConsolidatedPackageReadyNotification extends Notification
{
    use Queueable, MailerSendTrait;

    public $user;
    public $consolidatedPackage;
    public $showCosts;
    public $specialInstructions;

    /**
     * Create a new notification instance.
     *
     * @param User $user
     * @param ConsolidatedPackage $consolidatedPackage
     * @param bool $showCosts
     * @param string|null $specialInstructions
     * @return void
     */
    public function __construct(User $user, ConsolidatedPackage $consolidatedPackage, bool $showCosts = true, ?string $specialInstructions = null)
    {
        $this->user = $user;
        $this->consolidatedPackage = $consolidatedPackage;
        $this->showCosts = $showCosts;
        $this->specialInstructions = $specialInstructions;
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
        return (new MailMessage)
                    ->subject('Consolidated Package Ready for Pickup - ' . $this->consolidatedPackage->consolidated_tracking_number)
                    ->view('emails.packages.consolidated-package-ready', [
                        'user' => $this->user,
                        'consolidatedPackage' => $this->consolidatedPackage,
                        'individualPackages' => $this->consolidatedPackage->packages,
                        'showCosts' => $this->showCosts,
                        'specialInstructions' => $this->specialInstructions,
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
            'show_costs' => $this->showCosts,
            'special_instructions' => $this->specialInstructions,
        ];
    }
}