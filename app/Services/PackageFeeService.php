<?php

namespace App\Services;

use App\Models\Package;
use App\Models\User;
use App\Models\CustomerTransaction;
use App\Enums\PackageStatus;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PackageFeeService
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }
    /**
     * Update package fees and transition to ready status
     */
    public function updatePackageFeesAndSetReady(
        Package $package,
        array $fees,
        User $updatedBy,
        bool $applyCreditBalance = false
    ): array {
        try {
            DB::beginTransaction();

            // Store the old status before updating
            $oldStatus = $package->status;

            // Update package fees
            $package->update([
                'customs_duty' => isset($fees['customs_duty']) ? $fees['customs_duty'] : 0,
                'storage_fee' => isset($fees['storage_fee']) ? $fees['storage_fee'] : 0,
                'delivery_fee' => isset($fees['delivery_fee']) ? $fees['delivery_fee'] : 0,
                'status' => PackageStatus::READY,
            ]);

            $totalCost = $package->total_cost;
            $customer = $package->user;
            $appliedCredit = 0;

            // Apply credit balance if requested and available
            if ($applyCreditBalance && $customer->credit_balance > 0) {
                $appliedCredit = $customer->applyCreditBalance(
                    $totalCost,
                    "Credit applied to package {$package->tracking_number}",
                    $updatedBy->id,
                    'package_fee_update',
                    $package->id,
                    [
                        'package_tracking' => $package->tracking_number,
                        'total_cost' => $totalCost,
                        'fees_updated' => $fees,
                    ]
                );

                // Log credit application to audit system
                if ($appliedCredit > 0) {
                    $this->auditService->logFinancialTransaction('credit_balance_applied', [
                        'customer_id' => $customer->id,
                        'package_id' => $package->id,
                        'package_tracking' => $package->tracking_number,
                        'amount' => $appliedCredit,
                        'type' => 'credit_application',
                        'total_cost' => $totalCost,
                        'applied_by' => $updatedBy->name,
                    ], $customer);
                }
            }

            // Log fee update to audit system
            $this->auditService->logFinancialTransaction('package_fees_updated', [
                'package_id' => $package->id,
                'package_tracking' => $package->tracking_number,
                'customer_id' => $customer->id,
                'old_status' => $oldStatus->value,
                'new_status' => PackageStatus::READY,
                'fees' => $fees,
                'total_cost' => $totalCost,
                'credit_applied' => $appliedCredit,
                'net_charge' => $totalCost - $appliedCredit,
                'updated_by' => $updatedBy->name,
            ], $customer);

            // Note: Customer will be charged when package is distributed, not when set to ready
            // This follows the business logic of charging only when package is actually delivered

            // Update package status history
            $package->statusHistory()->create([
                'old_status' => $oldStatus->value,
                'new_status' => PackageStatus::READY,
                'changed_by' => $updatedBy->id,
                'changed_at' => now(),
                'notes' => 'Package fees updated and set to ready for pickup',
            ]);

            // Send notification email
            app(PackageNotificationService::class)->sendStatusNotification($package, PackageStatus::from(PackageStatus::READY));

            DB::commit();

            return [
                'success' => true,
                'message' => 'Package fees updated and status set to ready for pickup',
                'package' => $package->fresh(),
                'total_cost' => $totalCost,
                'credit_applied' => $appliedCredit,
                'net_charge' => $totalCost - $appliedCredit,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update package fees', [
                'package_id' => $package->id,
                'fees' => $fees,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update package fees: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate balance impact for fee update
     */
    public function calculateBalanceImpact(Package $package, array $fees, bool $applyCreditBalance = false): array
    {
        $currentTotalCost = $package->total_cost;
        
        // Calculate new total cost
        $newTotalCost = ($package->freight_price ? $package->freight_price : 0) + 
                       (isset($fees['customs_duty']) ? $fees['customs_duty'] : 0) + 
                       (isset($fees['storage_fee']) ? $fees['storage_fee'] : 0) + 
                       (isset($fees['delivery_fee']) ? $fees['delivery_fee'] : 0);

        $customer = $package->user;
        $availableCredit = $applyCreditBalance ? $customer->credit_balance : 0;
        $creditToApply = min($newTotalCost, $availableCredit);
        $netCharge = $newTotalCost - $creditToApply;

        return [
            'current_total_cost' => $currentTotalCost,
            'new_total_cost' => $newTotalCost,
            'cost_difference' => $newTotalCost - $currentTotalCost,
            'available_credit' => $availableCredit,
            'credit_to_apply' => $creditToApply,
            'net_charge' => $netCharge,
            'customer_balance_after' => $customer->account_balance - $netCharge,
            'customer_credit_after' => $customer->credit_balance - $creditToApply,
        ];
    }

    /**
     * Validate fee update data
     */
    public function validateFees(array $fees): array
    {
        $errors = [];

        if (!isset($fees['customs_duty']) || !is_numeric($fees['customs_duty']) || $fees['customs_duty'] < 0) {
            $errors['customs_duty'] = 'Customs duty must be a valid positive number';
        }

        if (!isset($fees['storage_fee']) || !is_numeric($fees['storage_fee']) || $fees['storage_fee'] < 0) {
            $errors['storage_fee'] = 'Storage fee must be a valid positive number';
        }

        if (!isset($fees['delivery_fee']) || !is_numeric($fees['delivery_fee']) || $fees['delivery_fee'] < 0) {
            $errors['delivery_fee'] = 'Delivery fee must be a valid positive number';
        }

        return $errors;
    }

    /**
     * Get fee update preview
     */
    public function getFeeUpdatePreview(Package $package, array $fees, bool $applyCreditBalance = false): array
    {
        $validationErrors = $this->validateFees($fees);
        if (!empty($validationErrors)) {
            return [
                'valid' => false,
                'errors' => $validationErrors,
            ];
        }

        $balanceImpact = $this->calculateBalanceImpact($package, $fees, $applyCreditBalance);
        $customer = $package->user;

        return [
            'valid' => true,
            'package' => [
                'tracking_number' => $package->tracking_number,
                'description' => $package->description,
                'current_status' => $package->status->getLabel(),
                'new_status' => 'Ready for Pickup',
            ],
            'fees' => [
                'freight_price' => $package->freight_price ? $package->freight_price : 0,
                'customs_duty' => $fees['customs_duty'],
                'storage_fee' => $fees['storage_fee'],
                'delivery_fee' => $fees['delivery_fee'],
            ],
            'cost_summary' => $balanceImpact,
            'customer' => [
                'name' => $customer->full_name,
                'current_account_balance' => $customer->account_balance,
                'current_credit_balance' => $customer->credit_balance,
                'total_available_balance' => $customer->total_available_balance,
            ],
            'formatted' => [
                'new_total_cost' => number_format($balanceImpact['new_total_cost'], 2),
                'credit_to_apply' => number_format($balanceImpact['credit_to_apply'], 2),
                'net_charge' => number_format($balanceImpact['net_charge'], 2),
                'customer_balance_after' => number_format($balanceImpact['customer_balance_after'], 2),
                'customer_credit_after' => number_format($balanceImpact['customer_credit_after'], 2),
            ],
        ];
    }
}