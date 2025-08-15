<?php

namespace App\Mail;

use App\Models\ConsolidatedPackage;
use App\Models\PackageDistribution;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ConsolidatedPackageReceiptEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public PackageDistribution $distribution;
    public User $customer;
    public ConsolidatedPackage $consolidatedPackage;
    public array $totals;

    /**
     * Create a new message instance.
     *
     * @param PackageDistribution $distribution
     * @param User $customer
     * @param ConsolidatedPackage $consolidatedPackage
     */
    public function __construct(PackageDistribution $distribution, User $customer, ConsolidatedPackage $consolidatedPackage)
    {
        $this->distribution = $distribution;
        $this->customer = $customer;
        $this->consolidatedPackage = $consolidatedPackage;
        
        // Ensure customer profile is loaded
        $customer->load('profile');
        
        // Ensure consolidated package has individual packages loaded
        $consolidatedPackage->load('packages');
        
        // Calculate totals for email template
        $this->totals = $this->calculateRawTotals($distribution, $consolidatedPackage);

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
        $email = $this->subject('Consolidated Package Delivery Receipt - ' . $this->distribution->receipt_number)
            ->view('emails.packages.consolidated-receipt')
            ->with([
                'distribution' => $this->distribution,
                'customer' => $this->customer,
                'consolidatedPackage' => $this->consolidatedPackage,
                'individualPackages' => $this->consolidatedPackage->packages,
                'totals' => $this->totals,
                'receipt_number' => $this->distribution->receipt_number,
                'distributed_at' => $this->distribution->distributed_at->format('F j, Y \a\t g:i A'),
                'payment_status' => $this->getPaymentStatusLabel(),
                'company_name' => config('app.name', 'ShipShark Ltd'),
            ]);

        // Attach PDF receipt if it exists on public disk
        if (Storage::disk('public')->exists($this->distribution->receipt_path)) {
            $email->attach(Storage::disk('public')->path($this->distribution->receipt_path), [
                'as' => 'Consolidated-Receipt-' . $this->distribution->receipt_number . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $email;
    }

    /**
     * Calculate raw numeric totals for email template formatting
     *
     * @param PackageDistribution $distribution
     * @param ConsolidatedPackage $consolidatedPackage
     * @return array
     */
    private function calculateRawTotals(PackageDistribution $distribution, ConsolidatedPackage $consolidatedPackage): array
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
        \Log::error('Consolidated package receipt email failed', [
            'distribution_id' => $this->distribution->id,
            'consolidated_package_id' => $this->consolidatedPackage->id,
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