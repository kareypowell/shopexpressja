<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\ConsolidationHistory;
use App\Services\PackageConsolidationService;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class ConsolidationAuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected $consolidationService;
    protected $admin;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        
        $this->consolidationService = app(PackageConsolidationService::class);
        $adminRole = Role::where('name', 'superadmin')->first();
        $customerRole = Role::where('name', 'customer')->first();
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->customer = User::factory()->create(['role_id' => $customerRole->id]);
    }

    /** @test */
    public function it_logs_consolidation_action_when_packages_are_consolidated()
    {
        // Create packages for consolidation
        $packages = Package::factory()->count(3)->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::PROCESSING,
            'weight' => 5.0,
            'freight_price' => 25.00,
            'clearance_fee' => 0,
            'storage_fee' => 0,
            'delivery_fee' => 0,
        ]);

        $packageIds = $packages->pluck('id')->toArray();

        // Consolidate packages
        $result = $this->consolidationService->consolidatePackages(
            $packageIds,
            $this->admin,
            ['notes' => 'Test consolidation']
        );

        $this->assertTrue($result['success']);
        $consolidatedPackage = $result['consolidated_package'];

        // Assert history record was created
        $this->assertDatabaseHas('consolidation_history', [
            'consolidated_package_id' => $consolidatedPackage->id,
            'action' => 'consolidated',
            'performed_by' => $this->admin->id,
        ]);

        // Get the history record and check details
        $historyRecord = ConsolidationHistory::where('consolidated_package_id', $consolidatedPackage->id)
            ->where('action', 'consolidated')
            ->first();

        $this->assertNotNull($historyRecord);
        $this->assertEquals($packageIds, $historyRecord->details['package_ids']);
        $this->assertEquals(3, $historyRecord->details['package_count']);
        $this->assertEquals(15.0, $historyRecord->details['total_weight']);
        $this->assertEquals(75.0, $historyRecord->details['total_cost']);
    }

    /** @test */
    public function it_logs_unconsolidation_action_when_packages_are_unconsolidated()
    {
        // Create consolidated package
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
            'is_active' => true,
        ]);

        $packages = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true,
            'status' => PackageStatus::PROCESSING,
        ]);

        // Unconsolidate packages
        $result = $this->consolidationService->unconsolidatePackages(
            $consolidatedPackage,
            $this->admin,
            ['notes' => 'Customer requested separation']
        );

        $this->assertTrue($result['success']);

        // Assert history record was created
        $this->assertDatabaseHas('consolidation_history', [
            'consolidated_package_id' => $consolidatedPackage->id,
            'action' => 'unconsolidated',
            'performed_by' => $this->admin->id,
        ]);

        // Get the history record and check details
        $historyRecord = ConsolidationHistory::where('consolidated_package_id', $consolidatedPackage->id)
            ->where('action', 'unconsolidated')
            ->first();

        $this->assertNotNull($historyRecord);
        $this->assertEquals($packages->pluck('id')->toArray(), $historyRecord->details['package_ids']);
        $this->assertEquals(2, $historyRecord->details['package_count']);
        $this->assertEquals('Customer requested separation', $historyRecord->details['reason']);
    }

    /** @test */
    public function it_logs_status_change_action_when_consolidated_package_status_is_updated()
    {
        // Create consolidated package
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => PackageStatus::PROCESSING,
            'is_active' => true,
        ]);

        Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true,
            'status' => PackageStatus::PROCESSING,
        ]);

        // Update status
        $result = $this->consolidationService->updateConsolidatedStatus(
            $consolidatedPackage,
            PackageStatus::READY->value,
            $this->admin,
            ['reason' => 'All packages processed']
        );

        $this->assertTrue($result['success']);

        // Assert history record was created
        $this->assertDatabaseHas('consolidation_history', [
            'consolidated_package_id' => $consolidatedPackage->id,
            'action' => 'status_changed',
            'performed_by' => $this->admin->id,
        ]);

        // Get the history record and check details
        $historyRecord = ConsolidationHistory::where('consolidated_package_id', $consolidatedPackage->id)
            ->where('action', 'status_changed')
            ->first();

        $this->assertNotNull($historyRecord);
        $this->assertEquals(PackageStatus::PROCESSING->value, $historyRecord->details['old_status']);
        $this->assertEquals(PackageStatus::READY->value, $historyRecord->details['new_status']);
        $this->assertEquals(2, $historyRecord->details['package_count']);
        $this->assertEquals('All packages processed', $historyRecord->details['reason']);
    }

    /** @test */
    public function it_can_retrieve_consolidation_history()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create();

        // Create multiple history records
        ConsolidationHistory::factory()->consolidated()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'performed_at' => now()->subDays(5),
        ]);

        ConsolidationHistory::factory()->statusChanged()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'performed_at' => now()->subDays(3),
        ]);

        ConsolidationHistory::factory()->unconsolidated()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'performed_at' => now()->subDays(1),
        ]);

        // Get history
        $history = $this->consolidationService->getConsolidationHistory($consolidatedPackage);

        $this->assertCount(3, $history);
        
        // Should be ordered by performed_at desc
        $this->assertEquals('unconsolidated', $history->first()->action);
        $this->assertEquals('consolidated', $history->last()->action);
    }

    /** @test */
    public function it_can_filter_consolidation_history_by_action()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create();

        ConsolidationHistory::factory()->consolidated()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
        ]);

        ConsolidationHistory::factory()->statusChanged()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
        ]);

        // Get only consolidated actions
        $history = $this->consolidationService->getConsolidationHistory(
            $consolidatedPackage,
            ['action' => 'consolidated']
        );

        $this->assertCount(1, $history);
        $this->assertEquals('consolidated', $history->first()->action);
    }

    /** @test */
    public function it_can_filter_consolidation_history_by_date_range()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create();

        // Old record
        ConsolidationHistory::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'performed_at' => now()->subDays(45),
        ]);

        // Recent record
        ConsolidationHistory::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'performed_at' => now()->subDays(15),
        ]);

        // Get only recent records
        $history = $this->consolidationService->getConsolidationHistory(
            $consolidatedPackage,
            ['days' => 30]
        );

        $this->assertCount(1, $history);
        $this->assertTrue($history->first()->performed_at->greaterThan(now()->subDays(30)));
    }

    /** @test */
    public function it_can_get_consolidation_history_summary()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create();

        // Create various history records
        ConsolidationHistory::factory()->consolidated()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'performed_at' => now()->subDays(10),
        ]);

        ConsolidationHistory::factory()->statusChanged()->count(2)->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'performed_at' => now()->subDays(5),
        ]);

        ConsolidationHistory::factory()->unconsolidated()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'performed_at' => now()->subDays(1),
        ]);

        $summary = $this->consolidationService->getConsolidationHistorySummary($consolidatedPackage);

        $this->assertEquals(4, $summary['total_actions']);
        $this->assertEquals(1, $summary['actions_by_type']['consolidated']);
        $this->assertEquals(2, $summary['actions_by_type']['status_changed']);
        $this->assertEquals(1, $summary['actions_by_type']['unconsolidated']);
        $this->assertNotNull($summary['first_action']);
        $this->assertNotNull($summary['last_action']);
    }

    /** @test */
    public function it_can_export_consolidation_audit_trail_as_array()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        ConsolidationHistory::factory()->consolidated()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'performed_by' => $this->admin->id,
        ]);

        $exportData = $this->consolidationService->exportConsolidationAuditTrail(
            $consolidatedPackage,
            'array'
        );

        $this->assertIsArray($exportData);
        $this->assertArrayHasKey('export_generated_at', $exportData);
        $this->assertArrayHasKey('consolidated_package', $exportData);
        $this->assertArrayHasKey('audit_trail', $exportData);

        $packageInfo = $exportData['consolidated_package'];
        $this->assertEquals($consolidatedPackage->id, $packageInfo['consolidated_package_id']);
        $this->assertEquals($this->customer->name, $packageInfo['customer_name']);

        $this->assertCount(1, $exportData['audit_trail']);
        $auditRecord = $exportData['audit_trail'][0];
        $this->assertEquals('consolidated', $auditRecord['action']);
        $this->assertEquals($this->admin->name, $auditRecord['performed_by_name']);
    }

    /** @test */
    public function it_can_export_consolidation_audit_trail_as_json()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create();
        ConsolidationHistory::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
        ]);

        $exportData = $this->consolidationService->exportConsolidationAuditTrail(
            $consolidatedPackage,
            'json'
        );

        $this->assertIsString($exportData);
        $decoded = json_decode($exportData, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('consolidated_package', $decoded);
        $this->assertArrayHasKey('audit_trail', $decoded);
    }

    /** @test */
    public function it_can_export_consolidation_audit_trail_as_csv()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'consolidated_tracking_number' => 'CONS-20250815-0001',
        ]);
        
        ConsolidationHistory::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
        ]);

        $exportData = $this->consolidationService->exportConsolidationAuditTrail(
            $consolidatedPackage,
            'csv'
        );

        $this->assertIsString($exportData);
        $this->assertStringContainsString('CONS-20250815-0001', $exportData);
        $this->assertStringContainsString('Action,Action Description,Performed At', $exportData);
    }

    /** @test */
    public function it_can_get_consolidation_statistics()
    {
        $customer1 = User::factory()->create();
        $customer2 = User::factory()->create();

        $consolidatedPackage1 = ConsolidatedPackage::factory()->create(['customer_id' => $customer1->id]);
        $consolidatedPackage2 = ConsolidatedPackage::factory()->create(['customer_id' => $customer2->id]);

        // Create history records
        ConsolidationHistory::factory()->consolidated()->create([
            'consolidated_package_id' => $consolidatedPackage1->id,
            'performed_at' => now()->subDays(5),
        ]);

        ConsolidationHistory::factory()->statusChanged()->create([
            'consolidated_package_id' => $consolidatedPackage1->id,
            'performed_at' => now()->subDays(3),
        ]);

        ConsolidationHistory::factory()->unconsolidated()->create([
            'consolidated_package_id' => $consolidatedPackage2->id,
            'performed_at' => now()->subDays(1),
        ]);

        $stats = $this->consolidationService->getConsolidationStatistics();

        $this->assertEquals(3, $stats['total_actions']);
        $this->assertEquals(1, $stats['actions_by_type']['consolidated']);
        $this->assertEquals(1, $stats['actions_by_type']['status_changed']);
        $this->assertEquals(1, $stats['actions_by_type']['unconsolidated']);
        $this->assertEquals(2, $stats['unique_consolidated_packages']);
    }

    /** @test */
    public function it_can_filter_consolidation_statistics_by_customer()
    {
        $customer1 = User::factory()->create();
        $customer2 = User::factory()->create();

        $consolidatedPackage1 = ConsolidatedPackage::factory()->create(['customer_id' => $customer1->id]);
        $consolidatedPackage2 = ConsolidatedPackage::factory()->create(['customer_id' => $customer2->id]);

        ConsolidationHistory::factory()->create(['consolidated_package_id' => $consolidatedPackage1->id]);
        ConsolidationHistory::factory()->create(['consolidated_package_id' => $consolidatedPackage2->id]);

        $stats = $this->consolidationService->getConsolidationStatistics([
            'customer_id' => $customer1->id,
        ]);

        $this->assertEquals(1, $stats['total_actions']);
        $this->assertEquals(1, $stats['unique_consolidated_packages']);
    }

    /** @test */
    public function consolidation_actions_are_logged_to_laravel_log()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Package consolidation action: consolidated', \Mockery::type('array'));

        Log::shouldReceive('info')
            ->once()
            ->with('Packages consolidated successfully', \Mockery::type('array'));

        $packages = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::PROCESSING,
        ]);

        $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->admin
        );
    }
}