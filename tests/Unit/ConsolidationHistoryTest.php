<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\ConsolidationHistory;
use App\Models\ConsolidatedPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsolidationHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** @test */
    public function it_can_create_consolidation_history_record()
    {
        $user = User::factory()->create();
        $consolidatedPackage = ConsolidatedPackage::factory()->create();

        $history = ConsolidationHistory::create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'action' => 'consolidated',
            'performed_by' => $user->id,
            'details' => [
                'package_count' => 3,
                'total_weight' => 15.5,
                'total_cost' => 125.00,
            ],
            'performed_at' => now(),
        ]);

        $this->assertDatabaseHas('consolidation_history', [
            'id' => $history->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'action' => 'consolidated',
            'performed_by' => $user->id,
        ]);

        $this->assertEquals([
            'package_count' => 3,
            'total_weight' => 15.5,
            'total_cost' => 125.00,
        ], $history->details);
    }

    /** @test */
    public function it_belongs_to_consolidated_package()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create();
        $history = ConsolidationHistory::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
        ]);

        $this->assertInstanceOf(ConsolidatedPackage::class, $history->consolidatedPackage);
        $this->assertEquals($consolidatedPackage->id, $history->consolidatedPackage->id);
    }

    /** @test */
    public function it_belongs_to_user_who_performed_action()
    {
        $user = User::factory()->create();
        $history = ConsolidationHistory::factory()->create([
            'performed_by' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $history->performedBy);
        $this->assertEquals($user->id, $history->performedBy->id);
    }

    /** @test */
    public function it_can_filter_by_action()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create();
        
        ConsolidationHistory::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'action' => 'consolidated',
        ]);
        
        ConsolidationHistory::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'action' => 'status_changed',
        ]);
        
        ConsolidationHistory::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'action' => 'unconsolidated',
        ]);

        $consolidatedRecords = ConsolidationHistory::byAction('consolidated')->get();
        $statusChangedRecords = ConsolidationHistory::byAction('status_changed')->get();

        $this->assertCount(1, $consolidatedRecords);
        $this->assertCount(1, $statusChangedRecords);
        $this->assertEquals('consolidated', $consolidatedRecords->first()->action);
        $this->assertEquals('status_changed', $statusChangedRecords->first()->action);
    }

    /** @test */
    public function it_can_filter_recent_records()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create();
        
        // Create old record
        ConsolidationHistory::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'performed_at' => now()->subDays(45),
        ]);
        
        // Create recent record
        ConsolidationHistory::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'performed_at' => now()->subDays(15),
        ]);

        $recentRecords = ConsolidationHistory::recent(30)->get();

        $this->assertCount(1, $recentRecords);
        $this->assertTrue($recentRecords->first()->performed_at->greaterThan(now()->subDays(30)));
    }

    /** @test */
    public function it_provides_action_description()
    {
        $history1 = ConsolidationHistory::factory()->create(['action' => 'consolidated']);
        $history2 = ConsolidationHistory::factory()->create(['action' => 'unconsolidated']);
        $history3 = ConsolidationHistory::factory()->create(['action' => 'status_changed']);
        $history4 = ConsolidationHistory::factory()->create(['action' => 'custom_action']);

        $this->assertEquals('Packages consolidated', $history1->action_description);
        $this->assertEquals('Packages unconsolidated', $history2->action_description);
        $this->assertEquals('Status changed', $history3->action_description);
        $this->assertEquals('Custom_action', $history4->action_description);
    }

    /** @test */
    public function it_formats_details_for_consolidated_action()
    {
        $history = ConsolidationHistory::factory()->create([
            'action' => 'consolidated',
            'details' => [
                'package_count' => 3,
                'total_weight' => 15.75,
                'total_cost' => 125.50,
                'package_ids' => [1, 2, 3],
            ],
        ]);

        $formatted = $history->formatted_details;

        $this->assertEquals('3', $formatted['Package Count']);
        $this->assertEquals('15.75 lbs', $formatted['Total Weight']);
        $this->assertEquals('$125.50', $formatted['Total Cost']);
        $this->assertEquals('1, 2, 3', $formatted['Package IDs']);
    }

    /** @test */
    public function it_formats_details_for_unconsolidated_action()
    {
        $history = ConsolidationHistory::factory()->create([
            'action' => 'unconsolidated',
            'details' => [
                'package_count' => 2,
                'reason' => 'Customer request',
                'package_ids' => [4, 5],
            ],
        ]);

        $formatted = $history->formatted_details;

        $this->assertEquals('2', $formatted['Package Count']);
        $this->assertEquals('Customer request', $formatted['Reason']);
        $this->assertEquals('4, 5', $formatted['Package IDs']);
    }

    /** @test */
    public function it_formats_details_for_status_changed_action()
    {
        $history = ConsolidationHistory::factory()->create([
            'action' => 'status_changed',
            'details' => [
                'old_status' => 'processing',
                'new_status' => 'ready',
                'package_count' => 3,
                'reason' => 'All packages processed',
            ],
        ]);

        $formatted = $history->formatted_details;

        $this->assertEquals('Processing', $formatted['From Status']);
        $this->assertEquals('Ready', $formatted['To Status']);
        $this->assertEquals('3', $formatted['Package Count']);
        $this->assertEquals('All packages processed', $formatted['Reason']);
    }

    /** @test */
    public function it_handles_missing_details_gracefully()
    {
        $history = ConsolidationHistory::factory()->create([
            'action' => 'consolidated',
            'details' => null,
        ]);

        $formatted = $history->formatted_details;

        $this->assertEquals('N/A', $formatted['Package Count']);
        $this->assertEquals('N/A', $formatted['Total Weight']);
        $this->assertEquals('N/A', $formatted['Total Cost']);
    }
}