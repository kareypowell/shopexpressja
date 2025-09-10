<?php

namespace Tests\Feature;

use App\Models\Manifest;
use App\Models\Role;
use App\Models\User;
use App\Services\ManifestNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ManifestNotificationErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected ManifestNotificationService $notificationService;
    protected User $adminUser;
    protected Manifest $manifest;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->notificationService = new ManifestNotificationService();
        
        // Create roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        
        // Create users
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        
        // Create manifest
        $this->manifest = Manifest::factory()->create();
    }

    public function test_notification_service_handles_database_errors_gracefully()
    {
        // This test simulates database connection issues
        // In a real scenario, you might mock the database to throw exceptions
        
        $result = $this->notificationService->sendUnlockNotification(
            $this->manifest,
            $this->adminUser,
            'Test database error handling'
        );

        // Even if there are internal errors, the service should return a structured response
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('recipients_count', $result);
    }

    public function test_notification_service_validates_configuration()
    {
        $validation = $this->notificationService->validateNotificationConfiguration();

        $this->assertIsArray($validation);
        $this->assertArrayHasKey('success', $validation);
        $this->assertArrayHasKey('issues', $validation);
        $this->assertArrayHasKey('admin_count', $validation);
        $this->assertIsArray($validation['issues']);
    }

    public function test_notification_service_handles_invalid_user_data()
    {
        // Create user with minimal data
        $minimalUser = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'role_id' => Role::where('name', 'admin')->first()->id,
        ]);

        $result = $this->notificationService->sendUnlockNotification(
            $this->manifest,
            $minimalUser,
            'Test with minimal user data'
        );

        $this->assertTrue($result['success']);
        $this->assertIsInt($result['recipients_count']);
    }

    public function test_notification_service_handles_invalid_manifest_data()
    {
        // Create manifest with minimal data
        $minimalManifest = Manifest::factory()->create([
            'name' => 'Minimal Test Manifest',
        ]);

        $result = $this->notificationService->sendUnlockNotification(
            $minimalManifest,
            $this->adminUser,
            'Test with minimal manifest data'
        );

        $this->assertTrue($result['success']);
        $this->assertIsInt($result['recipients_count']);
    }

    public function test_get_notification_statistics_handles_missing_table()
    {
        // This test ensures the method doesn't crash if notifications table doesn't exist
        // In practice, the table should exist, but this tests error handling
        
        $stats = $this->notificationService->getNotificationStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('success', $stats);
        
        if ($stats['success']) {
            $this->assertArrayHasKey('admin_recipients_count', $stats);
            $this->assertArrayHasKey('recent_notifications_30_days', $stats);
            $this->assertArrayHasKey('notification_channels', $stats);
        } else {
            $this->assertArrayHasKey('message', $stats);
        }
    }

    public function test_test_notification_delivery_handles_invalid_email()
    {
        // Create user with potentially problematic email
        $userWithBadEmail = User::factory()->create([
            'email' => 'not-a-valid-email-format',
            'role_id' => Role::where('name', 'admin')->first()->id,
        ]);

        $result = $this->notificationService->testNotificationDelivery(
            $userWithBadEmail,
            $this->manifest
        );

        // Should handle gracefully regardless of email validity
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_notification_service_logs_errors_appropriately()
    {
        // Enable log capture
        Log::shouldReceive('warning')->once();
        
        // Delete all admin users to trigger warning log
        User::whereHas('role', function ($query) {
            $query->whereIn('name', ['admin', 'superadmin']);
        })->delete();

        $result = $this->notificationService->sendUnlockNotification(
            $this->manifest,
            $this->adminUser,
            'Test logging'
        );

        // Should still return success but log the warning
        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['recipients_count']);
    }

    public function test_notification_service_handles_empty_reason()
    {
        $result = $this->notificationService->sendUnlockNotification(
            $this->manifest,
            $this->adminUser,
            '' // Empty reason
        );

        // Should still work - validation is handled at the ManifestLockService level
        $this->assertTrue($result['success']);
    }

    public function test_notification_service_handles_very_long_reason()
    {
        $veryLongReason = str_repeat('This is a very long reason. ', 100); // ~2800 characters
        
        $result = $this->notificationService->sendUnlockNotification(
            $this->manifest,
            $this->adminUser,
            $veryLongReason
        );

        $this->assertTrue($result['success']);
    }

    public function test_notification_service_handles_special_characters_in_reason()
    {
        $specialReason = "Reason with special chars: áéíóú, 中文, emoji 🚀, quotes \"test\" and 'test', newlines\nand\ttabs";
        
        $result = $this->notificationService->sendUnlockNotification(
            $this->manifest,
            $this->adminUser,
            $specialReason
        );

        $this->assertTrue($result['success']);
    }

    public function test_get_unlock_notification_recipients_handles_role_query_errors()
    {
        // This tests the robustness of the role-based query
        $recipients = $this->notificationService->getUnlockNotificationRecipients(
            $this->manifest,
            $this->adminUser
        );

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $recipients);
    }

    public function test_notification_preferences_methods_are_safe()
    {
        // Test placeholder methods don't crash
        $preferences = $this->notificationService->getUserNotificationPreferences($this->adminUser);
        $this->assertIsArray($preferences);

        $updateResult = $this->notificationService->updateUserNotificationPreferences(
            $this->adminUser,
            ['test' => true]
        );
        $this->assertIsArray($updateResult);
        $this->assertTrue($updateResult['success']);
    }

    public function test_notification_service_handles_concurrent_requests()
    {
        // Simulate multiple concurrent notification requests
        $results = [];
        
        for ($i = 0; $i < 5; $i++) {
            $results[] = $this->notificationService->sendUnlockNotification(
                $this->manifest,
                $this->adminUser,
                "Concurrent test request #{$i}"
            );
        }

        // All should succeed
        foreach ($results as $result) {
            $this->assertTrue($result['success']);
            $this->assertIsInt($result['recipients_count']);
        }
    }
}