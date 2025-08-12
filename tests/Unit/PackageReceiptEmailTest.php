<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Mail\PackageReceiptEmail;
use App\Models\User;
use App\Models\PackageDistribution;
use App\Models\PackageDistributionItem;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

class PackageReceiptEmailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function package_receipt_email_can_be_rendered_without_number_format_errors()
    {
        // Create test customer
        $customer = User::create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'customer@test.com',
            'password' => bcrypt('password'),
            'role_id' => 3,
        ]);

        // Create admin user for distributed_by
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => 1,
        ]);

        // Create test distribution
        $distribution = PackageDistribution::create([
            'customer_id' => $customer->id,
            'receipt_number' => 'RCP20250811123456789',
            'receipt_path' => 'receipts/test-receipt.pdf',
            'total_amount' => 7942.00,
            'amount_collected' => 8000.00,
            'credit_applied' => 0.00,
            'account_balance_applied' => 0.00,
            'write_off_amount' => 0.00,
            'payment_status' => 'paid',
            'distributed_by' => $admin->id,
            'distributed_at' => now(),
        ]);

        // Create test package and distribution item
        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'tracking_number' => 'TEST123',
            'description' => 'Test Package',
        ]);

        PackageDistributionItem::create([
            'distribution_id' => $distribution->id,
            'package_id' => $package->id,
            'freight_price' => 5000.00,
            'customs_duty' => 1500.00,
            'storage_fee' => 200.00,
            'delivery_fee' => 1242.00,
            'total_cost' => 7942.00,
        ]);

        // Create the email
        $email = new PackageReceiptEmail($distribution, $customer);

        // Test that the email can be built without errors
        $builtEmail = $email->build();
        
        $this->assertInstanceOf(\Illuminate\Mail\Mailable::class, $builtEmail);
        $this->assertEquals('Package Delivery Receipt - RCP20250811123456789', $builtEmail->subject);
        
        // Test that totals are numeric values (not formatted strings)
        $this->assertIsNumeric($email->totals['total_amount']);
        $this->assertIsNumeric($email->totals['amount_collected']);
        $this->assertIsNumeric($email->totals['outstanding_balance']);
        
        // Test that the email view can be rendered without errors
        $renderedView = $builtEmail->render();
        $this->assertStringContainsString('RCP20250811123456789', $renderedView);
        $this->assertStringContainsString('$7,942.00', $renderedView); // Should be formatted in template
    }

    /** @test */
    public function package_receipt_email_handles_large_amounts_correctly()
    {
        // Create test customer
        $customer = User::create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'customer@test.com',
            'password' => bcrypt('password'),
            'role_id' => 3,
        ]);

        // Create admin user for distributed_by
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => 1,
        ]);

        // Create test distribution with large amounts
        $distribution = PackageDistribution::create([
            'customer_id' => $customer->id,
            'receipt_number' => 'RCP20250811123456790',
            'receipt_path' => 'receipts/test-receipt-large.pdf',
            'total_amount' => 15842.50,
            'amount_collected' => 16000.00,
            'credit_applied' => 0.00,
            'account_balance_applied' => 0.00,
            'write_off_amount' => 0.00,
            'payment_status' => 'paid',
            'distributed_by' => $admin->id,
            'distributed_at' => now(),
        ]);

        // Create test package and distribution item
        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'tracking_number' => 'TEST456',
            'description' => 'Large Package',
        ]);

        PackageDistributionItem::create([
            'distribution_id' => $distribution->id,
            'package_id' => $package->id,
            'freight_price' => 10000.00,
            'customs_duty' => 3500.00,
            'storage_fee' => 500.00,
            'delivery_fee' => 1842.50,
            'total_cost' => 15842.50,
        ]);

        // Create the email
        $email = new PackageReceiptEmail($distribution, $customer);

        // Test that the email can be built and rendered without number format errors
        $builtEmail = $email->build();
        $renderedView = $builtEmail->render();
        
        // Should contain properly formatted large amounts
        $this->assertStringContainsString('$15,842.50', $renderedView);
        $this->assertStringContainsString('$16,000.00', $renderedView);
    }
}