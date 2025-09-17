<?php

namespace App\Mail;

use App\Models\Backup;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BackupSuccessNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $backup;
    public $systemHealth;

    /**
     * Create a new message instance.
     */
    public function __construct(Backup $backup, array $systemHealth = [])
    {
        $this->backup = $backup;
        $this->systemHealth = $systemHealth;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Backup Completed Successfully - ' . $this->backup->name)
            ->view('emails.backup-success')
            ->with([
                'backup' => $this->backup,
                'systemHealth' => $this->systemHealth,
                'fileSizeMB' => round($this->backup->file_size / (1024 * 1024), 2),
                'duration' => $this->backup->completed_at ? 
                    $this->backup->created_at->diffForHumans($this->backup->completed_at, true) : 'Unknown',
            ]);
    }
}