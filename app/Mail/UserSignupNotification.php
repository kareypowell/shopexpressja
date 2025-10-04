<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use MailerSend\LaravelDriver\MailerSendTrait;

class UserSignupNotification extends Mailable
{
    use Queueable, SerializesModels, MailerSendTrait;

    protected $newUsers;
    protected int $newUserCount = 0;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($newUsers, int $newUserCount = 0)
    {
        $this->newUsers = $newUsers;
        $this->newUserCount = $newUserCount;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Ship Heaven Sharks - New User Signups [weekly]')
            ->from('no-reply@shopexpressja.com')
            ->markdown('emails.users.signups', [
                'newUsers' => $this->newUsers,
                'newUserCount' => $this->newUserCount
            ]);
    }
}
