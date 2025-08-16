<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\ConsolidationHistory;
use App\Models\ConsolidatedPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsolidationHistorySimpleTest extends TestCase
{
    use RefreshDatabase;

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
    public function it_provides_action_description()
    {
        $history1 = ConsolidationHistory::factory()->create(['action' => 'consolidated']);
        $history2 = ConsolidationHistory::factory()->create(['action' => 'unconsolidated']);
        $history3 = ConsolidationHistory::factory()->create(['action' => 'status_changed']);

        $this->assertEquals('Packages consolidated', $history1->action_description);
        $this->assertEquals('Packages unconsolidated', $history2->action_description);
        $this->assertEquals('Status changed', $history3->action_description);
    }
}