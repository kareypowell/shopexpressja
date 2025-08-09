<?php

namespace App\Mail;

use App\Models\PackageDistribution;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class PackageReceiptEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public PackageDistribution $distribution;
    public User $customer;
    public array $packages;
    public array $totals;

    /**
     * Create a new message instance.
     *
     * @param PackageDistribution $distribution
     * @param User $customer
     */
    public function __construct(PackageDistribution $distribution, User $customer)
    {
        $this->distribution = $distribution;
        $this->customer = $customer;
        
        // Ensure customer profile is loaded
        $customer->load('profile');
        
        // Load distribution items with package details
        $this->packages = $distribution->items()
            ->with('package')
            ->get()
            ->map(function ($item) {
                return [
                    'tracking_number' => $item->package->tracking_number,
                    'description' => $item->package->description ?? 'Package',
                    'freight_price' => $item->freight_price,
                    'customs_duty' => $item->customs_duty,
                    'storage_fee' => $item->storage_fee,
                    'delivery_fee' => $item->delivery_fee,
                    'total_cost' => $item->total_cost,
                ];
            })
            ->toArray();

        // Calculate totals
        $this->totals = [
            'freight_total' => collect($this->packages)->sum('freight_price'),
            'customs_total' => collect($this->packages)->sum('customs_duty'),
            'storage_total' => collect($this->packages)->sum('storage_fee'),
            'delivery_total' => collect($this->packages)->sum('delivery_fee'),
            'grand_total' => $distribution->total_amount,
            'amount_collected' => $distribution->amount_collected,
            'balance' => $distribution->total_amount - $distribution->amount_collected,
        ];

        // Set queue configuration
        $this->onQueue('emails');
        $this->delay(now()->addSeconds(5)); // Small delay to ensure database transaction is committed
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $email = $this->subject('Package Delivery Receipt - ' . $this->distribution->receipt_number)
            ->view('emails.packages.receipt')
            ->with([
                'distribution' => $this->distribution,
                'customer' => $this->customer,
                'packages' => $this->packages,
                'totals' => $this->totals,
                'receipt_number' => $this->distribution->receipt_number,
                'distributed_at' => $this->distribution->distributed_at->format('F j, Y \a\t g:i A'),
                'payment_status' => $this->getPaymentStatusLabel(),
                'company_name' => config('app.name', 'ShipShark Ltd'),
            ]);

        // Attach PDF receipt if it exists on public disk
        if (Storage::disk('public')->exists($this->distribution->receipt_path)) {
            $email->attach(Storage::disk('public')->path($this->distribution->receipt_path), [
                'as' => 'Receipt-' . $this->distribution->receipt_number . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $email;
    }

    /**
     * Get human-readable payment status label
     *
     * @return string
     */
    private function getPaymentStatusLabel(): string
    {
        switch ($this->distribution->payment_status) {
            case 'paid':
                return 'Paid in Full';
            case 'partial':
                return 'Partially Paid';
            case 'unpaid':
                return 'Unpaid';
            default:
                return 'Unknown';
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        // Log the failure and update distribution record
        \Log::error('Package receipt email failed', [
            'distribution_id' => $this->distribution->id,
            'customer_email' => $this->customer->email,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Mark email as failed in database
        $this->distribution->update([
            'email_sent' => false,
            'email_sent_at' => null
        ]);
    }
}