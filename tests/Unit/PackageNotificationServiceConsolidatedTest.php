<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PackageNotificationService;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\User;
use App\Enums\PackageStatus;
use App\Notifications\ConsolidatedPackageStatusNotification;
use App\Notifications\PackageConsolidationNotification;
use App\Notifications\PackageUnconsolidationNotification;
use App\Notifications\PackageReadyNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Collection;

class PackageNotificationServiceConsolidatedTest extends TestCase
{
    use RefreshDatabase;

    private PackageNotificationService $service;
    private User $customer;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new PackageNotificationService();
        
        $this->customer = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com'
        ]);
        
        $this->admin = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com'
        ]);

        Notification::fake();
    }

    /** @test */
    public function it_sends_consolidated_status_notification_for_consolidated_packages()
    {
        // Create consolidated package with individual packages
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->admin->id,
            'consolidated_tracking_number' => 'CONS-20241208-0001',
            'status' => 'processing',
        ]);

        $package1 = Package::factory()->create([
            'user_id' => $this->customer->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true,
            'status' => 'processing',
        ]);

        $package2 = Package::factory()->create([
            'user_id' => $this->customer->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true,
            'status' => 'processing',
        ]);

        // Test that individual package status notification redirects to consolidated notification
        $result = $this->service->sendStatusNotification($package1, PackageStatus::READY());

        $this->assertTrue($result);
        
        Notification::assertSentTo(
            $this->customer,
            ConsolidatedPackageStatusNotification::class,
            function ($notification) use ($consolidatedPackage) {
                return $notification->consolidatedPackage->id === $consolidatedPackage->id &&
                       $notification->newStatus->value === 'ready';
            }
        );
    }

    /** @test */
    public function it_sends_individual_notification_for_non_consolidated_packages()
    {
        $package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'is_consolidated' => false,
            'status' => 'processing',
        ]);

        $result = $this->service->sendStatusNotification($package, PackageStatus::READY());

        $this->assertTrue($result);
        
        Notification::assertSentTo(
            $this->customer,
            PackageReadyNotification::class
        );

        Notification::assertNotSentTo(
            $this->customer,
            ConsolidatedPackageStatusNotification::class
        );
    }

    /** @test */
    public function it_sends_consolidated_status_notification_directly()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->admin->id,
            'status' => 'processing',
        ]);

        Package::factory()->count(3)->create([
            'user_id' => $this->customer->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true,
        ]);

        $result = $this->service->sendConsolidatedStatusNotification($consolidatedPackage, PackageStatus::READY());

        $this->assertTrue($result);
        
        Notification::assertSentTo(
            $this->customer,
            ConsolidatedPackageStatusNotification::class,
            function ($notification) use ($consolidatedPackage) {
                return $notification->consolidatedPackage->id === $consolidatedPackage->id &&
                       $notification->newStatus->value === 'ready' &&
                       $notification->user->id === $this->customer->id;
            }
        );
    }

    /** @test */
    public function it_does_not_send_consolidated_notification_for_non_notifiable_statuses()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'pending',
        ]);

        $result = $this->service->sendConsolidatedStatusNotification($consolidatedPackage, PackageStatus::PENDING());

        $this->assertTrue($result); // Returns true but doesn't send notification
        
        Notification::assertNotSentTo(
            $this->customer,
            ConsolidatedPackageStatusNotification::class
        );
    }

    /** @test */
    public function it_sends_consolidation_notification()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->admin->id,
        ]);

        Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true,
        ]);

        $result = $this->service->sendConsolidationNotification($consolidatedPackage);

        $this->assertTrue($result);
        
        Notification::assertSentTo(
            $this->customer,
            PackageConsolidationNotification::class,
            function ($notification) use ($consolidatedPackage) {
                return $notification->consolidatedPackage->id === $consolidatedPackage->id &&
                       $notification->user->id === $this->customer->id;
            }
        );
    }

    /** @test */
    public function it_sends_unconsolidation_notification()
    {
        $package1 = Package::factory()->create([
            'user_id' => $this->customer->id,
            'tracking_number' => 'PKG001',
            'is_consolidated' => false,
        ]);

        $package2 = Package::factory()->create([
            'user_id' => $this->customer->id,
            'tracking_number' => 'PKG002',
            'is_consolidated' => false,
        ]);

        $packages = collect([$package1, $package2]);
        $formerTrackingNumber = 'CONS-20241208-0001';

        $result = $this->service->sendUnconsolidationNotification($packages, $this->customer, $formerTrackingNumber);

        $this->assertTrue($result);
        
        Notification::assertSentTo(
            $this->customer,
            PackageUnconsolidationNotification::class,
            function ($notification) use ($packages, $formerTrackingNumber) {
                return $notification->packages->count() === $packages->count() &&
                       $notification->formerConsolidatedTrackingNumber === $formerTrackingNumber &&
                       $notification->user->id === $this->customer->id;
            }
        );
    }

    /** @test */
    public function it_handles_consolidated_notification_without_customer()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        // Delete the customer to simulate missing customer
        $this->customer->delete();

        $result = $this->service->sendConsolidatedStatusNotification($consolidatedPackage, PackageStatus::READY());

        $this->assertFalse($result);
        
        Notification::assertNothingSent();
    }

    /** @test */
    public function it_handles_consolidation_notification_without_customer()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        // Delete the customer to simulate missing customer
        $this->customer->delete();

        $result = $this->service->sendConsolidationNotification($consolidatedPackage);

        $this->assertFalse($result);
        
        Notification::assertNothingSent();
    }

    /** @test */
    public function it_handles_unconsolidation_notification_with_empty_packages()
    {
        $packages = collect([]);

        $result = $this->service->sendUnconsolidationNotification($packages, $this->customer, 'CONS-123');

        $this->assertFalse($result);
        
        Notification::assertNothingSent();
    }

    /** @test */
    public function it_sends_notifications_for_all_notifiable_consolidated_statuses()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $notifiableStatuses = [
            PackageStatus::PROCESSING(),
            PackageStatus::SHIPPED(),
            PackageStatus::CUSTOMS(),
            PackageStatus::READY(),
            PackageStatus::DELIVERED(),
            PackageStatus::DELAYED(),
        ];

        foreach ($notifiableStatuses as $status) {
            Notification::fake(); // Reset notifications for each test
            
            $result = $this->service->sendConsolidatedStatusNotification($consolidatedPackage, $status);
            
            $this->assertTrue($result, "Failed to send notification for status: {$status->value}");
            
            Notification::assertSentTo(
                $this->customer,
                ConsolidatedPackageStatusNotification::class,
                function ($notification) use ($status) {
                    return $notification->newStatus->value === $status->value;
                }
            );
        }
    }

    /** @test */
    public function it_handles_notification_exceptions_gracefully()
    {
        // Create a consolidated package that will cause an exception
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        // Create a mock customer that will throw an exception when notify is called
        $mockCustomer = \Mockery::mock($this->customer);
        $mockCustomer->shouldReceive('notify')
            ->andThrow(new \Exception('Notification service unavailable'));

        // Replace the customer relationship
        $consolidatedPackage->setRelation('customer', $mockCustomer);

        $result = $this->service->sendConsolidatedStatusNotification($consolidatedPackage, PackageStatus::READY());

        $this->assertFalse($result);
    }
}