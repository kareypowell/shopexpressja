<?php

namespace App\Services;

use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\PackageDistribution;
use App\Models\PackageDistributionItem;
use App\Models\User;
use App\Enums\PackageStatus;
use App\Services\ReceiptGeneratorService;
use App\Services\DistributionEmailService;
use App\Services\PackageStatusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PackageDistributionService
{
    protected $receiptGenerator;
    protected $emailService;

    public function __construct(ReceiptGeneratorService $receiptGenerator, DistributionEmailService $emailService)
    {
        $this->receiptGenerator = $receiptGenerator;
        $this->emailService = $emailService;
    }
    /**
     * Distribute consolidated packages to a customer
     *
     * @param ConsolidatedPackage $consolidatedPackage
     * @param float $amountCollected
     * @param User $user
     * @param array $balanceOptions Balance application options: ['credit' => bool, 'account' => bool]
     * @param array $options Additional options: writeOff, feeAdjustments, notes
     * @return array
     */
    public function distributeConsolidatedPackages(ConsolidatedPackage $consolidatedPackage, float $amountCollected, User $user, array $balanceOptions = [], array $options = []): array
    {
        try {
            DB::beginTransaction();

            // Validate consolidated package is ready for distribution
            if ($consolidatedPackage->status !== PackageStatus::READY) {
                throw new Exception('Consolidated package is not ready for distribution');
            }

            // Get all packages in the consolidation
            $packages = $consolidatedPackage->packages()
                ->where('status', PackageStatus::READY)
                ->get();

            if ($packages->isEmpty()) {
                throw new Exception('No packages ready for distribution in this consolidation');
            }

            $customer = $consolidatedPackage->customer;
            if (!$customer) {
                throw new Exception('Customer not found');
            }

            // Use consolidated totals for distribution
            $totalAmount = $consolidatedPackage->total_cost;
            
            // Apply fee adjustments if provided
            if (isset($options['feeAdjustments']) && is_array($options['feeAdjustments'])) {
                // For consolidated packages, adjustments apply to the consolidated totals
                if (isset($options['feeAdjustments']['consolidated'])) {
                    $adjustments = $options['feeAdjustments']['consolidated'];
                    $consolidatedPackage->update([
                        'total_freight_price' => $adjustments['total_freight_price'] ?? $consolidatedPackage->total_freight_price,
                        'total_clearance_fee' => $adjustments['total_clearance_fee'] ?? $consolidatedPackage->total_clearance_fee,
                        'total_storage_fee' => $adjustments['total_storage_fee'] ?? $consolidatedPackage->total_storage_fee,
                        'total_delivery_fee' => $adjustments['total_delivery_fee'] ?? $consolidatedPackage->total_delivery_fee,
                    ]);
                    $totalAmount = $consolidatedPackage->fresh()->total_cost;
                }
            }
            
            // Apply write-off/discount if provided
            $writeOffAmount = 0;
            if (isset($options['writeOff']) && $options['writeOff'] > 0) {
                $writeOffAmount = min($options['writeOff'], $totalAmount);
                $totalAmount -= $writeOffAmount;
            }

            // Parse balance options
            $applyCreditBalance = $balanceOptions['credit'] ?? $balanceOptions['applyCreditBalance'] ?? false;
            $applyAccountBalance = $balanceOptions['account'] ?? $balanceOptions['applyAccountBalance'] ?? false;
            
            // Apply available balances
            $creditApplied = 0;
            $accountBalanceApplied = 0;
            $remainingAmount = $totalAmount;
            
            // Apply credit balance if requested and available
            if ($applyCreditBalance && $customer->credit_balance > 0) {
                $creditToApply = min($customer->credit_balance, $remainingAmount);
                $creditApplied = $customer->applyCreditBalance(
                    $creditToApply,
                    "Credit applied to consolidated package distribution - Receipt #TBD",
                    $user->id,
                    'consolidated_package_distribution',
                    null,
                    [
                        'consolidated_package_id' => $consolidatedPackage->id,
                        'consolidated_tracking_number' => $consolidatedPackage->consolidated_tracking_number,
                        'package_ids' => $packages->pluck('id')->toArray(),
                        'total_amount' => $totalAmount,
                        'amount_collected' => $amountCollected,
                    ]
                );
                $remainingAmount -= $creditApplied;
            }
            
            // Apply account balance if requested and available
            $remainingAfterCash = $remainingAmount - $amountCollected;
            if ($applyAccountBalance && $remainingAfterCash > 0 && $customer->account_balance > 0) {
                $accountBalanceApplied = min($customer->account_balance, $remainingAfterCash);
                
                $customer->recordCharge(
                    $accountBalanceApplied,
                    "Account balance applied to consolidated package distribution - Receipt #TBD",
                    $user->id,
                    'consolidated_package_distribution',
                    null,
                    [
                        'consolidated_package_id' => $consolidatedPackage->id,
                        'consolidated_tracking_number' => $consolidatedPackage->consolidated_tracking_number,
                        'package_ids' => $packages->pluck('id')->toArray(),
                        'total_amount' => $totalAmount,
                        'amount_collected' => $amountCollected,
                        'account_balance_applied' => $accountBalanceApplied,
                    ]
                );
            }
            
            $totalBalanceApplied = $creditApplied + $accountBalanceApplied;
            $totalReceived = $amountCollected + $totalBalanceApplied;
            $paymentStatus = $this->validatePaymentAmount($totalAmount, $totalReceived);

            // Create distribution record
            $distribution = PackageDistribution::create([
                'receipt_number' => PackageDistribution::generateReceiptNumber(),
                'customer_id' => $customer->id,
                'distributed_by' => $user->id,
                'distributed_at' => now(),
                'total_amount' => $totalAmount + $writeOffAmount,
                'amount_collected' => $amountCollected,
                'credit_applied' => $creditApplied,
                'account_balance_applied' => $accountBalanceApplied,
                'write_off_amount' => $writeOffAmount,
                'write_off_reason' => $options['writeOffReason'] ?? null,
                'payment_status' => $paymentStatus,
                'notes' => ($options['notes'] ?? '') . " [Consolidated Package: {$consolidatedPackage->consolidated_tracking_number}]",
                'receipt_path' => '',
                'email_sent' => false,
            ]);

            // Update transaction references with distribution ID
            $this->updateTransactionReferences($customer, $distribution, $creditApplied, $accountBalanceApplied);

            // Handle payment transactions
            $this->handleConsolidatedPaymentTransactions($customer, $distribution, $consolidatedPackage, $packages, $user, $totalAmount, $amountCollected, $totalBalanceApplied, $writeOffAmount);

            // Create distribution items for each package in the consolidation
            foreach ($packages as $package) {
                PackageDistributionItem::create([
                    'distribution_id' => $distribution->id,
                    'package_id' => $package->id,
                    'freight_price' => $package->freight_price ?? 0,
                    'clearance_fee' => $package->clearance_fee ?? 0,
                    'storage_fee' => $package->storage_fee ?? 0,
                    'delivery_fee' => $package->delivery_fee ?? 0,
                    'total_cost' => $package->total_cost ?? 0,
                ]);

                // Update package status to delivered
                $packageStatusService = app(PackageStatusService::class);
                $packageStatusService->markAsDeliveredThroughDistribution(
                    $package, 
                    $user, 
                    'Package delivered through consolidated distribution process'
                );
            }

            // Update consolidated package status
            $consolidatedPackage->syncPackageStatuses(PackageStatus::DELIVERED);

            // Generate consolidated receipt PDF
            try {
                $receiptPath = $this->generateConsolidatedReceipt($consolidatedPackage, $distribution);
                $distribution->update(['receipt_path' => $receiptPath]);
                
                // Send receipt email
                $emailResult = $this->emailService->sendReceiptEmail($distribution, $customer);
                
                if ($emailResult['success']) {
                    Log::info('Consolidated distribution receipt email sent successfully', [
                        'distribution_id' => $distribution->id,
                        'consolidated_package_id' => $consolidatedPackage->id,
                        'customer_email' => $customer->email,
                        'receipt_number' => $distribution->receipt_number
                    ]);
                } else {
                    Log::warning('Consolidated distribution receipt email failed', [
                        'distribution_id' => $distribution->id,
                        'consolidated_package_id' => $consolidatedPackage->id,
                        'customer_email' => $customer->email,
                        'error' => $emailResult['message']
                    ]);
                }
                
            } catch (Exception $e) {
                Log::warning('Consolidated receipt generation failed', [
                    'distribution_id' => $distribution->id,
                    'consolidated_package_id' => $consolidatedPackage->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Log the distribution
            $this->logConsolidatedDistribution($consolidatedPackage, $packages->toArray(), $distribution, $amountCollected, $user);

            DB::commit();

            return [
                'success' => true,
                'distribution' => $distribution,
                'consolidated_package' => $consolidatedPackage,
                'message' => 'Consolidated packages distributed successfully',
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Consolidated package distribution failed', [
                'error' => $e->getMessage(),
                'consolidated_package_id' => $consolidatedPackage->id,
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
     * Distribute packages to a customer with enhanced options
     *
     * @param array $packageIds
     * @param float $amountCollected
     * @param User $user
     * @param array $balanceOptions Balance application options: ['credit' => bool, 'account' => bool]
     * @param array $options Additional options: writeOff, feeAdjustments, notes
     * @return array
     */
    public function distributePackages(array $packageIds, float $amountCollected, User $user, array $balanceOptions = [], array $options = []): array
    {
        try {
            DB::beginTransaction();

            // Check if package IDs are provided
            if (empty($packageIds)) {
                throw new Exception('No packages provided for distribution');
            }

            // Check if any packages are consolidated and handle accordingly
            $consolidatedPackageIds = Package::whereIn('id', $packageIds)
                ->whereNotNull('consolidated_package_id')
                ->pluck('consolidated_package_id')
                ->unique();

            if ($consolidatedPackageIds->isNotEmpty()) {
                // If consolidated packages are detected, redirect to consolidated distribution
                if ($consolidatedPackageIds->count() > 1) {
                    throw new Exception('Cannot distribute packages from multiple consolidations together');
                }
                
                $consolidatedPackage = ConsolidatedPackage::find($consolidatedPackageIds->first());
                if (!$consolidatedPackage) {
                    throw new Exception('Consolidated package not found');
                }
                
                return $this->distributeConsolidatedPackages($consolidatedPackage, $amountCollected, $user, $balanceOptions, $options);
            }

            // Validate packages are ready for distribution
            $packages = Package::whereIn('id', $packageIds)
                ->where('status', PackageStatus::READY)
                ->whereNull('consolidated_package_id') // Only individual packages
                ->get();

            if ($packages->count() !== count($packageIds)) {
                throw new Exception('Some packages are not ready for distribution or are part of a consolidation');
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

            // Calculate total cost with potential fee adjustments
            $totalAmount = $this->calculatePackageTotals($packages->toArray());
            
            // Apply fee adjustments if provided
            if (isset($options['feeAdjustments']) && is_array($options['feeAdjustments'])) {
                foreach ($options['feeAdjustments'] as $packageId => $adjustments) {
                    $package = $packages->firstWhere('id', $packageId);
                    if ($package && is_array($adjustments)) {
                        // Update package fees before distribution
                        $package->update([
                            'freight_price' => $adjustments['freight_price'] ?? $package->freight_price,
                            'clearance_fee' => $adjustments['clearance_fee'] ?? $package->clearance_fee,
                            'storage_fee' => $adjustments['storage_fee'] ?? $package->storage_fee,
                            'delivery_fee' => $adjustments['delivery_fee'] ?? $package->delivery_fee,
                        ]);
                    }
                }
                // Recalculate total after adjustments
                $packages = $packages->fresh(); // Reload packages with updated fees
                $totalAmount = $this->calculatePackageTotals($packages->toArray());
            }
            
            // Apply write-off/discount if provided
            $writeOffAmount = 0;
            if (isset($options['writeOff']) && $options['writeOff'] > 0) {
                $writeOffAmount = min($options['writeOff'], $totalAmount); // Can't write off more than total
                $totalAmount -= $writeOffAmount;
            }

            // Parse balance options - support both old and new format for backward compatibility
            $applyCreditBalance = $balanceOptions['credit'] ?? $balanceOptions['applyCreditBalance'] ?? false;
            $applyAccountBalance = $balanceOptions['account'] ?? $balanceOptions['applyAccountBalance'] ?? false;
            
            // Apply available balances based on user selection
            $creditApplied = 0;
            $accountBalanceApplied = 0;
            $remainingAmount = $totalAmount;
            
            // Apply credit balance if requested and available
            if ($applyCreditBalance && $customer->credit_balance > 0) {
                $creditToApply = min($customer->credit_balance, $remainingAmount);
                $creditApplied = $customer->applyCreditBalance(
                    $creditToApply,
                    "Credit applied to package distribution - Receipt #TBD",
                    $user->id,
                    'package_distribution',
                    null, // Will be updated after distribution is created
                    [
                        'package_ids' => $packageIds,
                        'total_amount' => $totalAmount,
                        'amount_collected' => $amountCollected,
                    ]
                );
                $remainingAmount -= $creditApplied;
            }
            
            // Apply account balance if requested and available
            // Calculate remaining amount after credit applied AND cash collected
            $remainingAfterCash = $remainingAmount - $amountCollected;
            if ($applyAccountBalance && $remainingAfterCash > 0 && $customer->account_balance > 0) {
                $accountBalanceApplied = min($customer->account_balance, $remainingAfterCash);
                
                // Record the account balance application as a charge (deduction from account)
                $customer->recordCharge(
                    $accountBalanceApplied,
                    "Account balance applied to package distribution - Receipt #TBD",
                    $user->id,
                    'package_distribution',
                    null, // Will be updated after distribution is created
                    [
                        'package_ids' => $packageIds,
                        'total_amount' => $totalAmount,
                        'amount_collected' => $amountCollected,
                        'account_balance_applied' => $accountBalanceApplied,
                    ]
                );
            }
            
            $totalBalanceApplied = $creditApplied + $accountBalanceApplied;

            // Determine payment status
            $totalReceived = $amountCollected + $totalBalanceApplied;
            $paymentStatus = $this->validatePaymentAmount($totalAmount, $totalReceived);

            // Create distribution record
            $distribution = PackageDistribution::create([
                'receipt_number' => PackageDistribution::generateReceiptNumber(),
                'customer_id' => $customer->id,
                'distributed_by' => $user->id,
                'distributed_at' => now(),
                'total_amount' => $totalAmount + $writeOffAmount, // Original total before write-off
                'amount_collected' => $amountCollected,
                'credit_applied' => $creditApplied,
                'account_balance_applied' => $accountBalanceApplied,
                'write_off_amount' => $writeOffAmount,
                'write_off_reason' => $options['writeOffReason'] ?? null,
                'payment_status' => $paymentStatus,
                'notes' => $options['notes'] ?? null,
                'receipt_path' => '', // Will be set after PDF generation
                'email_sent' => false,
            ]);

            // Update transaction references with distribution ID
            if ($creditApplied > 0) {
                $creditTransaction = $customer->transactions()
                    ->where('reference_type', 'package_distribution')
                    ->whereNull('reference_id')
                    ->where('amount', $creditApplied)
                    ->latest()
                    ->first();
                
                if ($creditTransaction) {
                    $creditTransaction->update([
                        'reference_id' => $distribution->id,
                        'description' => str_replace('Receipt #TBD', "Receipt #{$distribution->receipt_number}", $creditTransaction->description)
                    ]);
                }
            }
            
            if ($accountBalanceApplied > 0) {
                $accountTransaction = $customer->transactions()
                    ->where('reference_type', 'package_distribution')
                    ->whereNull('reference_id')
                    ->where('amount', $accountBalanceApplied)
                    ->where('type', 'charge')
                    ->latest()
                    ->first();
                
                if ($accountTransaction) {
                    $accountTransaction->update([
                        'reference_id' => $distribution->id,
                        'description' => str_replace('Receipt #TBD', "Receipt #{$distribution->receipt_number}", $accountTransaction->description)
                    ]);
                }
            }

            // Get manifest_id from the first package (all packages should be from same manifest ideally)
            $manifestId = $packages->first()->manifest_id ?? null;
            
            // Handle cash payments and account charges differently
            // Apply proper rounding at the start of transaction processing
            $totalAmount = round($totalAmount, 2);
            $amountCollected = round($amountCollected, 2);
            $totalBalanceApplied = round($totalBalanceApplied, 2);
            
            $totalPaid = round($amountCollected + $totalBalanceApplied, 2);
            $netChargeAmount = round($totalAmount - $totalBalanceApplied, 2);
            
            if ($totalPaid >= $totalAmount) {
                // Customer paid enough - record charge and payment for audit trail
                // but ensure net effect on account balance is zero for cash portion
                
                if ($netChargeAmount > 0) {
                    // Record the charge
                    $customer->recordCharge(
                        $netChargeAmount,
                        "Package distribution charge - Receipt #{$distribution->receipt_number}",
                        $user->id,
                        'package_distribution',
                        $distribution->id,
                        [
                            'distribution_id' => $distribution->id,
                            'total_amount' => $totalAmount,
                            'credit_applied' => $creditApplied,
                            'account_balance_applied' => $accountBalanceApplied,
                            'total_balance_applied' => $totalBalanceApplied,
                            'net_charge' => $netChargeAmount,
                            'package_ids' => $packageIds,
                        ],
                        $manifestId
                    );
                }
                
                // Record cash payment if provided
                if ($amountCollected > 0) {
                    // Calculate how much of the cash payment covers the service vs overpayment
                    $servicePaymentAmount = round(min($amountCollected, $netChargeAmount), 2);
                    
                    if ($servicePaymentAmount > 0) {
                        $customer->recordPayment(
                            $servicePaymentAmount,
                            "Payment received for package distribution - Receipt #{$distribution->receipt_number}",
                            $user->id,
                            'package_distribution',
                            $distribution->id,
                            [
                                'distribution_id' => $distribution->id,
                                'total_amount' => $totalAmount,
                                'amount_collected' => $amountCollected,
                                'service_payment_portion' => $servicePaymentAmount,
                                'package_ids' => $packageIds,
                            ],
                            $manifestId
                        );
                    }
                }
            } else {
                // Customer didn't pay enough - charge account for full amount and record payment
                if ($netChargeAmount > 0) {
                    $customer->recordCharge(
                        $netChargeAmount,
                        "Package distribution charge - Receipt #{$distribution->receipt_number}",
                        $user->id,
                        'package_distribution',
                        $distribution->id,
                        [
                            'distribution_id' => $distribution->id,
                            'total_amount' => $totalAmount,
                            'credit_applied' => $creditApplied,
                            'account_balance_applied' => $accountBalanceApplied,
                            'total_balance_applied' => $totalBalanceApplied,
                            'net_charge' => $netChargeAmount,
                            'package_ids' => $packageIds,
                        ],
                        $manifestId
                    );
                }
                
                if ($amountCollected > 0) {
                    $customer->recordPayment(
                        $amountCollected,
                        "Payment received for package distribution - Receipt #{$distribution->receipt_number}",
                        $user->id,
                        'package_distribution',
                        $distribution->id,
                        [
                            'distribution_id' => $distribution->id,
                            'total_amount' => $totalAmount,
                            'amount_collected' => $amountCollected,
                            'package_ids' => $packageIds,
                        ],
                        $manifestId
                    );
                }
            }
            
            // Note: The charge and payment transactions above handle the full transaction flow
            // No additional logic needed here as balances are already properly managed

            // Record write-off if provided
            if ($writeOffAmount > 0) {
                $customer->recordWriteOff(
                    $writeOffAmount,
                    "Write-off/discount applied - Receipt #{$distribution->receipt_number}" . 
                    (isset($options['writeOffReason']) ? " - {$options['writeOffReason']}" : ""),
                    $user->id,
                    'package_distribution',
                    $distribution->id,
                    [
                        'distribution_id' => $distribution->id,
                        'original_total' => $totalAmount + $writeOffAmount,
                        'write_off_amount' => $writeOffAmount,
                        'write_off_reason' => $options['writeOffReason'] ?? 'Discount applied',
                        'package_ids' => $packageIds,
                    ],
                    $manifestId
                );
            }

            // Handle true overpayment - only when customer paid more cash than needed
            // Calculate if there's actual overpayment from cash payment
            $totalCovered = round($totalBalanceApplied + $amountCollected, 2);
            $actualOverpayment = round($totalCovered - $totalAmount, 2);
            
            if ($actualOverpayment > 0.01) {
                // Customer paid more cash than needed - convert overpayment to credit
                $customer->addOverpaymentCredit(
                    $actualOverpayment,
                    "Overpayment credit from package distribution - Receipt #{$distribution->receipt_number}",
                    $user->id,
                    'package_distribution',
                    $distribution->id,
                    [
                        'distribution_id' => $distribution->id,
                        'total_amount' => $totalAmount,
                        'amount_collected' => $amountCollected,
                        'total_balance_applied' => $totalBalanceApplied,
                        'overpayment' => $actualOverpayment,
                        'package_ids' => $packageIds,
                    ]
                );
                
                // Note: Do NOT reduce account balance for cash overpayments
                // The overpayment came from cash, not from the customer's account balance
                // Only the credit balance should be affected
            }
            
            // Note: Customer's remaining account balance should stay as account balance
            // Only cash overpayments should be converted to credit

            // Create distribution items
            foreach ($packages as $package) {
                PackageDistributionItem::create([
                    'distribution_id' => $distribution->id,
                    'package_id' => $package->id,
                    'freight_price' => $package->freight_price ? $package->freight_price : 0,
                    'clearance_fee' => $package->clearance_fee ? $package->clearance_fee : 0,
                    'storage_fee' => $package->storage_fee ? $package->storage_fee : 0,
                    'delivery_fee' => $package->delivery_fee ? $package->delivery_fee : 0,
                    'total_cost' => $package->total_cost ? $package->total_cost : 0,
                ]);

                // Update package status to delivered through proper distribution process
                $packageStatusService = app(PackageStatusService::class);
                $packageStatusService->markAsDeliveredThroughDistribution(
                    $package, 
                    $user, 
                    'Package delivered through distribution process'
                );
            }

            // Generate receipt PDF
            try {
                $receiptPath = $this->receiptGenerator->generatePDF($distribution);
                
                // Update distribution with receipt path
                $distribution->update(['receipt_path' => $receiptPath]);
                
                // Send receipt email to customer
                $emailResult = $this->emailService->sendReceiptEmail($distribution, $customer);
                
                if ($emailResult['success']) {
                    Log::info('Distribution receipt email sent successfully', [
                        'distribution_id' => $distribution->id,
                        'customer_email' => $customer->email,
                        'receipt_number' => $distribution->receipt_number
                    ]);
                } else {
                    Log::warning('Distribution receipt email failed', [
                        'distribution_id' => $distribution->id,
                        'customer_email' => $customer->email,
                        'error' => $emailResult['message']
                    ]);
                }
                
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
     * Calculate total cost for packages or consolidated packages
     *
     * @param array $packages
     * @param ConsolidatedPackage|null $consolidatedPackage
     * @return float
     */
    public function calculatePackageTotals(array $packages, ConsolidatedPackage $consolidatedPackage = null): float
    {
        // If consolidated package is provided, use its totals
        if ($consolidatedPackage) {
            return round($consolidatedPackage->total_cost, 2);
        }

        $total = 0;

        foreach ($packages as $package) {
            $packageTotal = 0;
            
            if (is_array($package)) {
                $packageTotal += round(isset($package['freight_price']) ? $package['freight_price'] : 0, 2);
                $packageTotal += round(isset($package['clearance_fee']) ? $package['clearance_fee'] : 0, 2);
                $packageTotal += round(isset($package['storage_fee']) ? $package['storage_fee'] : 0, 2);
                $packageTotal += round(isset($package['delivery_fee']) ? $package['delivery_fee'] : 0, 2);
            } else {
                $packageTotal += round($package->freight_price ? $package->freight_price : 0, 2);
                $packageTotal += round($package->clearance_fee ? $package->clearance_fee : 0, 2);
                $packageTotal += round($package->storage_fee ? $package->storage_fee : 0, 2);
                $packageTotal += round($package->delivery_fee ? $package->delivery_fee : 0, 2);
            }

            $total += round($packageTotal, 2);
        }

        return round($total, 2);
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
            ->where('status', PackageStatus::READY)
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

    /**
     * Backward compatibility method for the old interface
     * 
     * @param array $packageIds
     * @param float $amountCollected
     * @param User $user
     * @param bool $applyCreditBalance
     * @param array $options
     * @return array
     */
    public function distributePackagesLegacy(array $packageIds, float $amountCollected, User $user, bool $applyCreditBalance = false, array $options = []): array
    {
        $balanceOptions = [];
        if ($applyCreditBalance) {
            $balanceOptions['credit'] = true;
            $balanceOptions['account'] = true;
        }
        
        return $this->distributePackages($packageIds, $amountCollected, $user, $balanceOptions, $options);
    }

    /**
     * Generate consolidated receipt with itemized individual package details
     *
     * @param ConsolidatedPackage $consolidatedPackage
     * @param PackageDistribution $distribution
     * @return string
     */
    public function generateConsolidatedReceipt(ConsolidatedPackage $consolidatedPackage, PackageDistribution $distribution): string
    {
        try {
            // Load distribution with relationships
            $distribution->load(['customer', 'distributedBy', 'items.package']);

            // Format consolidated receipt data
            $receiptData = $this->formatConsolidatedReceiptData($consolidatedPackage, $distribution);

            // Calculate totals using the receipt generator
            $totals = $this->receiptGenerator->calculateTotals($distribution);

            // Add payment status to totals if not present
            if (!isset($totals['payment_status'])) {
                $totals['payment_status'] = ucfirst($distribution->payment_status);
            }

            // Merge data
            $data = array_merge($receiptData, $totals);

            // Generate PDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('receipts.consolidated-package-distribution', $data);
            
            // Set PDF options
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif'
            ]);

            // Generate filename
            $filename = 'receipts/consolidated-' . $distribution->receipt_number . '.pdf';
            
            // Save PDF to storage
            $pdfContent = $pdf->output();
            \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $pdfContent);

            return $filename;

        } catch (Exception $e) {
            throw new Exception('Failed to generate consolidated receipt: ' . $e->getMessage());
        }
    }

    /**
     * Format consolidated receipt data for PDF template
     *
     * @param ConsolidatedPackage $consolidatedPackage
     * @param PackageDistribution $distribution
     * @return array
     */
    public function formatConsolidatedReceiptData(ConsolidatedPackage $consolidatedPackage, PackageDistribution $distribution): array
    {
        return [
            'receipt_number' => $distribution->receipt_number,
            'distribution_date' => $distribution->distributed_at->format('F j, Y'),
            'distribution_time' => $distribution->distributed_at->format('g:i A'),
            'is_consolidated' => true,
            'consolidated_tracking_number' => $consolidatedPackage->consolidated_tracking_number,
            'consolidated_totals' => [
                'total_weight' => number_format($consolidatedPackage->total_weight, 2),
                'total_quantity' => $consolidatedPackage->total_quantity,
                'total_freight_price' => number_format($consolidatedPackage->total_freight_price, 2),
                'total_clearance_fee' => number_format($consolidatedPackage->total_clearance_fee, 2),
                'total_storage_fee' => number_format($consolidatedPackage->total_storage_fee, 2),
                'total_delivery_fee' => number_format($consolidatedPackage->total_delivery_fee, 2),
                'total_cost' => number_format($consolidatedPackage->total_cost, 2),
            ],
            'customer' => [
                'name' => $distribution->customer->full_name,
                'email' => $distribution->customer->email,
                'account_number' => $distribution->customer->profile->account_number ?? 'N/A',
            ],
            'distributed_by' => [
                'name' => $distribution->distributedBy->full_name,
                'role' => $distribution->distributedBy->role->name ?? 'Staff',
            ],
            'packages' => $distribution->items->sortByDesc('package.tracking_number')->map(function ($item) {
                $package = $item->package;
                $isSeaPackage = $package->isSeaPackage();
                
                return [
                    'tracking_number' => $package->tracking_number,
                    'description' => $package->description ?? 'Package',
                    'weight_display' => $isSeaPackage 
                        ? number_format($package->cubic_feet ?? 0, 2) . ' ft³'
                        : number_format($package->weight ?? 0, 1) . ' lbs',
                    'weight_label' => $isSeaPackage ? 'Cubic Feet' : 'Weight',
                    'is_sea_package' => $isSeaPackage,
                    'freight_price' => number_format($item->freight_price, 2),
                    'clearance_fee' => number_format($item->clearance_fee, 2),
                    'storage_fee' => number_format($item->storage_fee, 2),
                    'delivery_fee' => number_format($item->delivery_fee, 2),
                    'total_cost' => number_format($item->total_cost, 2),
                ];
            })->toArray(),
            'company' => [
                'name' => config('app.name', 'Shop Express JA'),
                'address' => '57 Law Street, Kingston, Jamaica',
                'phone' => '876-453-7789',
                'email' => 'support@shopexpressja.com',
                'website' => 'www.shopexpressjs.com',
            ],
        ];
    }

    /**
     * Handle payment transactions for consolidated packages
     *
     * @param User $customer
     * @param PackageDistribution $distribution
     * @param ConsolidatedPackage $consolidatedPackage
     * @param \Illuminate\Database\Eloquent\Collection $packages
     * @param User $user
     * @param float $totalAmount
     * @param float $amountCollected
     * @param float $totalBalanceApplied
     * @param float $writeOffAmount
     * @return void
     */
    protected function handleConsolidatedPaymentTransactions(User $customer, PackageDistribution $distribution, ConsolidatedPackage $consolidatedPackage, $packages, User $user, float $totalAmount, float $amountCollected, float $totalBalanceApplied, float $writeOffAmount): void
    {
        // Apply proper rounding at the start of transaction processing
        $totalAmount = round($totalAmount, 2);
        $amountCollected = round($amountCollected, 2);
        $totalBalanceApplied = round($totalBalanceApplied, 2);
        $writeOffAmount = round($writeOffAmount, 2);
        
        $totalPaid = round($amountCollected + $totalBalanceApplied, 2);
        $netChargeAmount = round($totalAmount - $totalBalanceApplied, 2);
        
        // Get manifest information from packages for linking
        $manifestIds = $packages->pluck('manifest_id')->filter()->unique();
        $primaryManifest = null;
        if ($manifestIds->count() === 1) {
            // All packages from same manifest - link transactions to it
            $primaryManifest = \App\Models\Manifest::find($manifestIds->first());
        }
        
        if ($totalPaid >= $totalAmount) {
            // Customer paid enough
            if ($netChargeAmount > 0) {
                $transaction = $customer->recordCharge(
                    $netChargeAmount,
                    "Consolidated package distribution charge - Receipt #{$distribution->receipt_number}",
                    $user->id,
                    'App\\Models\\PackageDistribution',
                    $distribution->id,
                    [
                        'distribution_id' => $distribution->id,
                        'consolidated_package_id' => $consolidatedPackage->id,
                        'consolidated_tracking_number' => $consolidatedPackage->consolidated_tracking_number,
                        'total_amount' => $totalAmount,
                        'package_ids' => $packages->pluck('id')->toArray(),
                        'manifest_ids' => $manifestIds->toArray(),
                    ]
                );
                
                // Link to primary manifest if available
                if ($primaryManifest) {
                    $transaction->linkToManifest($primaryManifest);
                }
            }
            
            if ($amountCollected > 0) {
                $servicePaymentAmount = round(min($amountCollected, $netChargeAmount), 2);
                
                if ($servicePaymentAmount > 0) {
                    $transaction = $customer->recordPayment(
                        $servicePaymentAmount,
                        "Payment received for consolidated package distribution - Receipt #{$distribution->receipt_number}",
                        $user->id,
                        'App\\Models\\PackageDistribution',
                        $distribution->id,
                        [
                            'distribution_id' => $distribution->id,
                            'consolidated_package_id' => $consolidatedPackage->id,
                            'consolidated_tracking_number' => $consolidatedPackage->consolidated_tracking_number,
                            'amount_collected' => $amountCollected,
                            'service_payment_portion' => $servicePaymentAmount,
                            'package_ids' => $packages->pluck('id')->toArray(),
                            'manifest_ids' => $manifestIds->toArray(),
                        ]
                    );
                    
                    // Link to primary manifest if available
                    if ($primaryManifest) {
                        $transaction->linkToManifest($primaryManifest);
                    }
                }
            }
        } else {
            // Customer didn't pay enough
            if ($netChargeAmount > 0) {
                $transaction = $customer->recordCharge(
                    $netChargeAmount,
                    "Consolidated package distribution charge - Receipt #{$distribution->receipt_number}",
                    $user->id,
                    'App\\Models\\PackageDistribution',
                    $distribution->id,
                    [
                        'distribution_id' => $distribution->id,
                        'consolidated_package_id' => $consolidatedPackage->id,
                        'consolidated_tracking_number' => $consolidatedPackage->consolidated_tracking_number,
                        'total_amount' => $totalAmount,
                        'package_ids' => $packages->pluck('id')->toArray(),
                        'manifest_ids' => $manifestIds->toArray(),
                    ]
                );
                
                // Link to primary manifest if available
                if ($primaryManifest) {
                    $transaction->linkToManifest($primaryManifest);
                }
            }
            
            if ($amountCollected > 0) {
                $customer->recordPayment(
                    $amountCollected,
                    "Payment received for consolidated package distribution - Receipt #{$distribution->receipt_number}",
                    $user->id,
                    'consolidated_package_distribution',
                    $distribution->id,
                    [
                        'distribution_id' => $distribution->id,
                        'consolidated_package_id' => $consolidatedPackage->id,
                        'consolidated_tracking_number' => $consolidatedPackage->consolidated_tracking_number,
                        'amount_collected' => $amountCollected,
                        'package_ids' => $packages->pluck('id')->toArray(),
                    ]
                );
            }
        }

        // Record write-off if provided
        if ($writeOffAmount > 0) {
            $customer->recordWriteOff(
                $writeOffAmount,
                "Write-off/discount applied to consolidated distribution - Receipt #{$distribution->receipt_number}",
                $user->id,
                'consolidated_package_distribution',
                $distribution->id,
                [
                    'distribution_id' => $distribution->id,
                    'consolidated_package_id' => $consolidatedPackage->id,
                    'consolidated_tracking_number' => $consolidatedPackage->consolidated_tracking_number,
                    'original_total' => $totalAmount + $writeOffAmount,
                    'write_off_amount' => $writeOffAmount,
                    'package_ids' => $packages->pluck('id')->toArray(),
                ]
            );
        }

        // Handle overpayment
        $totalCovered = round($totalBalanceApplied + $amountCollected, 2);
        $actualOverpayment = round($totalCovered - $totalAmount, 2);
        
        if ($actualOverpayment > 0.01) {
            $customer->addOverpaymentCredit(
                $actualOverpayment,
                "Overpayment credit from consolidated package distribution - Receipt #{$distribution->receipt_number}",
                $user->id,
                'consolidated_package_distribution',
                $distribution->id,
                [
                    'distribution_id' => $distribution->id,
                    'consolidated_package_id' => $consolidatedPackage->id,
                    'consolidated_tracking_number' => $consolidatedPackage->consolidated_tracking_number,
                    'total_amount' => $totalAmount,
                    'amount_collected' => $amountCollected,
                    'total_balance_applied' => $totalBalanceApplied,
                    'overpayment' => $actualOverpayment,
                    'package_ids' => $packages->pluck('id')->toArray(),
                ]
            );
        }
    }

    /**
     * Update transaction references with distribution ID
     *
     * @param User $customer
     * @param PackageDistribution $distribution
     * @param float $creditApplied
     * @param float $accountBalanceApplied
     * @return void
     */
    protected function updateTransactionReferences(User $customer, PackageDistribution $distribution, float $creditApplied, float $accountBalanceApplied): void
    {
        if ($creditApplied > 0) {
            $creditTransaction = $customer->transactions()
                ->where('reference_type', 'package_distribution')
                ->orWhere('reference_type', 'consolidated_package_distribution')
                ->whereNull('reference_id')
                ->where('amount', $creditApplied)
                ->latest()
                ->first();
            
            if ($creditTransaction) {
                $creditTransaction->update([
                    'reference_id' => $distribution->id,
                    'description' => str_replace('Receipt #TBD', "Receipt #{$distribution->receipt_number}", $creditTransaction->description)
                ]);
            }
        }
        
        if ($accountBalanceApplied > 0) {
            $accountTransaction = $customer->transactions()
                ->where('reference_type', 'package_distribution')
                ->orWhere('reference_type', 'consolidated_package_distribution')
                ->whereNull('reference_id')
                ->where('amount', $accountBalanceApplied)
                ->where('type', 'charge')
                ->latest()
                ->first();
            
            if ($accountTransaction) {
                $accountTransaction->update([
                    'reference_id' => $distribution->id,
                    'description' => str_replace('Receipt #TBD', "Receipt #{$distribution->receipt_number}", $accountTransaction->description)
                ]);
            }
        }
    }

    /**
     * Log consolidated distribution transaction
     *
     * @param ConsolidatedPackage $consolidatedPackage
     * @param array $packages
     * @param PackageDistribution $distribution
     * @param float $amountCollected
     * @param User $user
     * @return void
     */
    public function logConsolidatedDistribution(ConsolidatedPackage $consolidatedPackage, array $packages, PackageDistribution $distribution, float $amountCollected, User $user): void
    {
        $packageIds = collect($packages)->pluck('id')->toArray();
        
        Log::info('Consolidated package distribution completed', [
            'distribution_id' => $distribution->id,
            'receipt_number' => $distribution->receipt_number,
            'consolidated_package_id' => $consolidatedPackage->id,
            'consolidated_tracking_number' => $consolidatedPackage->consolidated_tracking_number,
            'customer_id' => $distribution->customer_id,
            'distributed_by' => $user->id,
            'package_ids' => $packageIds,
            'package_count' => count($packageIds),
            'total_amount' => $distribution->total_amount,
            'amount_collected' => $amountCollected,
            'payment_status' => $distribution->payment_status,
            'distributed_at' => $distribution->distributed_at,
        ]);
    }
}