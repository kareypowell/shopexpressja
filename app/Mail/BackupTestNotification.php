<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BackupTestNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $testMessage;
    public $timestamp;

    /**
     * Create a new message instance.
     */
    public function __construct()
    {
        $this->testMessage = 'This is a test notification to verify your backup email configuration is working correctly.';
        $this->timestamp = now();
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Backup System Test Notification')
            ->view('emails.backup-test')
            ->with([
                'testMessage' => $this->testMessage,
                'timestamp' => $this->timestamp,
                'systemName' => config('app.name'),
            ]);
    }
}