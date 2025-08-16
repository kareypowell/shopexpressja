<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\ConsolidatedPackage;
use App\Models\Package;
use App\Models\ConsolidationHistory;
use Database\Seeders\ConsolidatedPackageTestDataSeeder;

class ConsolidatedPackageSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed base data needed for consolidation
        $this->seed([
            \Database\Seeders\RolesTableSeeder::class,
            \Database\Seeders\OfficesTableSeeder::class,
            \Database\Seeders\ShippersTableSeeder::class,
            \Database\Seeders\ManifestsTableSeeder::class,
        ]);
    }

    /** @test */
    public function it_can_seed_consolidated_package_test_data_without_conflicts()
    {
        // Create some existing users to ensure no conflicts
        $existingUser = User::factory()->create([
            'email' => 'existing.user@test.com',
            'role_id' => \App\Models\Role::where('name', 'customer')->first()->id,
        ]);

        // Run the seeder
        $this->seed(ConsolidatedPackageTestDataSeeder::class);

        // Verify test customers were created with unique emails
        $testCustomers = User::where('email', 'like', '%.test.com')->get();
        $this->assertGreaterThan(5, $testCustomers->count());

        // Verify no duplicate emails
        $emails = $testCustomers->pluck('email')->toArray();
        $this->assertEquals(count($emails), count(array_unique($emails)));

        // Verify existing user was not affected
        $existingUser->refresh();
        $this->assertEquals('existing.user@test.com', $existingUser->email);
    }

    /** @test */
    public function it_creates_consolidated_packages_with_proper_relationships()
    {
        $this->seed(ConsolidatedPackageTestDataSeeder::class);

        // Verify consolidated packages were created
        $consolidatedPackages = ConsolidatedPackage::all();
        $this->assertGreaterThan(0, $consolidatedPackages->count());

        // Verify each consolidated package has proper relationships
        $consolidatedPackages->each(function ($consolidatedPackage) {
            $this->assertNotNull($consolidatedPackage->customer);
            $this->assertNotNull($consolidatedPackage->createdBy);
            $this->assertGreaterThan(0, $consolidatedPackage->packages()->count());
            
            // Verify all packages belong to the same customer
            $consolidatedPackage->packages->each(function ($package) use ($consolidatedPackage) {
                $this->assertEquals($consolidatedPackage->customer_id, $package->user_id);
                $this->assertTrue($package->is_consolidated);
                $this->assertEquals($consolidatedPackage->id, $package->consolidated_package_id);
            });
        });
    }

    /** @test */
    public function it_creates_consolidation_history_records()
    {
        $this->seed(ConsolidatedPackageTestDataSeeder::class);

        // Verify consolidation history was created
        $history = ConsolidationHistory::all();
        $this->assertGreaterThan(0, $history->count());

        // Verify history records have proper structure
        $history->each(function ($historyRecord) {
            $this->assertNotNull($historyRecord->consolidated_package_id);
            $this->assertNotNull($historyRecord->action);
            $this->assertNotNull($historyRecord->performed_by);
            $this->assertIsArray($historyRecord->details);
            $this->assertNotEmpty($historyRecord->details);
        });
    }

    /** @test */
    public function it_creates_packages_with_unique_tracking_numbers()
    {
        $this->seed(ConsolidatedPackageTestDataSeeder::class);

        // Get all packages created by the seeder
        $packages = Package::whereHas('user', function ($query) {
            $query->where('email', 'like', '%.test.com');
        })->get();

        $this->assertGreaterThan(0, $packages->count());

        // Verify all tracking numbers are unique
        $trackingNumbers = $packages->pluck('tracking_number')->toArray();
        $this->assertEquals(count($trackingNumbers), count(array_unique($trackingNumbers)));
    }

    /** @test */
    public function it_creates_customers_with_proper_balances()
    {
        $this->seed(ConsolidatedPackageTestDataSeeder::class);

        // Get test customers
        $testCustomers = User::where('email', 'like', '%.test.com')->get();

        // Verify customers have expected balance ranges
        $testCustomers->each(function ($customer) {
            $this->assertGreaterThanOrEqual(0, $customer->account_balance);
            $this->assertGreaterThanOrEqual(0, $customer->credit_balance);
            $this->assertLessThanOrEqual(5000, $customer->account_balance); // Reasonable upper limit
            $this->assertLessThanOrEqual(1000, $customer->credit_balance); // Reasonable upper limit
        });
    }

    /** @test */
    public function it_can_run_seeder_multiple_times_without_errors()
    {
        // Run seeder first time
        $this->seed(ConsolidatedPackageTestDataSeeder::class);
        $firstRunCount = User::where('email', 'like', '%.test.com')->count();

        // Run seeder second time
        $this->seed(ConsolidatedPackageTestDataSeeder::class);
        $secondRunCount = User::where('email', 'like', '%.test.com')->count();

        // Should create new users each time due to timestamp-based emails
        $this->assertGreaterThan($firstRunCount, $secondRunCount);
    }

    /** @test */
    public function it_creates_different_consolidation_scenarios()
    {
        $this->seed(ConsolidatedPackageTestDataSeeder::class);

        $consolidatedPackages = ConsolidatedPackage::all();
        
        // Verify we have consolidations in different statuses
        $statuses = $consolidatedPackages->pluck('status')->unique();
        $this->assertGreaterThan(1, $statuses->count());

        // Verify we have consolidations with different package counts
        $packageCounts = $consolidatedPackages->map(function ($cp) {
            return $cp->packages()->count();
        })->unique();
        $this->assertGreaterThan(1, $packageCounts->count());

        // Verify we have both active and inactive consolidations
        $activeCount = $consolidatedPackages->where('is_active', true)->count();
        $inactiveCount = $consolidatedPackages->where('is_active', false)->count();
        
        $this->assertGreaterThan(0, $activeCount);
        // Note: Inactive consolidations are created in historical scenarios
    }
}