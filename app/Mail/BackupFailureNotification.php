<?php

namespace App\Mail;

use App\Models\Backup;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BackupFailureNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $backup;
    public $errorMessage;
    public $systemHealth;

    /**
     * Create a new message instance.
     */
    public function __construct(Backup $backup, string $errorMessage = null, array $systemHealth = [])
    {
        $this->backup = $backup;
        $this->errorMessage = $errorMessage ?? ($backup->metadata['error_message'] ?? 'Unknown error');
        $this->systemHealth = $systemHealth;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Backup Failed - ' . $this->backup->name)
            ->view('emails.backup-failure')
            ->with([
                'backup' => $this->backup,
                'error' => $this->errorMessage,
                'errorMessage' => $this->errorMessage,
                'systemHealth' => $this->systemHealth,
                'failedAt' => $this->backup->updated_at,
            ]);
    }
}