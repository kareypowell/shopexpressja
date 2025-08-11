<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\Manifest;
use App\Models\CustomerTransaction;
use App\Services\DashboardAnalyticsService;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class DashboardMetricsFixTest extends TestCase
{
    use RefreshDatabase;

    protected $analyticsService;
    protected $admin;
    protected $customers;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->analyticsService = app(DashboardAnalyticsService::class);
        
        // Create admin users (should be excluded from customer metrics)
        $this->admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => 1,
        ]);
        $this->admin->email_verified_at = Carbon::now();
        $this->admin->save();
        
        $staff = User::create([
            'first_name' => 'Staff',
            'last_name' => 'User',
            'email' => 'staff@test.com',
            'password' => bcrypt('password'),
            'role_id' => 2,
        ]);
        $staff->email_verified_at = Carbon::now();
        $staff->save();
        
        // Create customer users (should be included in customer metrics)
        $this->customers = collect();
        for ($i = 1; $i <= 5; $i++) {
            $user = User::create([
                'first_name' => "Customer{$i}",
                'last_name' => 'User',
                'email' => "customer{$i}@test.com",
                'password' => bcrypt('password'),
                'role_id' => 3,
            ]);
            $user->email_verified_at = Carbon::now();
            $user->save();
            $this->customers->push($user);
        }
        
        // Create one inactive customer
        User::create([
            'first_name' => 'Inactive',
            'last_name' => 'Customer',
            'email' => 'inactive@test.com',
            'password' => bcrypt('password'),
            'role_id' => 3,
        ]);
    }

    /** @test */
    public function customer_metrics_exclude_admin_users()
    {
        $filters = ['period' => 'month'];
        $metrics = $this->analyticsService->getCustomerMetrics($filters);
        
        // Should only count customers (role_id = 3), not admins or staff
        $this->assertEquals(6, $metrics['total']); // 5 active + 1 inactive customer
        $this->assertEquals(5, $metrics['active']); // Only verified customers
        $this->assertEquals(1, $metrics['inactive']); // Unverified customer
    }

    /** @test */
    public function package_metrics_use_correct_status_enum_values()
    {
        $customer = $this->customers->first();

        // create manifest
        $manifest = Manifest::create([
            'user_id' => 1,
            'manifest_number' => 'MAN001',
            'description' => 'Test manifest',
            'office_id' => 1,
        ]);
        
        // Create packages with different statuses using correct enum values
        Package::create([
            'user_id' => $customer->id,
            'tracking_number' => 'TEST001',
            'description' => 'Test package 1',
            'weight' => 1.0,
            'manifest_id' => 1,
            'shipper_id' => 1,
            'office_id' => 1,
            'status' => PackageStatus::PENDING
        ]);
        
        Package::create([
            'user_id' => $customer->id,
            'tracking_number' => 'TEST002',
            'description' => 'Test package 2',
            'weight' => 1.0,
            'manifest_id' => 1,
            'shipper_id' => 1,
            'office_id' => 1,
            'status' => PackageStatus::PROCESSING
        ]);
        
        Package::create([
            'user_id' => $customer->id,
            'tracking_number' => 'TEST003',
            'description' => 'Test package 3',
            'weight' => 1.0,
            'manifest_id' => 1,
            'shipper_id' => 1,
            'office_id' => 1,
            'status' => PackageStatus::SHIPPED
        ]);
        
        Package::create([
            'user_id' => $customer->id,
            'tracking_number' => 'TEST004',
            'description' => 'Test package 4',
            'weight' => 1.0,
            'manifest_id' => 1,
            'shipper_id' => 1,
            'office_id' => 1,
            'status' => PackageStatus::DELIVERED
        ]);
        
        Package::create([
            'user_id' => $customer->id,
            'tracking_number' => 'TEST005',
            'description' => 'Test package 5',
            'weight' => 1.0,
            'manifest_id' => 1,
            'shipper_id' => 2,
            'office_id' => 1,
            'status' => PackageStatus::DELAYED
        ]);
        
        $filters = ['period' => 'month'];
        $metrics = $this->analyticsService->getShipmentMetrics($filters);
        
        // Verify correct status counts
        $this->assertEquals(5, $metrics['total']);
        $this->assertEquals(1, $metrics['pending']);
        $this->assertEquals(1, $metrics['processing']);
        $this->assertEquals(1, $metrics['shipped']);
        $this->assertEquals(1, $metrics['delivered']);
        $this->assertEquals(1, $metrics['delayed']);
        $this->assertEquals(1, $metrics['in_transit']); // shipped + customs (0)
    }

    /** @test */
    public function financial_metrics_use_actual_customer_transactions()
    {
        $customer = $this->customers->first();
        
        // Create actual customer transactions (not package fees)
        CustomerTransaction::create([
            'user_id' => $customer->id,
            'type' => CustomerTransaction::TYPE_PAYMENT,
            'amount' => 100.00,
            'balance_before' => 0.00,
            'balance_after' => 100.00,
            'description' => 'Test payment',
            'created_by' => $this->admin->id
        ]);
        
        CustomerTransaction::create([
            'user_id' => $customer->id,
            'type' => CustomerTransaction::TYPE_CHARGE,
            'amount' => 75.50,
            'balance_before' => 100.00,
            'balance_after' => 24.50,
            'description' => 'Package distribution charge',
            'reference_type' => 'package_distribution',
            'reference_id' => 1,
            'created_by' => $this->admin->id
        ]);
        
        // Create a credit transaction (should not count as revenue)
        CustomerTransaction::create([
            'user_id' => $customer->id,
            'type' => CustomerTransaction::TYPE_CREDIT,
            'amount' => 25.00,
            'balance_before' => 24.50,
            'balance_after' => 49.50,
            'description' => 'Account credit',
            'created_by' => $this->admin->id
        ]);
        
        $filters = ['period' => 'month'];
        $metrics = $this->analyticsService->getFinancialMetrics($filters);
        
        // Revenue should be payment + charge = 175.50
        $this->assertEquals(175.50, $metrics['current_period']);
        $this->assertEquals(1, $metrics['total_orders']); // One package distribution
        $this->assertEquals(175.50, $metrics['average_order_value']); // 175.50 / 1 order
    }

    /** @test */
    public function financial_metrics_exclude_admin_transactions()
    {
        $customer = $this->customers->first();
        
        // Create customer transaction
        CustomerTransaction::create([
            'user_id' => $customer->id,
            'type' => CustomerTransaction::TYPE_PAYMENT,
            'amount' => 100.00,
            'balance_before' => 0.00,
            'balance_after' => 100.00,
            'description' => 'Customer payment',
            'created_by' => $this->admin->id
        ]);
        
        // Create admin transaction (should be excluded)
        CustomerTransaction::create([
            'user_id' => $this->admin->id,
            'type' => CustomerTransaction::TYPE_PAYMENT,
            'amount' => 500.00,
            'balance_before' => 0.00,
            'balance_after' => 500.00,
            'description' => 'Admin payment',
            'created_by' => $this->admin->id
        ]);
        
        $filters = ['period' => 'month'];
        $metrics = $this->analyticsService->getFinancialMetrics($filters);
        
        // Should only count customer transaction, not admin
        $this->assertEquals(100.00, $metrics['current_period']);
    }
}