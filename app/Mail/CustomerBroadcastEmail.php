<?php

namespace App\Mail;

use App\Models\BroadcastMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class CustomerBroadcastEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $broadcastMessage;
    public $customer;
    public $personalizedSubject;
    public $personalizedContent;
    public $tries = 3;
    public $timeout = 60;

    /**
     * Create a new message instance.
     */
    public function __construct(BroadcastMessage $broadcastMessage, User $customer, $personalizedSubject = null, $personalizedContent = null)
    {
        $this->broadcastMessage = $broadcastMessage;
        $this->customer = $customer;
        $this->personalizedSubject = $personalizedSubject;
        $this->personalizedContent = $personalizedContent;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        // Use personalized content if available, otherwise fall back to original
        $subject = $this->personalizedSubject ?? $this->broadcastMessage->subject;
        $content = $this->personalizedContent ?? $this->broadcastMessage->content;
        
        return $this->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'))
                    ->replyTo(config('mail.from.address'), config('mail.from.name'))
                    ->view('emails.customer-broadcast')
                    ->text('emails.customer-broadcast-text')
                    ->with([
                        'content' => $content,
                        'customer' => $this->customer,
                        'broadcastMessage' => $this->broadcastMessage,
                        'companyName' => config('app.name', 'ShipShark Ltd'),
                        'supportEmail' => config('mail.admin.email', config('mail.from.address')),
                        'unsubscribeUrl' => $this->generateUnsubscribeUrl()
                    ]);
    }

    /**
     * Generate unsubscribe URL for the customer
     */
    private function generateUnsubscribeUrl(): string
    {
        // In a real implementation, this would generate a signed URL
        // For now, return a placeholder URL
        return url('/unsubscribe?token=placeholder');
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}