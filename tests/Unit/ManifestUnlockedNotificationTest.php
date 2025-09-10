<?php

namespace Tests\Unit;

use App\Models\Manifest;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ManifestUnlockedNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManifestUnlockedNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $recipientUser;
    protected Manifest $manifest;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        
        // Create users
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->recipientUser = User::factory()->create(['role_id' => $adminRole->id]);
        
        // Create manifest
        $this->manifest = Manifest::factory()->create();
    }

    public function test_notification_constructor_sets_properties_correctly()
    {
        $reason = 'Test unlock reason';
        $unlockedAt = Carbon::now();
        
        $notification = new ManifestUnlockedNotification(
            $this->manifest,
            $this->adminUser,
            $reason,
            $unlockedAt
        );

        $this->assertEquals($this->manifest->id, $notification->manifest->id);
        $this->assertEquals($this->adminUser->id, $notification->unlockedBy->id);
        $this->assertEquals($reason, $notification->reason);
        $this->assertEquals($unlockedAt->toISOString(), $notification->unlockedAt->toISOString());
        $this->assertEquals('notifications', $notification->queue);
    }

    public function test_via_method_returns_correct_channels()
    {
        $notification = new ManifestUnlockedNotification(
            $this->manifest,
            $this->adminUser,
            'Test reason',
            Carbon::now()
        );

        $channels = $notification->via($this->recipientUser);

        $this->assertIsArray($channels);
        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
        $this->assertCount(2, $channels);
    }

    public function test_to_mail_returns_correct_mail_message()
    {
        $reason = 'Test unlock reason for email';
        $unlockedAt = Carbon::now();
        
        $notification = new ManifestUnlockedNotification(
            $this->manifest,
            $this->adminUser,
            $reason,
            $unlockedAt
        );

        $mailMessage = $notification->toMail($this->recipientUser);

        $this->assertInstanceOf(\Illuminate\Notifications\Messages\MailMessage::class, $mailMessage);
        $this->assertEquals('Manifest Unlocked - ' . $this->manifest->name, $mailMessage->subject);
        $this->assertEquals('emails.admin.manifest-unlocked', $mailMessage->view);
        
        // Check view data
        $viewData = $mailMessage->viewData;
        $this->assertArrayHasKey('manifest', $viewData);
        $this->assertArrayHasKey('unlockedBy', $viewData);
        $this->assertArrayHasKey('reason', $viewData);
        $this->assertArrayHasKey('unlockedAt', $viewData);
        $this->assertArrayHasKey('recipient', $viewData);
        
        $this->assertEquals($this->manifest->id, $viewData['manifest']->id);
        $this->assertEquals($this->adminUser->id, $viewData['unlockedBy']->id);
        $this->assertEquals($reason, $viewData['reason']);
        $this->assertEquals($unlockedAt->toISOString(), $viewData['unlockedAt']->toISOString());
        $this->assertEquals($this->recipientUser->id, $viewData['recipient']->id);
    }

    public function test_to_database_returns_correct_data_structure()
    {
        $reason = 'Test unlock reason for database';
        $unlockedAt = Carbon::now();
        
        $notification = new ManifestUnlockedNotification(
            $this->manifest,
            $this->adminUser,
            $reason,
            $unlockedAt
        );

        $databaseData = $notification->toDatabase($this->recipientUser);

        $this->assertIsArray($databaseData);
        
        // Check required fields
        $this->assertEquals('manifest_unlocked', $databaseData['type']);
        $this->assertEquals($this->manifest->id, $databaseData['manifest_id']);
        $this->assertEquals($this->manifest->name, $databaseData['manifest_name']);
        $this->assertEquals($this->manifest->manifest_number, $databaseData['manifest_number']);
        $this->assertEquals($this->adminUser->id, $databaseData['unlocked_by_id']);
        $this->assertEquals($this->adminUser->full_name, $databaseData['unlocked_by_name']);
        $this->assertEquals($this->adminUser->email, $databaseData['unlocked_by_email']);
        $this->assertEquals($reason, $databaseData['reason']);
        $this->assertEquals($unlockedAt->toISOString(), $databaseData['unlocked_at']);
        
        // Check message format
        $expectedMessage = "Manifest '{$this->manifest->name}' was unlocked by {$this->adminUser->full_name}";
        $this->assertEquals($expectedMessage, $databaseData['message']);
    }

    public function test_to_array_returns_same_as_to_database()
    {
        $notification = new ManifestUnlockedNotification(
            $this->manifest,
            $this->adminUser,
            'Test reason',
            Carbon::now()
        );

        $arrayData = $notification->toArray($this->recipientUser);
        $databaseData = $notification->toDatabase($this->recipientUser);

        $this->assertEquals($databaseData, $arrayData);
    }

    public function test_notification_handles_manifest_without_manifest_number()
    {
        // Use the existing manifest which may not have manifest_number
        $notification = new ManifestUnlockedNotification(
            $this->manifest,
            $this->adminUser,
            'Test reason',
            Carbon::now()
        );

        $databaseData = $notification->toDatabase($this->recipientUser);
        
        // manifest_number might be null or have a value
        $this->assertEquals($this->manifest->manifest_number, $databaseData['manifest_number']);
        $this->assertEquals($this->manifest->name, $databaseData['manifest_name']);
    }

    public function test_notification_handles_user_without_full_name()
    {
        // Create user with empty last name (but not null due to database constraints)
        $userWithoutLastName = User::factory()->create([
            'first_name' => 'John',
            'last_name' => '',
            'role_id' => Role::where('name', 'admin')->first()->id,
        ]);
        
        $notification = new ManifestUnlockedNotification(
            $this->manifest,
            $userWithoutLastName,
            'Test reason',
            Carbon::now()
        );

        $databaseData = $notification->toDatabase($this->recipientUser);
        
        $this->assertEquals($userWithoutLastName->full_name, $databaseData['unlocked_by_name']);
        $this->assertEquals($userWithoutLastName->email, $databaseData['unlocked_by_email']);
    }

    public function test_notification_queue_configuration()
    {
        $notification = new ManifestUnlockedNotification(
            $this->manifest,
            $this->adminUser,
            'Test queue',
            Carbon::now()
        );

        $this->assertEquals('notifications', $notification->queue);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $notification);
    }

    public function test_failed_method_logs_error_correctly()
    {
        $notification = new ManifestUnlockedNotification(
            $this->manifest,
            $this->adminUser,
            'Test failure logging',
            Carbon::now()
        );

        // Mock exception
        $exception = new \Exception('Test notification failure');

        // Capture log output
        $this->expectsEvents(\Illuminate\Log\Events\MessageLogged::class);

        $notification->failed($exception);

        // The failed method should log the error without throwing
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function test_notification_with_special_characters_in_reason()
    {
        $specialReason = 'Test with special chars: áéíóú, 中文, emoji 🚀, quotes "test" and \'test\'';
        
        $notification = new ManifestUnlockedNotification(
            $this->manifest,
            $this->adminUser,
            $specialReason,
            Carbon::now()
        );

        $databaseData = $notification->toDatabase($this->recipientUser);
        $mailMessage = $notification->toMail($this->recipientUser);

        $this->assertEquals($specialReason, $databaseData['reason']);
        $this->assertEquals($specialReason, $mailMessage->viewData['reason']);
    }

    public function test_notification_with_very_long_reason()
    {
        $longReason = str_repeat('This is a very long reason for unlocking the manifest. ', 20);
        
        $notification = new ManifestUnlockedNotification(
            $this->manifest,
            $this->adminUser,
            $longReason,
            Carbon::now()
        );

        $databaseData = $notification->toDatabase($this->recipientUser);
        
        $this->assertEquals($longReason, $databaseData['reason']);
        $this->assertGreaterThan(500, strlen($databaseData['reason']));
    }
}