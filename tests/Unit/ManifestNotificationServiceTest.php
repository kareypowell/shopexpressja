<?php

namespace Tests\Unit;

use App\Models\Manifest;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ManifestUnlockedNotification;
use App\Services\ManifestNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ManifestNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ManifestNotificationService $notificationService;
    protected User $adminUser;
    protected User $superAdminUser;
    protected User $customerUser;
    protected Manifest $manifest;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->notificationService = new ManifestNotificationService();
        
        // Create roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $superAdminRole = Role::factory()->create(['name' => 'superadmin']);
        $customerRole = Role::factory()->create(['name' => 'customer']);
        
        // Create users
        $this->adminUser = User::factory()->create([
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);
        
        $this->superAdminUser = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'email_verified_at' => now(),
        ]);
        
        $this->customerUser = User::factory()->create([
            'role_id' => $customerRole->id,
            'email_verified_at' => now(),
        ]);
        
        // Create manifest
        $this->manifest = Manifest::factory()->create([
            'is_open' => false,
        ]);
    }

    public function test_get_unlock_notification_recipients_returns_admin_users()
    {
        $recipients = $this->notificationService->getUnlockNotificationRecipients(
            $this->manifest, 
            $this->adminUser
        );

        // Should include superadmin but not the admin who unlocked or customer
        $this->assertCount(1, $recipients);
        $this->assertTrue($recipients->contains($this->superAdminUser));
        $this->assertFalse($recipients->contains($this->adminUser)); // Excluded as unlocking user
        $this->assertFalse($recipients->contains($this->customerUser)); // Not admin role
    }

    public function test_get_unlock_notification_recipients_excludes_unverified_emails()
    {
        // Create admin with unverified email
        $unverifiedAdmin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->first()->id,
            'email_verified_at' => null,
        ]);

        $recipients = $this->notificationService->getUnlockNotificationRecipients(
            $this->manifest, 
            $this->customerUser
        );

        // Should not include unverified admin
        $this->assertFalse($recipients->contains($unverifiedAdmin));
        $this->assertTrue($recipients->contains($this->adminUser));
        $this->assertTrue($recipients->contains($this->superAdminUser));
    }

    public function test_get_unlock_notification_recipients_excludes_soft_deleted_users()
    {
        // Soft delete the admin user
        $this->adminUser->delete();

        $recipients = $this->notificationService->getUnlockNotificationRecipients(
            $this->manifest, 
            $this->customerUser
        );

        // Should not include soft-deleted admin
        $this->assertFalse($recipients->contains($this->adminUser));
        $this->assertTrue($recipients->contains($this->superAdminUser));
    }

    public function test_send_unlock_notification_sends_to_recipients()
    {
        Notification::fake();

        $result = $this->notificationService->sendUnlockNotification(
            $this->manifest,
            $this->adminUser,
            'Test unlock reason'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['recipients_count']); // Only superadmin should receive

        Notification::assertSentTo(
            $this->superAdminUser,
            ManifestUnlockedNotification::class,
            function ($notification) {
                return $notification->manifest->id === $this->manifest->id &&
                       $notification->unlockedBy->id === $this->adminUser->id &&
                       $notification->reason === 'Test unlock reason';
            }
        );

        Notification::assertNotSentTo($this->adminUser, ManifestUnlockedNotification::class);
        Notification::assertNotSentTo($this->customerUser, ManifestUnlockedNotification::class);
    }

    public function test_send_unlock_notification_handles_no_recipients()
    {
        // Delete all admin users
        User::whereHas('role', function ($query) {
            $query->whereIn('name', ['admin', 'superadmin']);
        })->delete();

        // Test the notification service directly
        $result = $this->notificationService->sendUnlockNotification(
            $this->manifest,
            $this->customerUser,
            'Test unlock reason'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['recipients_count']);
        $this->assertEquals('No notification recipients configured.', $result['message']);
    }

    public function test_test_notification_delivery_sends_test_notification()
    {
        Notification::fake();

        $result = $this->notificationService->testNotificationDelivery(
            $this->adminUser,
            $this->manifest
        );

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Test notification sent successfully', $result['message']);

        Notification::assertSentTo(
            $this->adminUser,
            ManifestUnlockedNotification::class,
            function ($notification) {
                return $notification->manifest->id === $this->manifest->id &&
                       $notification->reason === 'Test notification - please ignore';
            }
        );
    }

    public function test_get_notification_statistics_returns_correct_data()
    {
        $stats = $this->notificationService->getNotificationStatistics();

        $this->assertTrue($stats['success']);
        $this->assertEquals(2, $stats['admin_recipients_count']); // admin + superadmin
        $this->assertArrayHasKey('recent_notifications_30_days', $stats);
        $this->assertEquals(['mail', 'database'], $stats['notification_channels']);
    }

    public function test_validate_notification_configuration_checks_admin_users()
    {
        $validation = $this->notificationService->validateNotificationConfiguration();

        // In test environment, queue might be 'sync' which is flagged as an issue
        // But we should have admin users, so check that specifically
        $this->assertEquals(2, $validation['admin_count']); // admin + superadmin
        
        // Check that admin count issue is not present
        $this->assertNotContains(
            'No admin users with verified emails found to receive notifications',
            $validation['issues']
        );
    }

    public function test_validate_notification_configuration_detects_no_admin_users()
    {
        // Delete all admin users
        User::whereHas('role', function ($query) {
            $query->whereIn('name', ['admin', 'superadmin']);
        })->delete();

        $validation = $this->notificationService->validateNotificationConfiguration();

        $this->assertFalse($validation['success']);
        $this->assertContains(
            'No admin users with verified emails found to receive notifications',
            $validation['issues']
        );
        $this->assertEquals(0, $validation['admin_count']);
    }

    public function test_get_user_notification_preferences_returns_defaults()
    {
        $preferences = $this->notificationService->getUserNotificationPreferences($this->adminUser);

        $this->assertTrue($preferences['manifest_unlock']);
        $this->assertFalse($preferences['manifest_auto_close']);
        $this->assertTrue($preferences['email_notifications']);
        $this->assertTrue($preferences['database_notifications']);
    }

    public function test_update_user_notification_preferences_returns_success()
    {
        $newPreferences = [
            'manifest_unlock' => false,
            'email_notifications' => false,
        ];

        $result = $this->notificationService->updateUserNotificationPreferences(
            $this->adminUser,
            $newPreferences
        );

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('updated successfully', $result['message']);
    }
}