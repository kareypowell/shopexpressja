<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BackupSystemHealthAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $systemHealth;
    public $warnings;

    /**
     * Create a new message instance.
     */
    public function __construct(array $systemHealth, array $warnings = [])
    {
        $this->systemHealth = $systemHealth;
        $this->warnings = $warnings;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $criticalWarnings = collect($this->warnings)->where('severity', 'critical');
        $subject = $criticalWarnings->isNotEmpty() ? 
            'Critical Backup System Alert' : 
            'Backup System Health Warning';

        return $this->subject($subject)
            ->view('emails.backup-health-alert')
            ->with([
                'systemHealth' => $this->systemHealth,
                'warnings' => $this->warnings,
                'criticalWarnings' => $criticalWarnings,
                'warningCount' => count($this->warnings),
            ]);
    }
}