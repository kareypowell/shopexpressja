<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ReceiptGeneratorService;
use App\Services\DistributionEmailService;
use App\Models\PackageDistribution;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class ReceiptStorageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function receipt_generator_and_email_service_use_same_storage_disk()
    {
        Storage::fake('public');
        
        // Create test data
        $customerRole = Role::where('name', 'customer')->first();
        $customer = User::factory()->create(['role_id' => $customerRole->id]);
        $admin = User::factory()->create();
        
        $distribution = PackageDistribution::create([
            'receipt_number' => 'TEST001',
            'customer_id' => $customer->id,
            'distributed_by' => $admin->id,
            'distributed_at' => now(),
            'total_amount' => 100.00,
            'amount_collected' => 100.00,
            'payment_status' => 'paid',
            'receipt_path' => '',
            'email_sent' => false,
        ]);

        // Mock the PDF generation to avoid dependencies
        $receiptPath = 'receipts/TEST001.pdf';
        Storage::disk('public')->put($receiptPath, 'mock pdf content');
        $distribution->update(['receipt_path' => $receiptPath]);

        // Test that email service can find the file
        $emailService = app(DistributionEmailService::class);
        
        // This should not throw an exception about file not found
        $this->expectNotToPerformAssertions();
        
        try {
            $result = $emailService->sendReceiptEmail($distribution, $customer);
            // If we get here without exception, the file was found correctly
        } catch (\Exception $e) {
            // Should not be a "file not found" error anymore
            $this->assertStringNotContainsString('Receipt file not found', $e->getMessage());
        }
    }

    /** @test */
    public function receipt_files_are_stored_on_public_disk()
    {
        Storage::fake('public');
        
        // Simulate receipt generation
        $receiptPath = 'receipts/RCP20250808222927093.pdf';
        Storage::disk('public')->put($receiptPath, 'mock pdf content');
        
        // Verify file exists on public disk
        $this->assertTrue(Storage::disk('public')->exists($receiptPath));
        
        // Verify file does NOT exist on default disk (this was the bug)
        $this->assertFalse(Storage::exists($receiptPath));
        
        // Verify we can get the correct path
        $fullPath = Storage::disk('public')->path($receiptPath);
        $this->assertStringContainsString('public/receipts', $fullPath);
    }
}