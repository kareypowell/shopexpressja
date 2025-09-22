<?php

namespace App\Services;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\PackageStatusHistory;
use App\Models\User;
use App\Services\PackageNotificationService;
use Illuminate\Support\Facades\Log;
use Exception;

class PackageStatusService
{
    protected $notificationService;

    public function __construct(PackageNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Update package status with validation and logging
     */
    public function updateStatus(Package $package, PackageStatus $newStatus, User $user, ?string $notes = null, bool $allowDeliveredStatus = false, bool $fromConsolidatedUpdate = false): bool
    {
        try {
            $oldStatus = $package->status; // Already cast to enum by model
            
            // Prevent manual updates to DELIVERED status (only allowed through distribution process)
            if ($newStatus->value === PackageStatus::DELIVERED && !$allowDeliveredStatus) {
                Log::warning('Manual update to DELIVERED status blocked - use distribution process instead', [
                    'package_id' => $package->id,
                    'old_status' => $oldStatus->value,
                    'new_status' => $newStatus->value,
                    'user_id' => $user->id,
                ]);
                return false;
            }
            
            // Validate status transition (skip validation for consolidated updates)
            if (!$fromConsolidatedUpdate && !$this->canTransitionTo($oldStatus, $newStatus)) {
                Log::warning('Invalid status transition attempted', [
                    'package_id' => $package->id,
                    'old_status' => $oldStatus->value,
                    'new_status' => $newStatus->value,
                    'user_id' => $user->id,
                ]);
                return false;
            }

            // Check if package is consolidated before updating
            $isConsolidated = $package->isConsolidated();
            $consolidatedPackage = $isConsolidated ? $package->consolidatedPackage : null;
            
            // Prevent individual status updates for consolidated packages (unless coming from consolidated update)
            if ($isConsolidated && $consolidatedPackage && !$fromConsolidatedUpdate) {
                Log::warning('Individual status update blocked for consolidated package - use consolidated package status update instead', [
                    'package_id' => $package->id,
                    'consolidated_package_id' => $consolidatedPackage->id,
                    'consolidated_tracking_number' => $consolidatedPackage->consolidated_tracking_number,
                    'old_status' => $oldStatus->value,
                    'new_status' => $newStatus->value,
                    'user_id' => $user->id,
                ]);
                return false;
            }

            // Update package status
            $package->status = $newStatus->value;
            $package->save();

            // Log status change
            $this->logStatusChange($package, $oldStatus, $newStatus, $user, $notes);

            // Send email notification to customer
            $this->notificationService->sendStatusNotification($package, $newStatus, $fromConsolidatedUpdate);

            Log::info('Package status updated successfully', [
                'package_id' => $package->id,
                'old_status' => $oldStatus->value,
                'new_status' => $newStatus->value,
                'user_id' => $user->id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to update package status', [
                'package_id' => $package->id,
                'new_status' => $newStatus->value,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get valid status transitions from current status
     */
    public function getValidTransitions(PackageStatus $currentStatus): array
    {
        return $currentStatus->getValidTransitions();
    }

    /**
     * Check if transition from one status to another is valid
     */
    public function canTransitionTo(PackageStatus $from, PackageStatus $to): bool
    {
        return $from->canTransitionTo($to);
    }

    /**
     * Log status change for audit trail
     */
    public function logStatusChange(
        Package $package, 
        PackageStatus $oldStatus, 
        PackageStatus $newStatus, 
        User $user, 
        ?string $notes = null
    ): void {
        try {
            // Create status history record
            PackageStatusHistory::create([
                'package_id' => $package->id,
                'old_status' => $oldStatus->value,
                'new_status' => $newStatus->value,
                'changed_by' => $user->id,
                'changed_at' => now(),
                'notes' => $notes,
            ]);

            // Also log to Laravel's log system for additional tracking
            Log::info('Package status change logged', [
                'package_id' => $package->id,
                'tracking_number' => $package->tracking_number,
                'old_status' => $oldStatus->value,
                'new_status' => $newStatus->value,
                'changed_by' => $user->id,
                'changed_by_name' => $user->full_name,
                'notes' => $notes,
                'timestamp' => now(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log status change', [
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update package status to DELIVERED through distribution process
     * This bypasses the manual update restriction for DELIVERED status
     */
    public function markAsDeliveredThroughDistribution(Package $package, User $user, ?string $notes = null): bool
    {
        return $this->updateStatus($package, PackageStatus::DELIVERED(), $user, $notes, true);
    }

    /**
     * Bulk update status for multiple packages
     */
    public function bulkUpdateStatus(array $packageIds, PackageStatus $newStatus, User $user, ?string $notes = null): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'total' => count($packageIds),
        ];

        foreach ($packageIds as $packageId) {
            $package = Package::find($packageId);
            
            if (!$package) {
                $results['failed'][] = [
                    'package_id' => $packageId,
                    'reason' => 'Package not found',
                ];
                continue;
            }

            if ($this->updateStatus($package, $newStatus, $user, $notes)) {
                $results['success'][] = $packageId;
            } else {
                $results['failed'][] = [
                    'package_id' => $packageId,
                    'reason' => 'Invalid status transition or update failed',
                ];
            }
        }

        Log::info('Bulk status update completed', [
            'total_packages' => $results['total'],
            'successful_updates' => count($results['success']),
            'failed_updates' => count($results['failed']),
            'new_status' => $newStatus->value,
            'user_id' => $user->id,
        ]);

        return $results;
    }

    /**
     * Get packages eligible for distribution (ready status)
     */
    public function getDistributablePackages(): \Illuminate\Database\Eloquent\Collection
    {
        return Package::where('status', PackageStatus::READY)
            ->with(['user', 'manifest', 'office'])
            ->get();
    }

    /**
     * Validate if packages can be distributed
     */
    public function canDistributePackages(array $packageIds): array
    {
        $packages = Package::whereIn('id', $packageIds)->get();
        $results = [
            'valid' => [],
            'invalid' => [],
        ];

        foreach ($packages as $package) {
            $status = PackageStatus::from($package->status);
            if ($status->allowsDistribution()) {
                $results['valid'][] = $package->id;
            } else {
                $results['invalid'][] = [
                    'package_id' => $package->id,
                    'current_status' => $status->value,
                    'reason' => 'Package must be in ready status for distribution',
                ];
            }
        }

        return $results;
    }

    /**
     * Get status statistics for reporting
     */
    public function getStatusStatistics(): array
    {
        $statistics = [];
        
        foreach (PackageStatus::cases() as $status) {
            $count = Package::where('status', $status->value)->count();
            $statistics[$status->value] = [
                'label' => $status->getLabel(),
                'count' => $count,
                'badge_class' => $status->getBadgeClass(),
            ];
        }

        return $statistics;
    }
}