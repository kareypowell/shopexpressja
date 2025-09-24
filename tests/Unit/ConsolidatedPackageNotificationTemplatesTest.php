<?php

namespace Tests\Unit;

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
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Collection;

class ConsolidatedPackageNotificationTemplatesTest extends TestCase
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
            'status' => PackageStatus::READY,
        ]);

        // Create individual packages
        $this->individualPackages = collect([
            Package::factory()->create([
                'user_id' => $this->user->id,
                'consolidated_package_id' => $this->consolidatedPackage->id,
                'tracking_number' => 'PKG001',
                'description' => 'Electronics',
                'weight' => 5.25,
                'freight_price' => 15.00,
                'clearance_fee' => 4.50,
                'storage_fee' => 3.00,
                'delivery_fee' => 5.00,
                'is_consolidated' => true,
            ]),
            Package::factory()->create([
                'user_id' => $this->user->id,
                'consolidated_package_id' => $this->consolidatedPackage->id,
                'tracking_number' => 'PKG002',
                'description' => 'Clothing',
                'weight' => 3.75,
                'freight_price' => 12.00,
                'clearance_fee' => 3.00,
                'storage_fee' => 2.50,
                'delivery_fee' => 4.00,
                'is_consolidated' => true,
            ]),
            Package::factory()->create([
                'user_id' => $this->user->id,
                'consolidated_package_id' => $this->consolidatedPackage->id,
                'tracking_number' => 'PKG003',
                'description' => 'Books',
                'weight' => 6.50,
                'freight_price' => 18.00,
                'clearance_fee' => 5.00,
                'storage_fee' => 2.50,
                'delivery_fee' => 6.00,
                'is_consolidated' => true,
            ]),
        ]);

        // Associate packages with consolidated package
        $this->consolidatedPackage->setRelation('packages', $this->individualPackages);
    }

    /** @test */
    public function consolidated_package_status_notification_template_renders_correctly()
    {
        $notification = new ConsolidatedPackageStatusNotification(
            $this->user,
            $this->consolidatedPackage,
            PackageStatus::READY()
        );

        $mailMessage = $notification->toMail($this->user);

        // Check that the notification uses the correct view
        $this->assertEquals('emails.packages.consolidated-package-status', $mailMessage->view);
        
        // Check subject
        $this->assertEquals('Consolidated Package Ready for Pickup', $mailMessage->subject);

        // Check that required data is passed to the view
        $viewData = $mailMessage->viewData;
        $this->assertArrayHasKey('user', $viewData);
        $this->assertArrayHasKey('consolidatedPackage', $viewData);
        $this->assertArrayHasKey('newStatus', $viewData);
        $this->assertArrayHasKey('statusTitle', $viewData);
        $this->assertArrayHasKey('individualPackages', $viewData);

        // Verify data content
        $this->assertEquals($this->user->id, $viewData['user']->id);
        $this->assertEquals($this->consolidatedPackage->id, $viewData['consolidatedPackage']->id);
        $this->assertEquals(PackageStatus::READY()->value, $viewData['newStatus']->value);
        $this->assertEquals('Ready for Pickup', $viewData['statusTitle']);
        $this->assertCount(3, $viewData['individualPackages']);
    }

    /** @test */
    public function consolidated_package_status_template_contains_required_content()
    {
        $viewData = [
            'user' => $this->user,
            'consolidatedPackage' => $this->consolidatedPackage,
            'newStatus' => PackageStatus::READY(),
            'statusTitle' => 'Ready for Pickup',
            'individualPackages' => $this->individualPackages,
        ];

        $renderedView = View::make('emails.packages.consolidated-package-status', $viewData)->render();

        // Check for essential content
        $this->assertStringContainsString('Hi John,', $renderedView);
        $this->assertStringContainsString('CONS-20241208-0001', $renderedView);
        $this->assertStringContainsString('Ready for Pickup', $renderedView);
        $this->assertStringContainsString('3 packages', $renderedView);
        $this->assertStringContainsString('15.50 lbs', $renderedView);
        
        // Check individual package details
        $this->assertStringContainsString('PKG001', $renderedView);
        $this->assertStringContainsString('PKG002', $renderedView);
        $this->assertStringContainsString('PKG003', $renderedView);
        $this->assertStringContainsString('Electronics', $renderedView);
        $this->assertStringContainsString('Clothing', $renderedView);
        $this->assertStringContainsString('Books', $renderedView);

        // Check pickup hours for ready status
        $this->assertStringContainsString('Pickup Hours:', $renderedView);
        $this->assertStringContainsString('Monday - Friday: 9:00 AM - 5:00 PM', $renderedView);
    }

    /** @test */
    public function package_consolidation_notification_template_renders_correctly()
    {
        $notification = new PackageConsolidationNotification($this->user, $this->consolidatedPackage);

        $mailMessage = $notification->toMail($this->user);

        // Check that the notification uses the correct view
        $this->assertEquals('emails.packages.package-consolidation', $mailMessage->view);
        
        // Check subject
        $this->assertEquals('Your Packages Have Been Consolidated', $mailMessage->subject);

        // Check that required data is passed to the view
        $viewData = $mailMessage->viewData;
        $this->assertArrayHasKey('user', $viewData);
        $this->assertArrayHasKey('consolidatedPackage', $viewData);
        $this->assertArrayHasKey('individualPackages', $viewData);
        $this->assertArrayHasKey('packageCount', $viewData);

        // Verify data content
        $this->assertEquals($this->user->id, $viewData['user']->id);
        $this->assertEquals($this->consolidatedPackage->id, $viewData['consolidatedPackage']->id);
        $this->assertEquals(3, $viewData['packageCount']);
        $this->assertCount(3, $viewData['individualPackages']);
    }

    /** @test */
    public function package_consolidation_template_contains_required_content()
    {
        $viewData = [
            'user' => $this->user,
            'consolidatedPackage' => $this->consolidatedPackage,
            'individualPackages' => $this->individualPackages,
            'packageCount' => 3,
        ];

        $renderedView = View::make('emails.packages.package-consolidation', $viewData)->render();

        // Check for essential content
        $this->assertStringContainsString('Hi John,', $renderedView);
        $this->assertStringContainsString('consolidated 3 of your packages', $renderedView);
        $this->assertStringContainsString('CONS-20241208-0001', $renderedView);
        $this->assertStringContainsString('3 packages', $renderedView);
        $this->assertStringContainsString('15.50 lbs', $renderedView);
        
        // Check benefits section
        $this->assertStringContainsString('Benefits of Package Consolidation', $renderedView);
        $this->assertStringContainsString('Simplified tracking', $renderedView);
        $this->assertStringContainsString('reduced shipping and handling costs', $renderedView);
        
        // Check individual package table
        $this->assertStringContainsString('PKG001', $renderedView);
        $this->assertStringContainsString('Electronics', $renderedView);
        $this->assertStringContainsString('5.25 lbs', $renderedView);
    }

    /** @test */
    public function package_unconsolidation_notification_template_renders_correctly()
    {
        $notification = new PackageUnconsolidationNotification(
            $this->user,
            $this->individualPackages,
            'CONS-20241208-0001'
        );

        $mailMessage = $notification->toMail($this->user);

        // Check that the notification uses the correct view
        $this->assertEquals('emails.packages.package-unconsolidation', $mailMessage->view);
        
        // Check subject
        $this->assertEquals('Your Consolidated Package Has Been Separated', $mailMessage->subject);

        // Check that required data is passed to the view
        $viewData = $mailMessage->viewData;
        $this->assertArrayHasKey('user', $viewData);
        $this->assertArrayHasKey('packages', $viewData);
        $this->assertArrayHasKey('packageCount', $viewData);
        $this->assertArrayHasKey('formerConsolidatedTrackingNumber', $viewData);

        // Verify data content
        $this->assertEquals($this->user->id, $viewData['user']->id);
        $this->assertEquals(3, $viewData['packageCount']);
        $this->assertEquals('CONS-20241208-0001', $viewData['formerConsolidatedTrackingNumber']);
        $this->assertCount(3, $viewData['packages']);
    }

    /** @test */
    public function package_unconsolidation_template_contains_required_content()
    {
        $viewData = [
            'user' => $this->user,
            'packages' => $this->individualPackages,
            'packageCount' => 3,
            'formerConsolidatedTrackingNumber' => 'CONS-20241208-0001',
        ];

        $renderedView = View::make('emails.packages.package-unconsolidation', $viewData)->render();

        // Check for essential content
        $this->assertStringContainsString('Hi John,', $renderedView);
        $this->assertStringContainsString('separated back into 3 individual packages', $renderedView);
        $this->assertStringContainsString('CONS-20241208-0001', $renderedView);
        
        // Check individual package table with status
        $this->assertStringContainsString('PKG001', $renderedView);
        $this->assertStringContainsString('PKG002', $renderedView);
        $this->assertStringContainsString('PKG003', $renderedView);
        
        // Check what this means section
        $this->assertStringContainsString('What This Means:', $renderedView);
        $this->assertStringContainsString('Each package will now receive individual status updates', $renderedView);
        $this->assertStringContainsString('track each package separately', $renderedView);
    }

    /** @test */
    public function consolidated_package_ready_notification_template_renders_correctly()
    {
        $notification = new ConsolidatedPackageReadyNotification(
            $this->user,
            $this->consolidatedPackage,
            true,
            'Please bring ID and payment'
        );

        $mailMessage = $notification->toMail($this->user);

        // Check that the notification uses the correct view
        $this->assertEquals('emails.packages.consolidated-package-ready', $mailMessage->view);
        
        // Check subject
        $this->assertStringContainsString('Consolidated Package Ready for Pickup', $mailMessage->subject);
        $this->assertStringContainsString('CONS-20241208-0001', $mailMessage->subject);

        // Check that required data is passed to the view
        $viewData = $mailMessage->viewData;
        $this->assertArrayHasKey('user', $viewData);
        $this->assertArrayHasKey('consolidatedPackage', $viewData);
        $this->assertArrayHasKey('individualPackages', $viewData);
        $this->assertArrayHasKey('showCosts', $viewData);
        $this->assertArrayHasKey('specialInstructions', $viewData);

        // Verify data content
        $this->assertEquals($this->user->id, $viewData['user']->id);
        $this->assertEquals($this->consolidatedPackage->id, $viewData['consolidatedPackage']->id);
        $this->assertTrue($viewData['showCosts']);
        $this->assertEquals('Please bring ID and payment', $viewData['specialInstructions']);
    }

    /** @test */
    public function consolidated_package_ready_template_contains_required_content()
    {
        $viewData = [
            'user' => $this->user,
            'consolidatedPackage' => $this->consolidatedPackage,
            'individualPackages' => $this->individualPackages,
            'showCosts' => true,
            'specialInstructions' => 'Please bring ID and payment',
        ];

        $renderedView = View::make('emails.packages.consolidated-package-ready', $viewData)->render();

        // Check for essential content
        $this->assertStringContainsString('Hi John,', $renderedView);
        $this->assertStringContainsString('containing 3 individual packages', $renderedView);
        $this->assertStringContainsString('ready for pickup', $renderedView);
        $this->assertStringContainsString('CONS-20241208-0001', $renderedView);
        $this->assertStringContainsString('ACC123456', $renderedView);
        
        // Check cost breakdown (when showCosts is true)
        $this->assertStringContainsString('Cost Breakdown', $renderedView);
        $this->assertStringContainsString('$45.00', $renderedView); // freight
        $this->assertStringContainsString('$12.50', $renderedView); // clearance
        $this->assertStringContainsString('$8.00', $renderedView);  // storage
        $this->assertStringContainsString('$15.00', $renderedView); // delivery
        
        // Check pickup information
        $this->assertStringContainsString('Pickup Information & Requirements', $renderedView);
        $this->assertStringContainsString('Valid government-issued photo identification', $renderedView);
        $this->assertStringContainsString('Monday - Friday: 9:00 AM - 5:00 PM', $renderedView);
        
        // Check special instructions
        $this->assertStringContainsString('Please bring ID and payment', $renderedView);
        
        // Check consolidated pickup benefits
        $this->assertStringContainsString('Consolidated Pickup Benefits:', $renderedView);
        $this->assertStringContainsString('Single visit to collect all your packages', $renderedView);
    }

    /** @test */
    public function consolidated_receipt_email_builds_correctly()
    {
        // Create a package distribution
        $distribution = PackageDistribution::factory()->create([
            'customer_id' => $this->user->id,
            'receipt_number' => 'RCP-20241208-001',
            'total_amount' => 80.50,
            'amount_collected' => 80.50,
            'payment_status' => 'paid',
            'distributed_at' => now(),
        ]);

        $email = new ConsolidatedPackageReceiptEmail($distribution, $this->user, $this->consolidatedPackage);
        $builtEmail = $email->build();

        // Check subject
        $this->assertStringContainsString('Consolidated Package Delivery Receipt', $builtEmail->subject);
        $this->assertStringContainsString('RCP-20241208-001', $builtEmail->subject);

        // Check view
        $this->assertEquals('emails.packages.consolidated-receipt', $builtEmail->view);

        // Check view data
        $viewData = $builtEmail->viewData;
        $this->assertArrayHasKey('distribution', $viewData);
        $this->assertArrayHasKey('customer', $viewData);
        $this->assertArrayHasKey('consolidatedPackage', $viewData);
        $this->assertArrayHasKey('individualPackages', $viewData);
        $this->assertArrayHasKey('totals', $viewData);
        $this->assertArrayHasKey('receipt_number', $viewData);
        $this->assertArrayHasKey('company_name', $viewData);

        // Verify data content
        $this->assertEquals('RCP-20241208-001', $viewData['receipt_number']);
        $this->assertEquals('Paid in Full', $viewData['payment_status']);
        $this->assertCount(3, $viewData['individualPackages']);
    }

    /** @test */
    public function consolidated_receipt_template_contains_required_content()
    {
        $distribution = PackageDistribution::factory()->create([
            'customer_id' => $this->user->id,
            'receipt_number' => 'RCP-20241208-001',
            'total_amount' => 80.50,
            'amount_collected' => 80.50,
            'payment_status' => 'paid',
            'distributed_at' => now(),
        ]);

        $totals = [
            'total_amount' => 80.50,
            'amount_collected' => 80.50,
            'credit_applied' => 0,
            'account_balance_applied' => 0,
            'write_off_amount' => 0,
            'outstanding_balance' => 0,
        ];

        $viewData = [
            'distribution' => $distribution,
            'customer' => $this->user,
            'consolidatedPackage' => $this->consolidatedPackage,
            'individualPackages' => $this->individualPackages,
            'totals' => $totals,
            'receipt_number' => 'RCP-20241208-001',
            'distributed_at' => now()->format('F j, Y \a\t g:i A'),
            'payment_status' => 'Paid in Full',
            'company_name' => 'ShipShark Ltd',
        ];

        $renderedView = View::make('emails.packages.consolidated-receipt', $viewData)->render();

        // Check for essential content
        $this->assertStringContainsString('Hi John,', $renderedView);
        $this->assertStringContainsString('consolidated package containing 3 individual packages', $renderedView);
        $this->assertStringContainsString('RCP-20241208-001', $renderedView);
        $this->assertStringContainsString('CONS-20241208-0001', $renderedView);
        $this->assertStringContainsString('Paid in Full', $renderedView);
        
        // Check individual package details table
        $this->assertStringContainsString('Individual Package Details', $renderedView);
        $this->assertStringContainsString('PKG001', $renderedView);
        $this->assertStringContainsString('Electronics', $renderedView);
        $this->assertStringContainsString('CONSOLIDATED TOTALS', $renderedView);
        
        // Check payment totals
        $this->assertStringContainsString('Grand Total:', $renderedView);
        $this->assertStringContainsString('Cash Collected:', $renderedView);
        
        // Check important note
        $this->assertStringContainsString('This receipt covers all 3 packages', $renderedView);
        $this->assertStringContainsString('PDF copy has been attached', $renderedView);
    }

    /** @test */
    public function consolidated_ready_email_builds_correctly()
    {
        $email = new ConsolidatedPackageReadyEmail(
            $this->user,
            $this->consolidatedPackage,
            true,
            'Bring valid ID'
        );
        $builtEmail = $email->build();

        // Check subject
        $this->assertStringContainsString('Consolidated Package Ready for Pickup', $builtEmail->subject);
        $this->assertStringContainsString('CONS-20241208-0001', $builtEmail->subject);

        // Check view
        $this->assertEquals('emails.packages.consolidated-package-ready', $builtEmail->view);

        // Check view data
        $viewData = $builtEmail->viewData;
        $this->assertArrayHasKey('user', $viewData);
        $this->assertArrayHasKey('consolidatedPackage', $viewData);
        $this->assertArrayHasKey('individualPackages', $viewData);
        $this->assertArrayHasKey('showCosts', $viewData);
        $this->assertArrayHasKey('specialInstructions', $viewData);

        // Verify data content
        $this->assertTrue($viewData['showCosts']);
        $this->assertEquals('Bring valid ID', $viewData['specialInstructions']);
        $this->assertCount(3, $viewData['individualPackages']);
    }

    /** @test */
    public function notification_templates_handle_missing_data_gracefully()
    {
        // Test with minimal data
        $user = User::factory()->create(['first_name' => 'Jane']);
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $user->id,
            'consolidated_tracking_number' => 'CONS-TEST-001',
        ]);

        $viewData = [
            'user' => $user,
            'consolidatedPackage' => $consolidatedPackage,
            'individualPackages' => collect([]),
            'showCosts' => false,
            'specialInstructions' => null,
        ];

        // Should not throw an exception
        $renderedView = View::make('emails.packages.consolidated-package-ready', $viewData)->render();
        
        $this->assertStringContainsString('Hi Jane,', $renderedView);
        $this->assertStringContainsString('CONS-TEST-001', $renderedView);
        
        // Cost breakdown should not appear when showCosts is false
        $this->assertStringNotContainsString('Cost Breakdown', $renderedView);
    }
}