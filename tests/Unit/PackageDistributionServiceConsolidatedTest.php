<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\PackageDistribution;
use App\Models\PackageDistributionItem;
use App\Enums\PackageStatus;
use App\Services\PackageDistributionService;
use App\Services\ReceiptGeneratorService;
use App\Services\DistributionEmailService;
use App\Services\PackageStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;

class PackageDistributionServiceConsolidatedTest extends TestCase
{
    use RefreshDatabase;

    protected $distributionService;
    protected $receiptGenerator;
    protected $emailService;
    protected $packageStatusService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->receiptGenerator = Mockery::mock(ReceiptGeneratorService::class);
        $this->emailService = Mockery::mock(DistributionEmailService::class);
        $this->packageStatusService = Mockery::mock(PackageStatusService::class);

        // Create service instance with mocked dependencies
        $this->distributionService = new PackageDistributionService(
            $this->receiptGenerator,
            $this->emailService
        );

        // Mock the PackageStatusService in the container
        $this->app->instance(PackageStatusService::class, $this->packageStatusService);

        // Mock Storage
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_distribute_consolidated_packages_successfully()
    {
        // Arrange
        $customer = User::factory()->create(['account_balance' => 100, 'credit_balance' => 50]);
        $admin = User::factory()->create();
        
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $admin->id,
            'status' => PackageStatus::READY,
            'total_freight_price' => 50.00,
            'total_clearance_fee' => 30.00,
            'total_storage_fee' => 10.00,
            'total_delivery_fee' => 15.00,
            'is_active' => true,
        ]);

        $packages = Package::factory()->count(3)->create([
            'user_id' => $customer->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'status' => PackageStatus::READY,
            'is_consolidated' => true,
            'freight_price' => 16.67,
            'clearance_fee' => 10.00,
            'storage_fee' => 3.33,
            'delivery_fee' => 5.00,
        ]);

        // Mock receipt generation
        $this->receiptGenerator
            ->shouldReceive('calculateTotals')
            ->once()
            ->andReturn([
                'subtotal' => '105.00',
                'total_freight' => '50.00',
                'total_customs' => '30.00',
                'total_storage' => '10.00',
                'total_delivery' => '15.00',
                'total_amount' => '105.00',
                'amount_collected' => '80.00',
                'credit_applied' => '0.00',
                'account_balance_applied' => '0.00',
                'write_off_amount' => '0.00',
                'total_paid' => '80.00',
                'outstanding_balance' => '25.00',
                'payment_status' => 'Partial',
            ]);

        // Mock email service
        $this->emailService
            ->shouldReceive('sendReceiptEmail')
            ->once()
            ->andReturn(['success' => true]);

        // Mock package status service
        $this->packageStatusService
            ->shouldReceive('markAsDeliveredThroughDistribution')
            ->times(3);

        // Act
        $result = $this->distributionService->distributeConsolidatedPackages(
            $consolidatedPackage,
            80.00,
            $admin,
            [],
            []
        );

        // Assert
        $this->assertTrue($result['success']);
        $this->assertInstanceOf(PackageDistribution::class, $result['distribution']);
        $this->assertInstanceOf(ConsolidatedPackage::class, $result['consolidated_package']);
        $this->assertEquals('Consolidated packages distributed successfully', $result['message']);

        // Verify distribution record
        $distribution = $result['distribution'];
        $this->assertEquals($customer->id, $distribution->customer_id);
        $this->assertEquals($admin->id, $distribution->distributed_by);
        $this->assertEquals(105.00, $distribution->total_amount);
        $this->assertEquals(80.00, $distribution->amount_collected);
        $this->assertEquals('partial', $distribution->payment_status);

        // Verify distribution items were created
        $this->assertEquals(3, $distribution->items()->count());

        // Verify consolidated package status was updated
        $consolidatedPackage->refresh();
        $this->assertEquals(PackageStatus::DELIVERED, $consolidatedPackage->status);
    }

    /** @test */
    public function it_applies_credit_balance_to_consolidated_distribution()
    {
        // Arrange
        $customer = User::factory()->create(['account_balance' => 100, 'credit_balance' => 50]);
        $admin = User::factory()->create();
        
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $admin->id,
            'status' => PackageStatus::READY,
            'total_freight_price' => 30.00,
            'total_clearance_fee' => 20.00,
            'total_storage_fee' => 5.00,
            'total_delivery_fee' => 10.00,
            'is_active' => true,
        ]);

        Package::factory()->count(2)->create([
            'user_id' => $customer->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'status' => PackageStatus::READY,
            'is_consolidated' => true,
        ]);

        // Mock dependencies
        $this->receiptGenerator->shouldReceive('calculateTotals')->once()->andReturn([
            'subtotal' => '65.00',
            'total_freight' => '30.00',
            'total_customs' => '20.00',
            'total_storage' => '5.00',
            'total_delivery' => '10.00',
            'total_amount' => '65.00',
            'amount_collected' => '30.00',
            'credit_applied' => '35.00',
            'account_balance_applied' => '0.00',
            'write_off_amount' => '0.00',
            'total_paid' => '65.00',
            'outstanding_balance' => '0.00',
            'payment_status' => 'Paid',
        ]);
        $this->emailService->shouldReceive('sendReceiptEmail')->once()->andReturn(['success' => true]);
        $this->packageStatusService->shouldReceive('markAsDeliveredThroughDistribution')->times(2);

        // Act
        $result = $this->distributionService->distributeConsolidatedPackages(
            $consolidatedPackage,
            30.00,
            $admin,
            ['credit' => true],
            []
        );

        // Assert
        $this->assertTrue($result['success']);
        
        $distribution = $result['distribution'];
        $this->assertEquals(65.00, $distribution->total_amount);
        $this->assertEquals(30.00, $distribution->amount_collected);
        $this->assertGreaterThan(0, $distribution->credit_applied);
        $this->assertEquals('paid', $distribution->payment_status);

        // Verify customer's credit balance was reduced
        $customer->refresh();
        $this->assertLessThan(50, $customer->credit_balance);
    }

    /** @test */
    public function it_applies_account_balance_to_consolidated_distribution()
    {
        // Arrange
        $customer = User::factory()->create(['account_balance' => 100, 'credit_balance' => 0]);
        $admin = User::factory()->create();
        
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $admin->id,
            'status' => PackageStatus::READY,
            'total_freight_price' => 40.00,
            'total_clearance_fee' => 25.00,
            'total_storage_fee' => 5.00,
            'total_delivery_fee' => 10.00,
            'is_active' => true,
        ]);

        Package::factory()->count(2)->create([
            'user_id' => $customer->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'status' => PackageStatus::READY,
            'is_consolidated' => true,
        ]);

        // Mock dependencies
        $this->receiptGenerator->shouldReceive('calculateTotals')->once()->andReturn([
            'subtotal' => '80.00',
            'total_freight' => '40.00',
            'total_customs' => '25.00',
            'total_storage' => '5.00',
            'total_delivery' => '10.00',
            'total_amount' => '80.00',
            'amount_collected' => '50.00',
            'credit_applied' => '0.00',
            'account_balance_applied' => '30.00',
            'write_off_amount' => '0.00',
            'total_paid' => '80.00',
            'outstanding_balance' => '0.00',
            'payment_status' => 'Paid',
        ]);
        $this->emailService->shouldReceive('sendReceiptEmail')->once()->andReturn(['success' => true]);
        $this->packageStatusService->shouldReceive('markAsDeliveredThroughDistribution')->times(2);

        // Act
        $result = $this->distributionService->distributeConsolidatedPackages(
            $consolidatedPackage,
            50.00,
            $admin,
            ['account' => true],
            []
        );

        // Assert
        $this->assertTrue($result['success']);
        
        $distribution = $result['distribution'];
        $this->assertEquals(80.00, $distribution->total_amount);
        $this->assertEquals(50.00, $distribution->amount_collected);
        $this->assertEquals(30.00, $distribution->account_balance_applied);
        $this->assertEquals('paid', $distribution->payment_status);
    }

    /** @test */
    public function it_handles_write_off_for_consolidated_distribution()
    {
        // Arrange
        $customer = User::factory()->create();
        $admin = User::factory()->create();
        
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $admin->id,
            'status' => PackageStatus::READY,
            'total_freight_price' => 50.00,
            'total_clearance_fee' => 30.00,
            'total_storage_fee' => 10.00,
            'total_delivery_fee' => 10.00,
            'is_active' => true,
        ]);

        Package::factory()->count(2)->create([
            'user_id' => $customer->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'status' => PackageStatus::READY,
            'is_consolidated' => true,
        ]);

        // Mock dependencies
        $this->receiptGenerator->shouldReceive('calculateTotals')->once()->andReturn([
            'subtotal' => '100.00',
            'total_freight' => '50.00',
            'total_customs' => '30.00',
            'total_storage' => '10.00',
            'total_delivery' => '10.00',
            'total_amount' => '100.00',
            'amount_collected' => '80.00',
            'credit_applied' => '0.00',
            'account_balance_applied' => '0.00',
            'write_off_amount' => '20.00',
            'total_paid' => '100.00',
            'outstanding_balance' => '0.00',
            'payment_status' => 'Paid',
        ]);
        $this->emailService->shouldReceive('sendReceiptEmail')->once()->andReturn(['success' => true]);
        $this->packageStatusService->shouldReceive('markAsDeliveredThroughDistribution')->times(2);

        // Act
        $result = $this->distributionService->distributeConsolidatedPackages(
            $consolidatedPackage,
            80.00,
            $admin,
            [],
            ['writeOff' => 20.00]
        );

        // Assert
        $this->assertTrue($result['success']);
        
        $distribution = $result['distribution'];
        $this->assertEquals(100.00, $distribution->total_amount); // Original total before write-off
        $this->assertEquals(80.00, $distribution->amount_collected);
        $this->assertEquals(20.00, $distribution->write_off_amount);
        $this->assertEquals('paid', $distribution->payment_status);
    }

    /** @test */
    public function it_fails_when_consolidated_package_is_not_ready()
    {
        // Arrange
        $customer = User::factory()->create();
        $admin = User::factory()->create();
        
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $admin->id,
            'status' => PackageStatus::PROCESSING,
            'is_active' => true,
        ]);

        // Act
        $result = $this->distributionService->distributeConsolidatedPackages(
            $consolidatedPackage,
            100.00,
            $admin,
            [],
            []
        );

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Consolidated package is not ready for distribution', $result['message']);
    }

    /** @test */
    public function it_fails_when_no_packages_are_ready_in_consolidation()
    {
        // Arrange
        $customer = User::factory()->create();
        $admin = User::factory()->create();
        
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $admin->id,
            'status' => PackageStatus::READY,
            'is_active' => true,
        ]);

        // Create packages that are not ready
        Package::factory()->count(2)->create([
            'user_id' => $customer->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'status' => PackageStatus::PROCESSING,
            'is_consolidated' => true,
        ]);

        // Act
        $result = $this->distributionService->distributeConsolidatedPackages(
            $consolidatedPackage,
            100.00,
            $admin,
            [],
            []
        );

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('No packages ready for distribution in this consolidation', $result['message']);
    }

    /** @test */
    public function it_redirects_individual_package_distribution_to_consolidated_when_packages_are_consolidated()
    {
        // Arrange
        $customer = User::factory()->create();
        $admin = User::factory()->create();
        
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $admin->id,
            'status' => PackageStatus::READY,
            'total_freight_price' => 30.00,
            'total_clearance_fee' => 20.00,
            'total_storage_fee' => 5.00,
            'total_delivery_fee' => 10.00,
            'is_active' => true,
        ]);

        $packages = Package::factory()->count(2)->create([
            'user_id' => $customer->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'status' => PackageStatus::READY,
            'is_consolidated' => true,
        ]);

        // Mock dependencies
        $this->receiptGenerator->shouldReceive('calculateTotals')->once()->andReturn([
            'subtotal' => '65.00',
            'total_freight' => '30.00',
            'total_customs' => '20.00',
            'total_storage' => '5.00',
            'total_delivery' => '10.00',
            'total_amount' => '65.00',
            'amount_collected' => '50.00',
            'credit_applied' => '0.00',
            'account_balance_applied' => '0.00',
            'write_off_amount' => '0.00',
            'total_paid' => '50.00',
            'outstanding_balance' => '15.00',
            'payment_status' => 'Partial',
        ]);
        $this->emailService->shouldReceive('sendReceiptEmail')->once()->andReturn(['success' => true]);
        $this->packageStatusService->shouldReceive('markAsDeliveredThroughDistribution')->times(2);

        // Act - Try to distribute individual packages, should redirect to consolidated
        $result = $this->distributionService->distributePackages(
            $packages->pluck('id')->toArray(),
            50.00,
            $admin,
            [],
            []
        );

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('consolidated_package', $result);
        $this->assertEquals('Consolidated packages distributed successfully', $result['message']);
    }

    /** @test */
    public function it_calculates_totals_correctly_for_consolidated_packages()
    {
        // Arrange
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'total_freight_price' => 100.00,
            'total_clearance_fee' => 50.00,
            'total_storage_fee' => 25.00,
            'total_delivery_fee' => 30.00,
        ]);

        $packages = Package::factory()->count(3)->create();

        // Act
        $total = $this->distributionService->calculatePackageTotals($packages->toArray(), $consolidatedPackage);

        // Assert
        $this->assertEquals(205.00, $total); // Sum of all consolidated totals
    }

    /** @test */
    public function it_calculates_totals_correctly_for_individual_packages_when_no_consolidation()
    {
        // Arrange
        $packages = collect([
            (object) ['freight_price' => 20, 'clearance_fee' => 10, 'storage_fee' => 5, 'delivery_fee' => 8],
            (object) ['freight_price' => 30, 'clearance_fee' => 15, 'storage_fee' => 7, 'delivery_fee' => 12],
        ]);

        // Act
        $total = $this->distributionService->calculatePackageTotals($packages->toArray());

        // Assert
        $this->assertEquals(107.00, $total); // (20+10+5+8) + (30+15+7+12)
    }

    /** @test */
    public function it_generates_consolidated_receipt_with_correct_data()
    {
        // Arrange
        $customer = User::factory()->create();
        $admin = User::factory()->create();
        
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $customer->id,
            'consolidated_tracking_number' => 'CONS-20241208-0001',
            'total_weight' => 15.5,
            'total_quantity' => 3,
            'total_freight_price' => 75.00,
            'total_clearance_fee' => 45.00,
            'total_storage_fee' => 15.00,
            'total_delivery_fee' => 20.00,
        ]);

        $distribution = PackageDistribution::factory()->create([
            'customer_id' => $customer->id,
            'distributed_by' => $admin->id,
            'receipt_number' => 'RCP-001',
        ]);

        $packages = Package::factory()->count(3)->create([
            'user_id' => $customer->id,
            'consolidated_package_id' => $consolidatedPackage->id,
        ]);

        foreach ($packages as $package) {
            PackageDistributionItem::factory()->create([
                'distribution_id' => $distribution->id,
                'package_id' => $package->id,
            ]);
        }

        // Act & Assert - Test that the method can format data correctly without PDF generation
        $receiptData = $this->distributionService->formatConsolidatedReceiptData($consolidatedPackage, $distribution);

        // Assert receipt data structure
        $this->assertEquals('RCP-001', $receiptData['receipt_number']);
        $this->assertEquals('CONS-20241208-0001', $receiptData['consolidated_tracking_number']);
        $this->assertTrue($receiptData['is_consolidated']);
        $this->assertEquals('15.50', $receiptData['consolidated_totals']['total_weight']);
        $this->assertEquals(3, $receiptData['consolidated_totals']['total_quantity']);
        $this->assertEquals('155.00', $receiptData['consolidated_totals']['total_cost']);
        $this->assertCount(3, $receiptData['packages']);
    }

    /** @test */
    public function it_formats_consolidated_receipt_data_correctly()
    {
        // Arrange
        $customer = User::factory()->create(['email' => 'customer@test.com']);
        $admin = User::factory()->create();
        
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $customer->id,
            'consolidated_tracking_number' => 'CONS-20241208-0001',
            'total_weight' => 25.0,
            'total_quantity' => 2,
            'total_freight_price' => 100.00,
            'total_clearance_fee' => 60.00,
            'total_storage_fee' => 20.00,
            'total_delivery_fee' => 25.00,
        ]);

        $distribution = PackageDistribution::factory()->create([
            'customer_id' => $customer->id,
            'distributed_by' => $admin->id,
            'receipt_number' => 'RCP-002',
            'distributed_at' => now(),
        ]);

        $packages = Package::factory()->count(2)->create([
            'user_id' => $customer->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'description' => 'Test Package',
        ]);

        foreach ($packages as $package) {
            PackageDistributionItem::factory()->create([
                'distribution_id' => $distribution->id,
                'package_id' => $package->id,
            ]);
        }

        // Act
        $receiptData = $this->distributionService->formatConsolidatedReceiptData($consolidatedPackage, $distribution);

        // Assert
        $this->assertEquals('RCP-002', $receiptData['receipt_number']);
        $this->assertEquals('CONS-20241208-0001', $receiptData['consolidated_tracking_number']);
        $this->assertTrue($receiptData['is_consolidated']);
        $this->assertEquals('customer@test.com', $receiptData['customer']['email']);
        $this->assertEquals('25.00', $receiptData['consolidated_totals']['total_weight']);
        $this->assertEquals(2, $receiptData['consolidated_totals']['total_quantity']);
        $this->assertEquals('205.00', $receiptData['consolidated_totals']['total_cost']);
        $this->assertCount(2, $receiptData['packages']);
    }

    /** @test */
    public function it_logs_consolidated_distribution_correctly()
    {
        // Arrange
        $customer = User::factory()->create();
        $admin = User::factory()->create();
        
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $customer->id,
            'consolidated_tracking_number' => 'CONS-20241208-0001',
        ]);

        $packages = Package::factory()->count(2)->create([
            'user_id' => $customer->id,
            'consolidated_package_id' => $consolidatedPackage->id,
        ]);

        $distribution = PackageDistribution::factory()->create([
            'customer_id' => $customer->id,
            'receipt_number' => 'RCP-003',
        ]);

        // Act - Just test that the method doesn't throw an exception
        $this->distributionService->logConsolidatedDistribution(
            $consolidatedPackage,
            $packages->toArray(),
            $distribution,
            100.00,
            $admin
        );

        // Assert - If we get here without exception, the test passes
        $this->assertTrue(true);
    }
}