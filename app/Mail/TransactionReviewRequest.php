<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\CustomerTransaction;
use App\Models\User;
use App\Models\Package;
use App\Models\PackageDistribution;

class TransactionReviewRequest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $transaction;
    public $customer;
    public $package;
    public $manifest;
    public $distribution;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(CustomerTransaction $transaction)
    {
        $this->transaction = $transaction;
        $this->customer = $transaction->user;
        
        // Get related package and manifest information
        if ($transaction->reference_type === 'package_distribution' && $transaction->reference_id) {
            $this->distribution = PackageDistribution::with(['items.package.manifest'])->find($transaction->reference_id);
            
            if ($this->distribution && $this->distribution->items->isNotEmpty()) {
                $this->package = $this->distribution->items->first()->package;
                $this->manifest = $this->package->manifest ?? null;
            }
        }
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = "Transaction Review Request - {$this->customer->full_name}";
        
        return $this->subject($subject)
                    ->view('emails.admin.transaction-review-request')
                    ->with([
                        'transaction' => $this->transaction,
                        'customer' => $this->customer,
                        'package' => $this->package,
                        'manifest' => $this->manifest,
                        'distribution' => $this->distribution,
                    ]);
    }
}