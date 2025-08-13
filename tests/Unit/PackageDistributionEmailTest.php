<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PackageDistributionService;
use App\Services\DistributionEmailService;
use App\Services\ReceiptGeneratorService;
use App\Models\Package;
use App\Models\User;
use App\Models\Role;
use App\Models\PackageDistribution;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\PackageReceiptEmail;

class PackageDistributionEmailTest extends TestCase
{
    use RefreshDatabase;

    protected $distributionService;
    protected $customer;
    protected $admin;
    protected $packages;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles and users
        $customerRole = Role::where('name', 'customer')->first();
        $this->customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'email' => 'customer@example.com'
        ]);
        
        $adminRole = Role::where('name', 'admin')->first();
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        
        // Create packages ready for distribution
        $this->packages = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 50.00,
            'customs_duty' => 10.00,
            'storage_fee' => 5.00,
            'delivery_fee' => 0.00,
        ]);

        // Set up storage fake first
        Storage::fake('public');
        
        // Mock the receipt generator to avoid file system operations
        $this->mock(ReceiptGeneratorService::class, function ($mock) {
            $mock->shouldReceive('calculateTotals')->andReturn([
                'freight_total' => 100.00, // 2 * 50
                'customs_total' => 20.00,  // 2 * 10
                'storage_total' => 10.00,  // 2 * 5
                'delivery_total' => 0.00,  // 2 * 0
                'grand_total' => 130.00,
                'amount_collected' => 130.00,
                'balance' => 0.00,
            ]);
            $mock->shouldReceive('generatePDF')->andReturnUsing(function ($distribution) {
                // Create a mock file for testing on public disk
                $path = 'receipts/test-receipt-' . $distribution->id . '.pdf';
                Storage::disk('public')->put($path, 'mock pdf content');
                return $path;
            });
        });

        $this->distributionService = app(PackageDistributionService::class);
    }

    /** @test */
    public function it_sends_receipt_email_after_successful_distribution()
    {
        Mail::fake();

        $packageIds = $this->packages->pluck('id')->toArray();
        $amountCollected = 130.00; // Total for 2 packages

        $result = $this->distributionService->distributePackages(
            $packageIds,
            $amountCollected,
            $this->admin,
            ['credit' => false, 'account' => true]
        );

        $this->assertTrue($result['success']);
        
        // Debug: Check if distribution was created
        $this->assertArrayHasKey('distribution', $result);
        $distribution = $result['distribution'];
        $this->assertInstanceOf(PackageDistribution::class, $distribution);
        


        // Verify email was queued
        Mail::assertQueued(PackageReceiptEmail::class, function ($mail) {
            return $mail->customer->id === $this->customer->id &&
                   $mail->distribution instanceof PackageDistribution;
        });
    }

    /** @test */
    public function receipt_email_contains_detailed_breakdown()
    {
        Mail::fake();

        $packageIds = $this->packages->pluck('id')->toArray();
        $amountCollected = 130.00;

        $result = $this->distributionService->distributePackages(
            $packageIds,
            $amountCollected,
            $this->admin,
            ['credit' => false, 'account' => true]
        );

        Mail::assertQueued(PackageReceiptEmail::class, function ($mail) {
            // Check that email has package details
            $this->assertCount(2, $mail->packages);
            
            // Check totals calculation
            $this->assertEquals(100.00, $mail->totals['total_freight']); // 2 * 50
            $this->assertEquals(20.00, $mail->totals['total_customs']); // 2 * 10
            $this->assertEquals(10.00, $mail->totals['total_storage']); // 2 * 5
            $this->assertEquals(0.00, $mail->totals['total_delivery']); // 2 * 0
            $this->assertEquals(130.00, $mail->totals['total_amount']);
            $this->assertEquals(130.00, $mail->totals['amount_collected']);
            $this->assertEquals(0.00, $mail->totals['outstanding_balance']);
            
            return true;
        });
    }

    /** @test */
    public function distribution_record_tracks_email_status()
    {
        Mail::fake();

        $packageIds = $this->packages->pluck('id')->toArray();
        $amountCollected = 130.00;

        $result = $this->distributionService->distributePackages(
            $packageIds,
            $amountCollected,
            $this->admin,
            ['credit' => false, 'account' => true]
        );

        $distribution = $result['distribution'];
        
        // Email should be sent and marked as sent
        $distribution->refresh(); // Refresh to get updated values
        $this->assertTrue($distribution->email_sent);
        $this->assertNotNull($distribution->email_sent_at);
        
        // Verify email was queued
        Mail::assertQueued(PackageReceiptEmail::class);
    }

    /** @test */
    public function email_service_can_send_receipt_independently()
    {
        Mail::fake();
        Storage::fake('public');
        
        // Create a mock receipt file on public disk
        $receiptPath = 'receipts/test-receipt.pdf';
        Storage::disk('public')->put($receiptPath, 'mock pdf content');

        // Create a distribution manually
        $distribution = PackageDistribution::create([
            'receipt_number' => 'TEST001',
            'customer_id' => $this->customer->id,
            'distributed_by' => $this->admin->id,
            'distributed_at' => now(),
            'total_amount' => 65.00,
            'amount_collected' => 65.00,
            'payment_status' => 'paid',
            'receipt_path' => $receiptPath,
            'email_sent' => false,
        ]);

        $emailService = app(DistributionEmailService::class);
        $result = $emailService->sendReceiptEmail($distribution, $this->customer);

        $this->assertTrue($result['success']);
        
        Mail::assertQueued(PackageReceiptEmail::class, function ($mail) use ($distribution) {
            return $mail->distribution->id === $distribution->id &&
                   $mail->customer->id === $this->customer->id;
        });
    }
}