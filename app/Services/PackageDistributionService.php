<?php

namespace App\Services;

use App\Models\Package;
use App\Models\PackageDistribution;
use App\Models\PackageDistributionItem;
use App\Models\User;
use App\Enums\PackageStatus;
use App\Services\ReceiptGeneratorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PackageDistributionService
{
    protected $receiptGenerator;

    public function __construct(ReceiptGeneratorService $receiptGenerator)
    {
        $this->receiptGenerator = $receiptGenerator;
    }
    /**
     * Distribute packages to a customer
     *
     * @param array $packageIds
     * @param float $amountCollected
     * @param User $user
     * @return array
     */
    public function distributePackages(array $packageIds, float $amountCollected, User $user): array
    {
        try {
            DB::beginTransaction();

            // Validate packages are ready for distribution
            $packages = Package::whereIn('id', $packageIds)
                ->where('status', PackageStatus::READY->value)
                ->get();

            if ($packages->count() !== count($packageIds)) {
                throw new Exception('Some packages are not ready for distribution');
            }

            // Ensure all packages belong to the same customer
            $customerIds = $packages->pluck('user_id')->unique();
            if ($customerIds->count() > 1) {
                throw new Exception('All packages must belong to the same customer');
            }

            $customer = User::find($customerIds->first());
            if (!$customer) {
                throw new Exception('Customer not found');
            }

            // Calculate total cost
            $totalAmount = $this->calculatePackageTotals($packages->toArray());

            // Determine payment status
            $paymentStatus = $this->validatePaymentAmount($totalAmount, $amountCollected);

            // Create distribution record
            $distribution = PackageDistribution::create([
                'receipt_number' => PackageDistribution::generateReceiptNumber(),
                'customer_id' => $customer->id,
                'distributed_by' => $user->id,
                'distributed_at' => now(),
                'total_amount' => $totalAmount,
                'amount_collected' => $amountCollected,
                'payment_status' => $paymentStatus,
                'receipt_path' => '', // Will be set after PDF generation
                'email_sent' => false,
            ]);

            // Create distribution items
            foreach ($packages as $package) {
                PackageDistributionItem::create([
                    'distribution_id' => $distribution->id,
                    'package_id' => $package->id,
                    'freight_price' => $package->freight_price ?? 0,
                    'customs_duty' => $package->customs_duty ?? 0,
                    'storage_fee' => $package->storage_fee ?? 0,
                    'delivery_fee' => $package->delivery_fee ?? 0,
                    'total_cost' => $package->total_cost ?? 0,
                ]);

                // Update package status to delivered
                $package->update(['status' => PackageStatus::DELIVERED->value]);
            }

            // Generate receipt PDF
            try {
                $receiptPath = $this->receiptGenerator->generatePDF($distribution);
            } catch (Exception $e) {
                Log::warning('Receipt generation failed', [
                    'distribution_id' => $distribution->id,
                    'error' => $e->getMessage(),
                ]);
                // Continue without failing the distribution
            }

            // Log the distribution
            $this->logDistribution($packages->toArray(), $distribution, $amountCollected, $user);

            DB::commit();

            return [
                'success' => true,
                'distribution' => $distribution,
                'message' => 'Packages distributed successfully',
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Package distribution failed', [
                'error' => $e->getMessage(),
                'package_ids' => $packageIds,
                'amount_collected' => $amountCollected,
                'user_id' => $user->id,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate total cost for packages
     *
     * @param array $packages
     * @return float
     */
    public function calculatePackageTotals(array $packages): float
    {
        $total = 0;

        foreach ($packages as $package) {
            $packageTotal = 0;
            
            if (is_array($package)) {
                $packageTotal += $package['freight_price'] ?? 0;
                $packageTotal += $package['customs_duty'] ?? 0;
                $packageTotal += $package['storage_fee'] ?? 0;
                $packageTotal += $package['delivery_fee'] ?? 0;
            } else {
                $packageTotal += $package->freight_price ?? 0;
                $packageTotal += $package->customs_duty ?? 0;
                $packageTotal += $package->storage_fee ?? 0;
                $packageTotal += $package->delivery_fee ?? 0;
            }

            $total += $packageTotal;
        }

        return $total;
    }

    /**
     * Validate payment amount and determine payment status
     *
     * @param float $totalCost
     * @param float $amountCollected
     * @return string
     */
    public function validatePaymentAmount(float $totalCost, float $amountCollected): string
    {
        if ($amountCollected >= $totalCost) {
            return 'paid';
        } elseif ($amountCollected > 0) {
            return 'partial';
        } else {
            return 'unpaid';
        }
    }

    /**
     * Log distribution transaction
     *
     * @param array $packages
     * @param PackageDistribution $distribution
     * @param float $amountCollected
     * @param User $user
     * @return void
     */
    public function logDistribution(array $packages, PackageDistribution $distribution, float $amountCollected, User $user): void
    {
        $packageIds = collect($packages)->pluck('id')->toArray();
        
        Log::info('Package distribution completed', [
            'distribution_id' => $distribution->id,
            'receipt_number' => $distribution->receipt_number,
            'customer_id' => $distribution->customer_id,
            'distributed_by' => $user->id,
            'package_ids' => $packageIds,
            'total_amount' => $distribution->total_amount,
            'amount_collected' => $amountCollected,
            'payment_status' => $distribution->payment_status,
            'distributed_at' => $distribution->distributed_at,
        ]);
    }

    /**
     * Get packages ready for distribution for a specific customer
     *
     * @param int $customerId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getReadyPackagesForCustomer(int $customerId)
    {
        return Package::where('user_id', $customerId)
            ->where('status', PackageStatus::READY->value)
            ->get();
    }

    /**
     * Get distribution history for a customer
     *
     * @param int $customerId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCustomerDistributionHistory(int $customerId)
    {
        return PackageDistribution::where('customer_id', $customerId)
            ->with(['items.package', 'distributedBy'])
            ->orderBy('distributed_at', 'desc')
            ->get();
    }
}