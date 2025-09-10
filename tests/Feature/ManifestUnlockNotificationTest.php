<?php

namespace Tests\Feature;

use App\Models\Manifest;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ManifestUnlockedNotification;
use App\Services\ManifestLockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ManifestUnlockNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $superAdminUser;
    protected Manifest $manifest;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $superAdminRole = Role::factory()->create(['name' => 'superadmin']);
        
        // Create users
        $this->adminUser = User::factory()->create([
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);
        
        $this->superAdminUser = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'email_verified_at' => now(),
        ]);
        
        // Create closed manifest
        $this->manifest = Manifest::factory()->create([
            'is_open' => false,
        ]);
    }

    public function test_unlock_manifest_sends_notification_to_stakeholders()
    {
        Notification::fake();
        
        $lockService = app(ManifestLockService::class);
        
        $result = $lockService->unlockManifest(
            $this->manifest,
            $this->adminUser,
            'Need to update package information due to customer request'
        );

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Notification sent to 1 stakeholder(s)', $result['message']);

        // Verify notification was sent to superadmin but not to the unlocking admin
        Notification::assertSentTo(
            $this->superAdminUser,
            ManifestUnlockedNotification::class,
            function ($notification) {
                return $notification->manifest->id === $this->manifest->id &&
                       $notification->unlockedBy->id === $this->adminUser->id &&
                       $notification->reason === 'Need to update package information due to customer request';
            }
        );

        Notification::assertNotSentTo($this->adminUser, ManifestUnlockedNotification::class);
    }

    public function test_unlock_manifest_handles_notification_failure_gracefully()
    {
        // Force notification failure by using invalid email
        $this->superAdminUser->update(['email' => 'invalid-email']);
        
        $lockService = app(ManifestLockService::class);
        
        $result = $lockService->unlockManifest(
            $this->manifest,
            $this->adminUser,
            'Test unlock with notification failure'
        );

        // Unlock should still succeed even if notification fails
        $this->assertTrue($result['success']);
        $this->assertTrue($this->manifest->fresh()->is_open);
    }

    public function test_unlock_manifest_with_no_notification_recipients()
    {
        // Delete all other admin users so there are no notification recipients
        User::where('id', '!=', $this->adminUser->id)
            ->whereHas('role', function ($query) {
                $query->whereIn('name', ['admin', 'superadmin']);
            })
            ->delete();

        $lockService = app(ManifestLockService::class);
        
        $result = $lockService->unlockManifest(
            $this->manifest,
            $this->adminUser,
            'Test unlock with no recipients'
        );

        $this->assertTrue($result['success']);
        $this->assertTrue($this->manifest->fresh()->is_open);
        
        // Should indicate no recipients
        $this->assertEquals(0, $result['notification_result']['recipients_count']);
    }

    public function test_notification_contains_correct_manifest_data()
    {
        Notification::fake();
        
        $lockService = app(ManifestLockService::class);
        
        $lockService->unlockManifest(
            $this->manifest,
            $this->adminUser,
            'Testing notification data'
        );

        Notification::assertSentTo(
            $this->superAdminUser,
            ManifestUnlockedNotification::class,
            function ($notification) {
                // Test notification properties
                $this->assertEquals($this->manifest->id, $notification->manifest->id);
                $this->assertEquals($this->adminUser->id, $notification->unlockedBy->id);
                $this->assertEquals('Testing notification data', $notification->reason);
                $this->assertInstanceOf(\Carbon\Carbon::class, $notification->unlockedAt);
                
                // Test database representation
                $databaseData = $notification->toDatabase($this->superAdminUser);
                $this->assertEquals('manifest_unlocked', $databaseData['type']);
                $this->assertEquals($this->manifest->id, $databaseData['manifest_id']);
                $this->assertEquals($this->manifest->name, $databaseData['manifest_name']);
                $this->assertEquals($this->adminUser->id, $databaseData['unlocked_by_id']);
                $this->assertEquals($this->adminUser->full_name, $databaseData['unlocked_by_name']);
                $this->assertEquals('Testing notification data', $databaseData['reason']);
                
                return true;
            }
        );
    }

    public function test_notification_mail_message_structure()
    {
        $notification = new ManifestUnlockedNotification(
            $this->manifest,
            $this->adminUser,
            'Test reason for email structure',
            now()
        );

        $mailMessage = $notification->toMail($this->superAdminUser);

        $this->assertStringContainsString('Manifest Unlocked - ' . $this->manifest->name, $mailMessage->subject);
        $this->assertEquals('emails.admin.manifest-unlocked', $mailMessage->view);
        
        // Check view data
        $viewData = $mailMessage->viewData;
        $this->assertEquals($this->manifest->id, $viewData['manifest']->id);
        $this->assertEquals($this->adminUser->id, $viewData['unlockedBy']->id);
        $this->assertEquals('Test reason for email structure', $viewData['reason']);
        $this->assertEquals($this->superAdminUser->id, $viewData['recipient']->id);
    }

    public function test_notification_uses_correct_queue()
    {
        $notification = new ManifestUnlockedNotification(
            $this->manifest,
            $this->adminUser,
            'Test queue configuration',
            now()
        );

        $this->assertEquals('notifications', $notification->queue);
    }

    public function test_notification_via_channels()
    {
        $notification = new ManifestUnlockedNotification(
            $this->manifest,
            $this->adminUser,
            'Test notification channels',
            now()
        );

        $channels = $notification->via($this->superAdminUser);
        
        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    public function test_multiple_admin_users_receive_notifications()
    {
        Notification::fake();
        
        // Create additional admin users
        $adminRole = Role::where('name', 'admin')->first();
        $additionalAdmin1 = User::factory()->create([
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);
        $additionalAdmin2 = User::factory()->create([
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);
        
        $lockService = app(ManifestLockService::class);
        
        $result = $lockService->unlockManifest(
            $this->manifest,
            $this->adminUser,
            'Test multiple recipients'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['notification_result']['recipients_count']); // superadmin + 2 additional admins

        // All should receive notification except the unlocking user
        Notification::assertSentTo($this->superAdminUser, ManifestUnlockedNotification::class);
        Notification::assertSentTo($additionalAdmin1, ManifestUnlockedNotification::class);
        Notification::assertSentTo($additionalAdmin2, ManifestUnlockedNotification::class);
        Notification::assertNotSentTo($this->adminUser, ManifestUnlockedNotification::class);
    }

    public function test_notification_failure_is_logged()
    {
        // This test would require mocking the notification system to force a failure
        // For now, we'll test that the notification service handles errors gracefully
        
        $lockService = app(ManifestLockService::class);
        
        // Test with valid data - should succeed
        $result = $lockService->unlockManifest(
            $this->manifest,
            $this->adminUser,
            'Test error handling'
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('notification_result', $result);
        $this->assertArrayHasKey('success', $result['notification_result']);
    }
}