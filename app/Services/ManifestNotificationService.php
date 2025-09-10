<?php

namespace App\Services;

use App\Models\Manifest;
use App\Models\User;
use App\Notifications\ManifestUnlockedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Exception;

class ManifestNotificationService
{
    /**
     * Send unlock notification to relevant stakeholders
     */
    public function sendUnlockNotification(Manifest $manifest, User $unlockedBy, string $reason): array
    {
        try {
            $recipients = $this->getUnlockNotificationRecipients($manifest, $unlockedBy);
            
            if ($recipients->isEmpty()) {
                Log::warning('No recipients found for manifest unlock notification', [
                    'manifest_id' => $manifest->id,
                    'manifest_name' => $manifest->name,
                    'unlocked_by_id' => $unlockedBy->id,
                ]);
                
                return [
                    'success' => true,
                    'message' => 'No notification recipients configured.',
                    'recipients_count' => 0
                ];
            }

            // Create notification instance
            $notification = new ManifestUnlockedNotification(
                $manifest,
                $unlockedBy,
                $reason,
                now()
            );

            // Send notification to all recipients
            Notification::send($recipients, $notification);

            Log::info('Manifest unlock notification sent successfully', [
                'manifest_id' => $manifest->id,
                'manifest_name' => $manifest->name,
                'unlocked_by_id' => $unlockedBy->id,
                'unlocked_by_name' => $unlockedBy->full_name,
                'recipients_count' => count($recipients),
                'recipient_emails' => $recipients->pluck('email')->toArray(),
            ]);

            return [
                'success' => true,
                'message' => 'Unlock notification sent successfully.',
                'recipients_count' => count($recipients)
            ];

        } catch (Exception $e) {
            Log::error('Failed to send manifest unlock notification', [
                'manifest_id' => $manifest->id,
                'manifest_name' => $manifest->name,
                'unlocked_by_id' => $unlockedBy->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send unlock notification: ' . $e->getMessage(),
                'recipients_count' => 0
            ];
        }
    }

    /**
     * Get recipients for unlock notifications
     */
    public function getUnlockNotificationRecipients(Manifest $manifest, User $unlockedBy): \Illuminate\Database\Eloquent\Collection
    {
        // Get all admin and superadmin users except the one who unlocked
        $recipients = User::whereHas('role', function ($query) {
                $query->whereIn('name', ['admin', 'superadmin']);
            })
            ->where('id', '!=', $unlockedBy->id) // Don't notify the person who unlocked
            ->whereNotNull('email_verified_at') // Only verified email addresses
            ->get();

        // Filter out soft-deleted users
        $recipients = $recipients->filter(function ($user) {
            return !$user->trashed();
        });

        // Additional filtering based on notification preferences could be added here
        // For example, checking user preferences for manifest notifications
        
        return $recipients;
    }

    /**
     * Get notification preferences for a user (placeholder for future enhancement)
     */
    public function getUserNotificationPreferences(User $user): array
    {
        // This is a placeholder for future notification preference system
        // For now, return default preferences
        return [
            'manifest_unlock' => true,
            'manifest_auto_close' => false, // Future feature
            'email_notifications' => true,
            'database_notifications' => true,
        ];
    }

    /**
     * Update notification preferences for a user (placeholder for future enhancement)
     */
    public function updateUserNotificationPreferences(User $user, array $preferences): array
    {
        // This is a placeholder for future notification preference system
        // For now, just return success
        Log::info('Notification preferences update requested', [
            'user_id' => $user->id,
            'preferences' => $preferences,
        ]);

        return [
            'success' => true,
            'message' => 'Notification preferences updated successfully.'
        ];
    }

    /**
     * Test notification delivery for a specific user
     */
    public function testNotificationDelivery(User $user, Manifest $manifest): array
    {
        try {
            // Create a test notification
            $testNotification = new ManifestUnlockedNotification(
                $manifest,
                auth()->user() ?? $user,
                'Test notification - please ignore',
                now()
            );

            // Send test notification
            $user->notify($testNotification);

            Log::info('Test notification sent successfully', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'manifest_id' => $manifest->id,
            ]);

            return [
                'success' => true,
                'message' => 'Test notification sent successfully to ' . $user->email
            ];

        } catch (Exception $e) {
            Log::error('Test notification failed', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'manifest_id' => $manifest->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Test notification failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStatistics(): array
    {
        try {
            // Count admin users who would receive notifications
            $adminCount = User::whereHas('role', function ($query) {
                    $query->whereIn('name', ['admin', 'superadmin']);
                })
                ->whereNotNull('email_verified_at')
                ->count();

            // Get recent unlock notifications from database notifications table
            $recentNotifications = \DB::table('notifications')
                ->where('type', 'App\Notifications\ManifestUnlockedNotification')
                ->where('created_at', '>=', now()->subDays(30))
                ->count();

            return [
                'success' => true,
                'admin_recipients_count' => $adminCount,
                'recent_notifications_30_days' => $recentNotifications,
                'notification_channels' => ['mail', 'database'],
            ];

        } catch (Exception $e) {
            Log::error('Failed to get notification statistics', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to get notification statistics: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate notification configuration
     */
    public function validateNotificationConfiguration(): array
    {
        $issues = [];
        
        try {
            // Check if there are any admin users to receive notifications
            $adminCount = User::whereHas('role', function ($query) {
                    $query->whereIn('name', ['admin', 'superadmin']);
                })
                ->whereNotNull('email_verified_at')
                ->count();

            if ($adminCount === 0) {
                $issues[] = 'No admin users with verified emails found to receive notifications';
            }

            // Check if mail configuration is set up
            if (!config('mail.default')) {
                $issues[] = 'Mail configuration is not set up';
            }

            // Check if queue is configured for notifications
            if (!config('queue.default') || config('queue.default') === 'sync') {
                $issues[] = 'Queue is not configured - notifications may be slow';
            }

            // Check if notification table exists for database notifications
            if (!\Schema::hasTable('notifications')) {
                $issues[] = 'Notifications table does not exist - database notifications will fail';
            }

            return [
                'success' => empty($issues),
                'issues' => $issues,
                'admin_count' => $adminCount ?? 0,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'issues' => ['Configuration validation failed: ' . $e->getMessage()],
                'admin_count' => 0,
            ];
        }
    }
}