<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use MailerSend\LaravelDriver\MailerSendTrait;

class CustomerWelcomeEmail extends Mailable
{
    use Queueable, SerializesModels, MailerSendTrait;

    public $customer;
    public $temporaryPassword;
    public $accountNumber;
    public $tries = 3;
    public $timeout = 60;

    /**
     * Create a new message instance.
     *
     * @param array $emailData
     * @return void
     */
    public function __construct(array $emailData)
    {
        $this->customer = $emailData['customer'];
        $this->temporaryPassword = $emailData['temporaryPassword'] ?? null;
        $this->accountNumber = $emailData['accountNumber'] ?? null;
        
        // Set queue configuration only if queuing is requested
        if ($emailData['queueEmail'] ?? true) {
            $this->onQueue('emails');
        }
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Welcome to Ship Heaven Sharks Ltd. - Your Account is Ready!')
            ->from('no-reply@shipsharkltd.com', 'Ship Heaven Sharks Ltd.')
            ->markdown('emails.customers.welcome', [
                'customer' => $this->customer,
                'firstName' => $this->customer->first_name,
                'lastName' => $this->customer->last_name,
                'email' => $this->customer->email,
                'temporaryPassword' => $this->temporaryPassword,
                'accountNumber' => $this->accountNumber,
                'loginUrl' => route('login'),
                'shippingInfoUrl' => route('shipping-information'),
                'supportEmail' => 'support@shipsharkltd.com',
            ]);
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        \Log::error('Customer welcome email failed', [
            'customer_id' => $this->customer->id,
            'email' => $this->customer->email,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}