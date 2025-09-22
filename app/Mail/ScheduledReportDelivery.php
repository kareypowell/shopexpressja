<?php

namespace App\Mail;

use App\Models\ReportTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ScheduledReportDelivery extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public ReportTemplate $template;
    public string $exportJobId;

    /**
     * Create a new message instance.
     */
    public function __construct(ReportTemplate $template, string $exportJobId)
    {
        $this->template = $template;
        $this->exportJobId = $exportJobId;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $reportTypeName = ucfirst($this->template->type);
        
        return $this->subject("Scheduled {$reportTypeName} Report - {$this->template->name}")
                    ->markdown('emails.reports.scheduled-delivery')
                    ->with([
                        'template' => $this->template,
                        'exportJobId' => $this->exportJobId,
                        'reportTypeName' => $reportTypeName,
                        'generatedAt' => now()->format('M j, Y g:i A'),
                        'downloadUrl' => route('admin.reports.exports.download', $this->exportJobId)
                    ]);
    }
}