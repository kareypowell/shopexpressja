<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use MailerSend\LaravelDriver\MailerSendTrait;

class MissingPreAlertNotification extends Notification
{
    use Queueable, MailerSendTrait;

    public $user;

    public $tracking_number;

    public $description;

    public $preAlert;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user, $tracking_number, $description, $preAlert)
    {
        $this->user = $user;
        $this->tracking_number = $tracking_number;
        $this->description = $description;
        $this->preAlert = $preAlert;
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
        $url = url('/pre-alerts/' . $this->preAlert->id . '/view');

        return (new MailMessage)
                    ->subject('Missing Pre-Alert Notification')
                    ->markdown('emails.packages.missing-pre-alerts', [
                        'user' => $this->user,
                        'trackingNumber' => $this->tracking_number,
                        'description' => $this->description,
                        'url' => $url,
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
            //
        ];
    }
}
