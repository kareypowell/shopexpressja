<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ConsolidatedPackage;
use App\Models\Package;
use App\Models\PackageDistribution;
use App\Notifications\ConsolidatedPackageStatusNotification;
use App\Notifications\PackageConsolidationNotification;
use App\Notifications\PackageUnconsolidationNotification;
use App\Notifications\ConsolidatedPackageReadyNotification;
use App\Mail\ConsolidatedPackageReceiptEmail;
use App\Mail\ConsolidatedPackageReadyEmail;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Collection;

class ConsolidatedPackageNotificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $consolidatedPackage;
    protected $individualPackages;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user with profile
        $this->user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com'
        ]);
        
        $this->user->profile()->create([
            'account_number' => 'ACC123456',
            'telephone_number' => '+1-555-123-4567',
            'tax_number' => 'TAX123456',
            'street_address' => '123 Test Street',
            'city_town' => 'Kingston',
            'parish' => 'St. Andrew'
        ]);

        // Create consolidated package
        $this->consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->user->id,
            'consolidated_tracking_number' => 'CONS-20241208-0001',
            'total_weight' => 15.50,
            'total_quantity' => 3,
            'total_freight_price' => 45.00,
            'total_clearance_fee' => 12.50,
            'total_storage_fee' => 8.00,
            'total_delivery_fee' => 15.00,
            'status' => PackageStatus::PROCESSING,
        ]);

        // Create individual packages
        $this->individualPackages = collect([
            Package::factory()->create([
                'user_id' => $this->user->id,
                'consolidated_package_id' => $this->consolidatedPackage->id,
                'tracking_number' => 'PKG001',
                'description' => 'Electronics',
                'weight' => 5.25,
                'is_consolidated' => true,
            ]),
            Package::factory()->create([
                'user_id' => $this->user->id,
                'consolidated_package_id' => $this->consolidatedPackage->id,
                'tracking_number' => 'PKG002',
                'description' => 'Clothing',
                'weight' => 3.75,
                'is_consolidated' => true,
            ]),
            Package::factory()->create([
                'user_id' => $this->user->id,
                'consolidated_package_id' => $this->consolidatedPackage->id,
                'tracking_number' => 'PKG003',
                'description' => 'Books',
                'weight' => 6.50,
                'is_consolidated' => true,
            ]),
        ]);

        // Associate packages with consolidated package
        $this->consolidatedPackage->setRelation('packages', $this->individualPackages);
    }

    /** @test */
    public function consolidated_package_status_notification_can_be_sent()
    {
        Notification::fake();

        // Send notification
        $this->user->notify(new ConsolidatedPackageStatusNotification(
            $this->user,
            $this->consolidatedPackage,
            PackageStatus::READY()
        ));

        // Assert notification was sent
        Notification::assertSentTo(
            $this->user,
            ConsolidatedPackageStatusNotification::class,
            function ($notification) {
                return $notification->consolidatedPackage->id === $this->consolidatedPackage->id
                    && $notification->newStatus->value === PackageStatus::READY;
            }
        );
    }

    /** @test */
    public function package_consolidation_notification_can_be_sent()
    {
        Notification::fake();

        // Send notification
        $this->user->notify(new PackageConsolidationNotification($this->user, $this->consolidatedPackage));

        // Assert notification was sent
        Notification::assertSentTo(
            $this->user,
            PackageConsolidationNotification::class,
            function ($notification) {
                return $notification->consolidatedPackage->id === $this->consolidatedPackage->id;
            }
        );
    }

    /** @test */
    public function package_unconsolidation_notification_can_be_sent()
    {
        Notification::fake();

        // Send notification
        $this->user->notify(new PackageUnconsolidationNotification(
            $this->user,
            $this->individualPackages,
            'CONS-20241208-0001'
        ));

        // Assert notification was sent
        Notification::assertSentTo(
            $this->user,
            PackageUnconsolidationNotification::class,
            function ($notification) {
                return $notification->formerConsolidatedTrackingNumber === 'CONS-20241208-0001'
                    && $notification->packages->count() === 3;
            }
        );
    }

    /** @test */
    public function consolidated_package_ready_notification_can_be_sent()
    {
        Notification::fake();

        // Send notification
        $this->user->notify(new ConsolidatedPackageReadyNotification(
            $this->user,
            $this->consolidatedPackage,
            true,
            'Please bring ID'
        ));

        // Assert notification was sent
        Notification::assertSentTo(
            $this->user,
            ConsolidatedPackageReadyNotification::class,
            function ($notification) {
                return $notification->consolidatedPackage->id === $this->consolidatedPackage->id
                    && $notification->showCosts === true
                    && $notification->specialInstructions === 'Please bring ID';
            }
        );
    }

    /** @test */
    public function consolidated_package_receipt_email_can_be_sent()
    {
        Mail::fake();

        // Create a package distribution
        $distribution = PackageDistribution::factory()->create([
            'customer_id' => $this->user->id,
            'receipt_number' => 'RCP-20241208-001',
            'total_amount' => 80.50,
            'amount_collected' => 80.50,
            'payment_status' => 'paid',
            'distributed_at' => now(),
        ]);

        // Send email
        Mail::to($this->user)->send(new ConsolidatedPackageReceiptEmail(
            $distribution,
            $this->user,
            $this->consolidatedPackage
        ));

        // Assert email was queued (since it implements ShouldQueue)
        Mail::assertQueued(ConsolidatedPackageReceiptEmail::class, function ($mail) use ($distribution) {
            return $mail->distribution->id === $distribution->id
                && $mail->customer->id === $this->user->id
                && $mail->consolidatedPackage->id === $this->consolidatedPackage->id;
        });
    }

    /** @test */
    public function consolidated_package_ready_email_can_be_sent()
    {
        Mail::fake();

        // Send email
        Mail::to($this->user)->send(new ConsolidatedPackageReadyEmail(
            $this->user,
            $this->consolidatedPackage,
            true,
            'Bring valid ID'
        ));

        // Assert email was queued (since it implements ShouldQueue)
        Mail::assertQueued(ConsolidatedPackageReadyEmail::class, function ($mail) {
            return $mail->user->id === $this->user->id
                && $mail->consolidatedPackage->id === $this->consolidatedPackage->id
                && $mail->showCosts === true
                && $mail->specialInstructions === 'Bring valid ID';
        });
    }

    /** @test */
    public function notification_array_representation_contains_correct_data()
    {
        // Test ConsolidatedPackageStatusNotification
        $statusNotification = new ConsolidatedPackageStatusNotification(
            $this->user,
            $this->consolidatedPackage,
            PackageStatus::READY()
        );

        $arrayData = $statusNotification->toArray($this->user);

        $this->assertArrayHasKey('consolidated_package_id', $arrayData);
        $this->assertArrayHasKey('consolidated_tracking_number', $arrayData);
        $this->assertArrayHasKey('new_status', $arrayData);
        $this->assertArrayHasKey('individual_packages_count', $arrayData);

        $this->assertEquals($this->consolidatedPackage->id, $arrayData['consolidated_package_id']);
        $this->assertEquals('CONS-20241208-0001', $arrayData['consolidated_tracking_number']);
        $this->assertEquals(PackageStatus::READY, $arrayData['new_status']);
        $this->assertEquals(3, $arrayData['individual_packages_count']);

        // Test PackageConsolidationNotification
        $consolidationNotification = new PackageConsolidationNotification($this->user, $this->consolidatedPackage);
        $arrayData = $consolidationNotification->toArray($this->user);

        $this->assertArrayHasKey('consolidated_package_id', $arrayData);
        $this->assertArrayHasKey('consolidated_tracking_number', $arrayData);
        $this->assertArrayHasKey('individual_packages_count', $arrayData);
        $this->assertArrayHasKey('individual_tracking_numbers', $arrayData);

        $this->assertEquals($this->consolidatedPackage->id, $arrayData['consolidated_package_id']);
        $this->assertEquals(3, $arrayData['individual_packages_count']);
        $this->assertContains('PKG001', $arrayData['individual_tracking_numbers']);
        $this->assertContains('PKG002', $arrayData['individual_tracking_numbers']);
        $this->assertContains('PKG003', $arrayData['individual_tracking_numbers']);

        // Test PackageUnconsolidationNotification
        $unconsolidationNotification = new PackageUnconsolidationNotification(
            $this->user,
            $this->individualPackages,
            'CONS-20241208-0001'
        );
        $arrayData = $unconsolidationNotification->toArray($this->user);

        $this->assertArrayHasKey('former_consolidated_tracking_number', $arrayData);
        $this->assertArrayHasKey('individual_packages_count', $arrayData);
        $this->assertArrayHasKey('individual_tracking_numbers', $arrayData);

        $this->assertEquals('CONS-20241208-0001', $arrayData['former_consolidated_tracking_number']);
        $this->assertEquals(3, $arrayData['individual_packages_count']);
        $this->assertContains('PKG001', $arrayData['individual_tracking_numbers']);

        // Test ConsolidatedPackageReadyNotification
        $readyNotification = new ConsolidatedPackageReadyNotification(
            $this->user,
            $this->consolidatedPackage,
            true,
            'Special instructions'
        );
        $arrayData = $readyNotification->toArray($this->user);

        $this->assertArrayHasKey('consolidated_package_id', $arrayData);
        $this->assertArrayHasKey('consolidated_tracking_number', $arrayData);
        $this->assertArrayHasKey('individual_packages_count', $arrayData);
        $this->assertArrayHasKey('show_costs', $arrayData);
        $this->assertArrayHasKey('special_instructions', $arrayData);

        $this->assertEquals($this->consolidatedPackage->id, $arrayData['consolidated_package_id']);
        $this->assertTrue($arrayData['show_costs']);
        $this->assertEquals('Special instructions', $arrayData['special_instructions']);
    }

    /** @test */
    public function notifications_handle_different_status_types_correctly()
    {
        $statuses = [
            PackageStatus::PROCESSING() => 'Processing Update',
            PackageStatus::SHIPPED() => 'Shipped',
            PackageStatus::CUSTOMS() => 'In Customs',
            PackageStatus::READY() => 'Ready for Pickup',
            PackageStatus::DELIVERED() => 'Delivered',
            PackageStatus::DELAYED() => 'Delayed',
        ];

        foreach ($statuses as $status => $expectedTitle) {
            $notification = new ConsolidatedPackageStatusNotification(
                $this->user,
                $this->consolidatedPackage,
                $status
            );

            $mailMessage = $notification->toMail($this->user);
            
            $this->assertEquals("Consolidated Package {$expectedTitle}", $mailMessage->subject);
            $this->assertEquals($expectedTitle, $mailMessage->viewData['statusTitle']);
        }
    }

    /** @test */
    public function mail_classes_handle_queue_configuration_correctly()
    {
        // Test ConsolidatedPackageReceiptEmail
        $distribution = PackageDistribution::factory()->create([
            'customer_id' => $this->user->id,
        ]);

        $receiptEmail = new ConsolidatedPackageReceiptEmail($distribution, $this->user, $this->consolidatedPackage);
        
        $this->assertEquals('emails', $receiptEmail->queue);
        $this->assertNotNull($receiptEmail->delay);

        // Test ConsolidatedPackageReadyEmail
        $readyEmail = new ConsolidatedPackageReadyEmail($this->user, $this->consolidatedPackage);
        
        $this->assertEquals('emails', $readyEmail->queue);
        $this->assertNotNull($readyEmail->delay);
    }

    /** @test */
    public function mail_classes_handle_missing_relationships_gracefully()
    {
        // Create user without profile
        $userWithoutProfile = User::factory()->create();

        // Create consolidated package without packages
        $emptyConsolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $userWithoutProfile->id,
        ]);

        // Should not throw exceptions
        $distribution = PackageDistribution::factory()->create([
            'customer_id' => $userWithoutProfile->id,
        ]);

        $receiptEmail = new ConsolidatedPackageReceiptEmail($distribution, $userWithoutProfile, $emptyConsolidatedPackage);
        $builtEmail = $receiptEmail->build();

        $this->assertNotNull($builtEmail);
        $this->assertEquals('emails.packages.consolidated-receipt', $builtEmail->view);

        $readyEmail = new ConsolidatedPackageReadyEmail($userWithoutProfile, $emptyConsolidatedPackage);
        $builtEmail = $readyEmail->build();

        $this->assertNotNull($builtEmail);
        $this->assertEquals('emails.packages.consolidated-package-ready', $builtEmail->view);
    }

    /** @test */
    public function notification_templates_are_accessible_and_renderable()
    {
        $templates = [
            'emails.packages.consolidated-package-status',
            'emails.packages.package-consolidation',
            'emails.packages.package-unconsolidation',
            'emails.packages.consolidated-package-ready',
            'emails.packages.consolidated-receipt',
        ];

        foreach ($templates as $template) {
            $this->assertTrue(
                view()->exists($template),
                "Template {$template} does not exist"
            );
        }
    }
}