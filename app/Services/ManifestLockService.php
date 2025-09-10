<?php

namespace App\Services;

use App\Models\Manifest;
use App\Models\ManifestAudit;
use App\Models\User;
use App\Services\ManifestNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ManifestLockService
{
    protected ManifestNotificationService $notificationService;

    public function __construct(ManifestNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * Check if a manifest can be edited by a user
     */
    public function canEdit(Manifest $manifest, User $user): bool
    {
        return $manifest->is_open && $user->can('edit', $manifest);
    }

    /**
     * Automatically close manifest if all packages are delivered
     */
    public function autoCloseIfComplete(Manifest $manifest): bool
    {
        try {
            // Don't process if manifest is already closed
            if (!$manifest->is_open) {
                return false;
            }

            // Check if all packages are delivered
            $allDelivered = $manifest->packages()
                ->where('status', '!=', 'delivered')
                ->count() === 0;

            // Only close if there are packages and all are delivered
            if ($allDelivered && $manifest->packages()->count() > 0) {
                return $this->closeManifest($manifest, 'auto_complete', 'All packages have been delivered');
            }

            return false;
        } catch (Exception $e) {
            Log::error('Failed to auto-close manifest', [
                'manifest_id' => $manifest->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Unlock a closed manifest with reason validation and audit logging
     */
    public function unlockManifest(Manifest $manifest, User $user, string $reason): array
    {
        try {
            // Validate that manifest is actually closed first
            if ($manifest->is_open) {
                return [
                    'success' => false, 
                    'message' => 'Manifest is already open.'
                ];
            }

            // Check if user has permission to unlock
            if (!$user->can('unlock', $manifest)) {
                return [
                    'success' => false, 
                    'message' => 'You do not have permission to unlock this manifest.'
                ];
            }

            // Validate reason
            $trimmedReason = trim($reason);
            if (empty($trimmedReason)) {
                return [
                    'success' => false, 
                    'message' => 'A reason is required to unlock the manifest.'
                ];
            }

            if (strlen($trimmedReason) < 10) {
                return [
                    'success' => false, 
                    'message' => 'Reason must be at least 10 characters long.'
                ];
            }

            if (strlen($trimmedReason) > 500) {
                return [
                    'success' => false, 
                    'message' => 'Reason cannot exceed 500 characters.'
                ];
            }

            // Perform unlock operation in transaction
            DB::transaction(function() use ($manifest, $user, $trimmedReason) {
                // Update manifest to open
                $manifest->update(['is_open' => true]);

                // Log the unlock action
                ManifestAudit::create([
                    'manifest_id' => $manifest->id,
                    'user_id' => $user->id,
                    'action' => 'unlocked',
                    'reason' => $trimmedReason,
                    'performed_at' => now()
                ]);
            });

            // Send unlock notification to stakeholders
            $notificationResult = $this->notificationService->sendUnlockNotification(
                $manifest, 
                $user, 
                $trimmedReason
            );

            // Log successful unlock
            Log::info('Manifest unlocked successfully', [
                'manifest_id' => $manifest->id,
                'manifest_name' => $manifest->name,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'reason' => $trimmedReason,
                'notification_sent' => $notificationResult['success'],
                'notification_recipients' => $notificationResult['recipients_count'] ?? 0,
            ]);

            $message = 'Manifest unlocked successfully.';
            if ($notificationResult['success'] && $notificationResult['recipients_count'] > 0) {
                $message .= ' Notification sent to ' . $notificationResult['recipients_count'] . ' stakeholder(s).';
            } elseif (!$notificationResult['success']) {
                $message .= ' Warning: Notification delivery failed.';
            }

            return [
                'success' => true, 
                'message' => $message,
                'notification_result' => $notificationResult
            ];

        } catch (Exception $e) {
            Log::error('Failed to unlock manifest', [
                'manifest_id' => $manifest->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false, 
                'message' => 'An error occurred while unlocking the manifest. Please try again.'
            ];
        }
    }

    /**
     * Manually lock/close a manifest with reason validation and audit logging
     */
    public function lockManifest(Manifest $manifest, User $user, string $reason): array
    {
        try {
            // Validate that manifest is actually open first
            if (!$manifest->is_open) {
                return [
                    'success' => false, 
                    'message' => 'Manifest is already closed.'
                ];
            }

            // Check if user has permission to edit (which includes closing)
            if (!$user->can('edit', $manifest)) {
                return [
                    'success' => false, 
                    'message' => 'You do not have permission to close this manifest.'
                ];
            }

            // Validate reason
            $trimmedReason = trim($reason);
            if (empty($trimmedReason)) {
                return [
                    'success' => false, 
                    'message' => 'A reason is required to close the manifest.'
                ];
            }

            if (strlen($trimmedReason) < 10) {
                return [
                    'success' => false, 
                    'message' => 'Reason must be at least 10 characters long.'
                ];
            }

            if (strlen($trimmedReason) > 500) {
                return [
                    'success' => false, 
                    'message' => 'Reason cannot exceed 500 characters.'
                ];
            }

            // Perform lock operation in transaction
            DB::transaction(function() use ($manifest, $user, $trimmedReason) {
                // Update manifest to closed
                $manifest->update(['is_open' => false]);

                // Log the lock action
                ManifestAudit::create([
                    'manifest_id' => $manifest->id,
                    'user_id' => $user->id,
                    'action' => 'closed',
                    'reason' => $trimmedReason,
                    'performed_at' => now()
                ]);
            });

            // Log successful lock
            Log::info('Manifest locked successfully', [
                'manifest_id' => $manifest->id,
                'manifest_name' => $manifest->name,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'reason' => $trimmedReason,
            ]);

            return [
                'success' => true, 
                'message' => 'Manifest locked successfully.'
            ];

        } catch (Exception $e) {
            Log::error('Failed to lock manifest', [
                'manifest_id' => $manifest->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false, 
                'message' => 'An error occurred while locking the manifest. Please try again.'
            ];
        }
    }

    /**
     * Close a manifest with consistent handling and audit logging
     */
    private function closeManifest(Manifest $manifest, string $action, string $reason): bool
    {
        try {
            return DB::transaction(function() use ($manifest, $action, $reason) {
                // Update manifest to closed
                $manifest->update(['is_open' => false]);

                // Log the closure action (use system user ID if no authenticated user)
                $userId = auth()->id() ?? 1; // Default to system user for auto-closure
                
                ManifestAudit::create([
                    'manifest_id' => $manifest->id,
                    'user_id' => $userId,
                    'action' => $action,
                    'reason' => $reason,
                    'performed_at' => now()
                ]);

                // Log successful closure
                Log::info('Manifest closed successfully', [
                    'manifest_id' => $manifest->id,
                    'manifest_name' => $manifest->name,
                    'action' => $action,
                    'reason' => $reason,
                    'user_id' => $userId,
                ]);

                return true;
            });
        } catch (Exception $e) {
            Log::error('Failed to close manifest', [
                'manifest_id' => $manifest->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get manifest lock status information
     */
    public function getManifestLockStatus(Manifest $manifest): array
    {
        return [
            'is_open' => $manifest->is_open,
            'status_label' => $manifest->status_label,
            'status_badge_class' => $manifest->status_badge_class,
            'can_be_edited' => $manifest->canBeEdited(),
            'all_packages_delivered' => $manifest->allPackagesDelivered(),
            'package_count' => $manifest->packages()->count(),
            'delivered_package_count' => $manifest->packages()->where('status', 'delivered')->count(),
        ];
    }

    /**
     * Check if manifest is eligible for auto-closure
     */
    public function isEligibleForAutoClosure(Manifest $manifest): bool
    {
        return $manifest->is_open && 
               $manifest->packages()->count() > 0 && 
               $manifest->allPackagesDelivered();
    }

    /**
     * Get recent audit activity for a manifest
     */
    public function getRecentAuditActivity(Manifest $manifest, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $manifest->audits()
            ->with('user:id,first_name,last_name,email')
            ->limit($limit)
            ->get();
    }

    /**
     * Validate unlock reason format and content
     */
    public function validateUnlockReason(string $reason): array
    {
        $trimmedReason = trim($reason);
        
        if (empty($trimmedReason)) {
            return [
                'valid' => false,
                'message' => 'A reason is required to unlock the manifest.'
            ];
        }

        if (strlen($trimmedReason) < 10) {
            return [
                'valid' => false,
                'message' => 'Reason must be at least 10 characters long.'
            ];
        }

        if (strlen($trimmedReason) > 500) {
            return [
                'valid' => false,
                'message' => 'Reason cannot exceed 500 characters.'
            ];
        }

        return [
            'valid' => true,
            'message' => 'Reason is valid.'
        ];
    }
}