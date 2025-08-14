<?php

namespace App\Mail;

use App\Models\CustomerTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BalanceAddedNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $transaction;
    public $customer;
    public $isAccountBalance;
    public $isCreditBalance;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(CustomerTransaction $transaction)
    {
        $this->transaction = $transaction;
        $this->customer = $transaction->user;
        
        // Determine the type of balance addition based on metadata
        $metadata = $this->transaction->metadata ?? [];
        $adjustmentType = $metadata['adjustment_type'] ?? '';
        
        $this->isAccountBalance = $adjustmentType === 'account_balance_addition';
        $this->isCreditBalance = $adjustmentType === 'credit_balance_addition';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $balanceType = $this->isAccountBalance ? 'Account Balance' : 'Credit Balance';
        
        return $this->subject("Your {$balanceType} Has Been Updated")
                    ->view('emails.balance-added-notification')
                    ->with([
                        'customerName' => $this->customer->first_name,
                        'amount' => number_format($this->transaction->amount, 2),
                        'balanceType' => $balanceType,
                        'description' => $this->transaction->description,
                        'newBalance' => number_format($this->transaction->balance_after, 2),
                        'transactionDate' => $this->transaction->created_at->format('F j, Y \a\t g:i A'),
                        'isAccountBalance' => $this->isAccountBalance,
                        'isCreditBalance' => $this->isCreditBalance,
                        'totalAvailableBalance' => number_format($this->customer->total_available_balance, 2),
                    ]);
    }
}