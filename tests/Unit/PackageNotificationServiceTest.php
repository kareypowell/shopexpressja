<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PackageNotificationService;
use App\Models\Package;
use App\Models\User;
use App\Models\Role;
use App\Enums\PackageStatus;
use App\Notifications\PackageReadyNotification;
use App\Notifications\PackageShippedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

class PackageNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $notificationService;
    protected $user;
    protected $package;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->notificationService = new PackageNotificationService();
        
        // Create a customer role and user
        $customerRole = Role::where('name', 'customer')->first();
        $this->user = User::factory()->create(['role_id' => $customerRole->id]);
        
        // Create a package
        $this->package = Package::factory()->create([
            'user_id' => $this->user->id,
            'tracking_number' => 'TEST123',
            'description' => 'Test Package',
            'freight_price' => 50.00,
            'clearance_fee' => 10.00,
            'storage_fee' => 5.00,
            'delivery_fee' => 0.00,
        ]);
    }

    /** @test */
    public function it_sends_ready_notification_with_cost_information()
    {
        Notification::fake();

        $result = $this->notificationService->sendStatusNotification(
            $this->package, 
            PackageStatus::READY()
        );

        $this->assertTrue($result);
        
        Notification::assertSentTo(
            $this->user,
            PackageReadyNotification::class,
            function ($notification) {
                return $notification->tracking_number === 'TEST123' &&
                       $notification->description === 'Test Package' &&
                       $notification->package !== null;
            }
        );
    }

    /** @test */
    public function it_sends_shipped_notification()
    {
        Notification::fake();

        $result = $this->notificationService->sendStatusNotification(
            $this->package, 
            PackageStatus::SHIPPED()
        );

        $this->assertTrue($result);
        
        Notification::assertSentTo(
            $this->user,
            PackageShippedNotification::class,
            function ($notification) {
                return $notification->tracking_number === 'TEST123' &&
                       $notification->description === 'Test Package';
            }
        );
    }

    /** @test */
    public function it_handles_package_with_deleted_user()
    {
        // Create a package with a user, then delete the user to simulate orphaned package
        $tempUser = User::factory()->create(['role_id' => Role::where('name', 'customer')->first()->id]);
        $packageWithDeletedUser = Package::factory()->create(['user_id' => $tempUser->id]);
        
        // Delete the user
        $tempUser->delete();
        
        // Refresh the package to clear any cached relationships
        $packageWithDeletedUser->refresh();

        $result = $this->notificationService->sendStatusNotification(
            $packageWithDeletedUser, 
            PackageStatus::READY()
        );

        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_true_for_pending_status_without_sending_notification()
    {
        Notification::fake();

        $result = $this->notificationService->sendStatusNotification(
            $this->package, 
            PackageStatus::PENDING()
        );

        $this->assertTrue($result);
        
        Notification::assertNothingSent();
    }
}