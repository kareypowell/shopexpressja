<?php

namespace App\Mail;

use App\Models\PackageDistribution;
use App\Models\User;
use App\Services\ReceiptGeneratorService;
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
        
        // Use ReceiptGeneratorService for consistent data formatting
        $receiptGenerator = app(ReceiptGeneratorService::class);
        
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

        // Get raw numeric totals for email template (will be formatted in template)
        $this->totals = $this->calculateRawTotals($distribution);

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
     * Calculate raw numeric totals for email template formatting
     *
     * @param PackageDistribution $distribution
     * @return array
     */
    private function calculateRawTotals(PackageDistribution $distribution): array
    {
        $subtotal = 0;
        $totalFreight = 0;
        $totalCustoms = 0;
        $totalStorage = 0;
        $totalDelivery = 0;

        foreach ($distribution->items as $item) {
            $subtotal += $item->total_cost;
            $totalFreight += $item->freight_price;
            $totalCustoms += $item->customs_duty;
            $totalStorage += $item->storage_fee;
            $totalDelivery += $item->delivery_fee;
        }

        $totalPaid = $distribution->amount_collected + $distribution->credit_applied + ($distribution->account_balance_applied ?? 0) + $distribution->write_off_amount;
        $outstandingBalance = max(0, $distribution->total_amount - $totalPaid);

        return [
            'subtotal' => $subtotal,
            'total_freight' => $totalFreight,
            'total_customs' => $totalCustoms,
            'total_storage' => $totalStorage,
            'total_delivery' => $totalDelivery,
            'total_amount' => $distribution->total_amount,
            'amount_collected' => $distribution->amount_collected,
            'credit_applied' => $distribution->credit_applied,
            'account_balance_applied' => $distribution->account_balance_applied ?? 0,
            'write_off_amount' => $distribution->write_off_amount,
            'total_paid' => $totalPaid,
            'outstanding_balance' => $outstandingBalance,
            'payment_status' => ucfirst($distribution->payment_status),
        ];
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