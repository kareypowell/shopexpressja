<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use MailerSend\LaravelDriver\MailerSendTrait;

class WelcomeUser extends Mailable
{
    use Queueable, SerializesModels, MailerSendTrait;

    protected string $firstName;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Ship Heaven Sharks Ltd. - Welcome')
            ->from('no-reply@shopexpressja.com')
            ->markdown('emails.users.welcome', [
                'firstName' => $this->firstName
            ]);
    }
}
