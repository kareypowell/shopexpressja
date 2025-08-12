<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\PackageDistribution;
use App\Services\ReceiptGeneratorService;
use App\Services\PackageDistributionService;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class ReceiptGenerationTest extends TestCase
{
    use RefreshDatabase;

    private $receiptGenerator;
    private $distributionService;
    private $admin;
    private $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->receiptGenerator = app(ReceiptGeneratorService::class);
        $this->distributionService = app(PackageDistributionService::class);
        
        $this->admin = User::factory()->create([
            'role_id' => 1,
            'first_name' => 'John',
            'last_name' => 'Admin',
        ]);
        
        $this->customer = User::factory()->create([
            'role_id' => 3,
            'first_name' => 'Simba',
            'last_name' => 'Powell',
            'email' => 'simba.powell@example.com',
            'account_balance' => 875.00,
            'credit_balance' => 0.00,
        ]);

        // Mock storage
        Storage::fake('public');
    }

    /** @test */
    public function it_includes_customer_name_in_receipt_data()
    {
        $package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
            'tracking_number' => 'TEST123',
            'description' => 'Test Package',
            'freight_price' => 100.00,
            'customs_duty' => 25.00,
            'storage_fee' => 10.00,
            'delivery_fee' => 15.00,
        ]);

        // Create distribution
        $result = $this->distributionService->distributePackages(
            [$package->id],
            150.00, // Exact payment
            $this->admin,
            [] // No balance application
        );

        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];

        // Test the receipt data formatting
        $receiptData = $this->receiptGenerator->formatReceiptData($distribution);

        // Verify customer information is properly included
        $this->assertArrayHasKey('customer', $receiptData);
        $this->assertEquals('Simba Powell', $receiptData['customer']['name']);
        $this->assertEquals('simba.powell@example.com', $receiptData['customer']['email']);

        // Verify distributed by information
        $this->assertArrayHasKey('distributed_by', $receiptData);
        $this->assertEquals('John Admin', $receiptData['distributed_by']['name']);

        // Verify other essential data
        $this->assertArrayHasKey('receipt_number', $receiptData);
        $this->assertArrayHasKey('packages', $receiptData);
        $this->assertCount(1, $receiptData['packages']);
        $this->assertEquals('TEST123', $receiptData['packages'][0]['tracking_number']);
    }

    /** @test */
    public function it_includes_balance_information_in_totals()
    {
        $package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 100.00,
            'customs_duty' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        // Create distribution with overpayment
        $result = $this->distributionService->distributePackages(
            [$package->id],
            150.00, // Overpayment
            $this->admin,
            [] // No balance application
        );

        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];

        // Test the totals calculation
        $totals = $this->receiptGenerator->calculateTotals($distribution);

        // Verify totals include all necessary fields
        $this->assertArrayHasKey('total_amount', $totals);
        $this->assertArrayHasKey('amount_collected', $totals);
        $this->assertArrayHasKey('credit_applied', $totals);
        $this->assertArrayHasKey('account_balance_applied', $totals);
        $this->assertArrayHasKey('total_paid', $totals);
        $this->assertArrayHasKey('outstanding_balance', $totals);

        // Verify values
        $this->assertEquals('100.00', $totals['total_amount']);
        $this->assertEquals('150.00', $totals['amount_collected']); // Full amount collected
        $this->assertEquals('0.00', $totals['credit_applied']);
        $this->assertEquals('0.00', $totals['account_balance_applied']);
        $this->assertEquals('150.00', $totals['total_paid']); // Total includes overpayment
        $this->assertEquals('0.00', $totals['outstanding_balance']);
    }

    /** @test */
    public function it_shows_credit_and_account_balance_when_applied()
    {
        // Customer with both balances
        $customer = User::factory()->create([
            'role_id' => 3,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'account_balance' => 200.00,
            'credit_balance' => 50.00,
        ]);

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 100.00,
            'customs_duty' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        // Create distribution using both balances
        $result = $this->distributionService->distributePackages(
            [$package->id],
            0.00, // No cash payment
            $this->admin,
            ['credit' => true, 'account' => true] // Use both balances
        );

        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];

        // Test the totals calculation
        $totals = $this->receiptGenerator->calculateTotals($distribution);

        // Should show credit applied
        $this->assertEquals('50.00', $totals['credit_applied']);
        // Should show account balance applied for remaining amount
        $this->assertEquals('50.00', $totals['account_balance_applied']);
        // Total paid should be sum of both
        $this->assertEquals('100.00', $totals['total_paid']);
        // No outstanding balance
        $this->assertEquals('0.00', $totals['outstanding_balance']);
    }

    /** @test */
    public function it_generates_pdf_with_customer_information()
    {
        $package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
            'tracking_number' => 'PDF123',
            'description' => 'PDF Test Package',
            'freight_price' => 75.00,
            'customs_duty' => 25.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        // Create distribution
        $result = $this->distributionService->distributePackages(
            [$package->id],
            100.00, // Exact payment
            $this->admin,
            [] // No balance application
        );

        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];

        // Generate PDF
        $pdfPath = $this->receiptGenerator->generatePDF($distribution);

        // Verify PDF was created
        $this->assertNotEmpty($pdfPath);
        Storage::disk('public')->assertExists($pdfPath);

        // Verify distribution was updated with receipt path
        $distribution->refresh();
        $this->assertEquals($pdfPath, $distribution->receipt_path);
    }

    /** @test */
    public function it_handles_missing_customer_profile_gracefully()
    {
        // Customer without profile
        $customer = User::factory()->create([
            'role_id' => 3,
            'first_name' => 'No',
            'last_name' => 'Profile',
        ]);

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 50.00,
            'customs_duty' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        // Create distribution
        $result = $this->distributionService->distributePackages(
            [$package->id],
            50.00,
            $this->admin,
            []
        );

        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];

        // Test receipt data formatting
        $receiptData = $this->receiptGenerator->formatReceiptData($distribution);

        // Should handle missing profile gracefully
        $this->assertEquals('No Profile', $receiptData['customer']['name']);
        $this->assertEquals('N/A', $receiptData['customer']['account_number']);
    }
}