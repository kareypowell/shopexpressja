<?php

namespace App\Services;

use App\Models\Package;
use App\Models\User;
use App\Enums\PackageStatus;
use App\Notifications\PackageProcessingNotification;
use App\Notifications\PackageShippedNotification;
use App\Notifications\PackageCustomsNotification;
use App\Notifications\PackageReadyNotification;
use App\Notifications\PackageDeliveredNotification;
use App\Notifications\PackageDelayedNotification;
use Illuminate\Support\Facades\Log;

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
}