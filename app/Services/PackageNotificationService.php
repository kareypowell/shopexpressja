<?php

namespace App\Services;

use App\Models\Package;
use App\Models\User;
use App\Models\ConsolidatedPackage;
use App\Enums\PackageStatus;
use App\Notifications\PackageProcessingNotification;
use App\Notifications\PackageShippedNotification;
use App\Notifications\PackageCustomsNotification;
use App\Notifications\PackageReadyNotification;
use App\Notifications\PackageDeliveredNotification;
use App\Notifications\PackageDelayedNotification;
use App\Notifications\ConsolidatedPackageStatusNotification;
use App\Notifications\PackageConsolidationNotification;
use App\Notifications\PackageUnconsolidationNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class PackageNotificationService
{
    /**
     * Send notification based on package status
     *
     * @param Package $package
     * @param PackageStatus $newStatus
     * @return bool
     */
    public function sendStatusNotification(Package $package, PackageStatus $newStatus): bool
    {
        try {
            $user = $package->user;
            
            if (!$user) {
                Log::warning('Cannot send notification: Package has no associated user', [
                    'package_id' => $package->id,
                    'status' => $newStatus->value
                ]);
                return false;
            }

            // Check if package is consolidated and send consolidated notification instead
            if ($package->is_consolidated && $package->consolidatedPackage) {
                return $this->sendConsolidatedStatusNotification(
                    $package->consolidatedPackage, 
                    $newStatus
                );
            }

            $notification = $this->getNotificationForStatus($newStatus, $user, $package);
            
            if (!$notification) {
                Log::info('No notification configured for status', [
                    'package_id' => $package->id,
                    'status' => $newStatus->value
                ]);
                return true; // Not an error, just no notification needed
            }

            $user->notify($notification);

            Log::info('Package status notification sent', [
                'package_id' => $package->id,
                'user_id' => $user->id,
                'status' => $newStatus->value,
                'notification_class' => get_class($notification)
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send package status notification', [
                'package_id' => $package->id,
                'status' => $newStatus->value,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get the appropriate notification class for the status
     *
     * @param PackageStatus $status
     * @param User $user
     * @param Package $package
     * @return mixed|null
     */
    private function getNotificationForStatus(PackageStatus $status, User $user, Package $package)
    {
        switch ($status->value) {
            case PackageStatus::PROCESSING:
                return new PackageProcessingNotification(
                    $user,
                    $package->tracking_number,
                    $package->description
                );

            case PackageStatus::SHIPPED:
                return new PackageShippedNotification(
                    $user,
                    $package->tracking_number,
                    $package->description
                );

            case PackageStatus::CUSTOMS:
                return new PackageCustomsNotification(
                    $user,
                    $package->tracking_number,
                    $package->description
                );

            case PackageStatus::READY:
                return new PackageReadyNotification(
                    $user,
                    $package->tracking_number,
                    $package->description,
                    $package // Include package for cost information
                );

            case PackageStatus::DELIVERED:
                // No separate notification for delivered status
                // Receipt email is sent through distribution process instead
                return null;

            case PackageStatus::DELAYED:
                return new PackageDelayedNotification(
                    $user,
                    $package->tracking_number,
                    $package->description
                );

            case PackageStatus::PENDING:
            default:
                // No notification for pending status or unknown statuses
                return null;
        }
    }

    /**
     * Send bulk status notifications
     *
     * @param array $packages
     * @param PackageStatus $newStatus
     * @return array
     */
    public function sendBulkStatusNotifications(array $packages, PackageStatus $newStatus): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($packages as $package) {
            if ($this->sendStatusNotification($package, $newStatus)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Failed to send notification for package {$package->tracking_number}";
            }
        }

        return $results;
    }

    /**
     * Send consolidated package status notification
     *
     * @param ConsolidatedPackage $consolidatedPackage
     * @param PackageStatus $newStatus
     * @return bool
     */
    public function sendConsolidatedStatusNotification(ConsolidatedPackage $consolidatedPackage, PackageStatus $newStatus): bool
    {
        try {
            $user = $consolidatedPackage->customer;
            
            if (!$user) {
                Log::warning('Cannot send consolidated notification: ConsolidatedPackage has no associated customer', [
                    'consolidated_package_id' => $consolidatedPackage->id,
                    'status' => $newStatus->value
                ]);
                return false;
            }

            // Only send notifications for certain statuses
            $notifiableStatuses = [
                PackageStatus::PROCESSING,
                PackageStatus::SHIPPED,
                PackageStatus::CUSTOMS,
                PackageStatus::READY,
                PackageStatus::DELIVERED,
                PackageStatus::DELAYED
            ];

            if (!in_array($newStatus, $notifiableStatuses)) {
                Log::info('No consolidated notification configured for status', [
                    'consolidated_package_id' => $consolidatedPackage->id,
                    'status' => $newStatus->value
                ]);
                return true; // Not an error, just no notification needed
            }

            $notification = new ConsolidatedPackageStatusNotification($user, $consolidatedPackage, $newStatus);
            $user->notify($notification);

            Log::info('Consolidated package status notification sent', [
                'consolidated_package_id' => $consolidatedPackage->id,
                'user_id' => $user->id,
                'status' => $newStatus->value,
                'individual_packages_count' => $consolidatedPackage->packages->count()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send consolidated package status notification', [
                'consolidated_package_id' => $consolidatedPackage->id,
                'status' => $newStatus->value,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send package consolidation notification
     *
     * @param ConsolidatedPackage $consolidatedPackage
     * @return bool
     */
    public function sendConsolidationNotification(ConsolidatedPackage $consolidatedPackage): bool
    {
        try {
            $user = $consolidatedPackage->customer;
            
            if (!$user) {
                Log::warning('Cannot send consolidation notification: ConsolidatedPackage has no associated customer', [
                    'consolidated_package_id' => $consolidatedPackage->id
                ]);
                return false;
            }

            $notification = new PackageConsolidationNotification($user, $consolidatedPackage);
            $user->notify($notification);

            Log::info('Package consolidation notification sent', [
                'consolidated_package_id' => $consolidatedPackage->id,
                'user_id' => $user->id,
                'individual_packages_count' => $consolidatedPackage->packages->count()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send package consolidation notification', [
                'consolidated_package_id' => $consolidatedPackage->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send package unconsolidation notification
     *
     * @param Collection $packages
     * @param User $customer
     * @param string $formerConsolidatedTrackingNumber
     * @return bool
     */
    public function sendUnconsolidationNotification(Collection $packages, User $customer, string $formerConsolidatedTrackingNumber): bool
    {
        try {
            if ($packages->isEmpty()) {
                Log::warning('Cannot send unconsolidation notification: No packages provided');
                return false;
            }

            $notification = new PackageUnconsolidationNotification($customer, $packages, $formerConsolidatedTrackingNumber);
            $customer->notify($notification);

            Log::info('Package unconsolidation notification sent', [
                'user_id' => $customer->id,
                'former_consolidated_tracking_number' => $formerConsolidatedTrackingNumber,
                'individual_packages_count' => $packages->count(),
                'package_ids' => $packages->pluck('id')->toArray()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send package unconsolidation notification', [
                'user_id' => $customer->id,
                'former_consolidated_tracking_number' => $formerConsolidatedTrackingNumber,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}