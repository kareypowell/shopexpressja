<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ConsolidatedPackage;
use App\Models\ConsolidationHistory;
use App\Http\Livewire\ConsolidationHistory as ConsolidationHistoryComponent;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsolidationHistoryComponentTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customer;
    protected $consolidatedPackage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        
        $this->admin = User::factory()->create(['role_id' => 1]);
        $this->customer = User::factory()->create(['role_id' => 2]);
        
        $this->consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
        ]);
    }

    /** @test */
    public function it_can_show_consolidation_history_modal()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(ConsolidationHistoryComponent::class)
            ->call('showHistory', $this->consolidatedPackage)
            ->assertSet('showModal', true)
            ->assertSet('consolidatedPackage.id', $this->consolidatedPackage->id);
    }

    /** @test */
    public function it_can_close_consolidation_history_modal()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(ConsolidationHistoryComponent::class)
            ->call('showHistory', $this->consolidatedPackage)
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false)
            ->assertSet('consolidatedPackage', null);
    }

    /** @test */
    public function it_displays_consolidation_history_records()
    {
        $this->actingAs($this->admin);

        // Create history records
        $history1 = ConsolidationHistory::factory()->consolidated()->create([
            'consolidated_package_id' => $this->consolidatedPackage->id,
            'performed_by' => $this->admin->id,
            'performed_at' => now()->subDays(2),
        ]);

        $history2 = ConsolidationHistory::factory()->statusChanged()->create([
            'consolidated_package_id' => $this->consolidatedPackage->id,
            'performed_by' => $this->admin->id,
            'performed_at' => now()->subDays(1),
        ]);

        $component = Livewire::test(ConsolidationHistoryComponent::class, [
            'consolidatedPackage' => $this->consolidatedPackage
        ])
            ->call('showHistory', $this->consolidatedPackage)
            ->assertSee($history1->action_description)
            ->assertSee($history2->action_description)
            ->assertSee($this->admin->name);
    }

    /** @test */
    public function it_can_filter_history_by_action()
    {
        $this->actingAs($this->admin);

        ConsolidationHistory::factory()->consolidated()->create([
            'consolidated_package_id' => $this->consolidatedPackage->id,
        ]);

        ConsolidationHistory::factory()->statusChanged()->create([
            'consolidated_package_id' => $this->consolidatedPackage->id,
        ]);

        $component = Livewire::test(ConsolidationHistoryComponent::class, [
            'consolidatedPackage' => $this->consolidatedPackage
        ])
            ->call('showHistory', $this->consolidatedPackage)
            ->set('filterAction', 'consolidated')
            ->assertSee('Packages consolidated')
            ->assertDontSee('Status changed');
    }

    /** @test */
    public function it_can_filter_history_by_date_range()
    {
        $this->actingAs($this->admin);

        // Old record
        ConsolidationHistory::factory()->create([
            'consolidated_package_id' => $this->consolidatedPackage->id,
            'performed_at' => now()->subDays(45),
        ]);

        // Recent record
        $recentHistory = ConsolidationHistory::factory()->create([
            'consolidated_package_id' => $this->consolidatedPackage->id,
            'performed_at' => now()->subDays(15),
        ]);

        $component = Livewire::test(ConsolidationHistoryComponent::class, [
            'consolidatedPackage' => $this->consolidatedPackage
        ])
            ->call('showHistory', $this->consolidatedPackage)
            ->set('filterDays', '30');

        // Should only show the recent record
        $history = $component->get('history');
        $this->assertCount(1, $history);
    }

    /** @test */
    public function it_can_reset_filters()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(ConsolidationHistoryComponent::class, [
            'consolidatedPackage' => $this->consolidatedPackage
        ])
            ->call('showHistory', $this->consolidatedPackage)
            ->set('filterAction', 'consolidated')
            ->set('filterDays', '30')
            ->call('resetFilters')
            ->assertSet('filterAction', '')
            ->assertSet('filterDays', '');
    }

    /** @test */
    public function it_displays_history_summary()
    {
        $this->actingAs($this->admin);

        ConsolidationHistory::factory()->consolidated()->create([
            'consolidated_package_id' => $this->consolidatedPackage->id,
        ]);

        ConsolidationHistory::factory()->statusChanged()->count(2)->create([
            'consolidated_package_id' => $this->consolidatedPackage->id,
        ]);

        $component = Livewire::test(ConsolidationHistoryComponent::class, [
            'consolidatedPackage' => $this->consolidatedPackage
        ])
            ->call('showHistory', $this->consolidatedPackage);

        $summary = $component->get('historySummary');
        $this->assertEquals(3, $summary['total_actions']);
        $this->assertEquals(1, $summary['actions_by_type']['consolidated']);
        $this->assertEquals(2, $summary['actions_by_type']['status_changed']);
    }

    /** @test */
    public function it_can_show_export_modal()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(ConsolidationHistoryComponent::class, [
            'consolidatedPackage' => $this->consolidatedPackage
        ])
            ->call('showHistory', $this->consolidatedPackage)
            ->call('showExportModal')
            ->assertSet('showExportModal', true);
    }

    /** @test */
    public function it_can_close_export_modal()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(ConsolidationHistoryComponent::class, [
            'consolidatedPackage' => $this->consolidatedPackage
        ])
            ->call('showHistory', $this->consolidatedPackage)
            ->call('showExportModal')
            ->assertSet('showExportModal', true)
            ->call('closeExportModal')
            ->assertSet('showExportModal', false);
    }

    /** @test */
    public function it_requires_authorization_to_view_history()
    {
        // Create another customer's consolidated package
        $otherCustomer = User::factory()->create(['role_id' => 2]);
        $otherConsolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $otherCustomer->id,
        ]);

        $this->actingAs($this->customer);

        // Should not be able to view other customer's history
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::test(ConsolidationHistoryComponent::class)
            ->call('showHistory', $otherConsolidatedPackage);
    }

    /** @test */
    public function customer_can_view_their_own_consolidation_history()
    {
        $this->actingAs($this->customer);

        ConsolidationHistory::factory()->create([
            'consolidated_package_id' => $this->consolidatedPackage->id,
            'performed_by' => $this->admin->id,
        ]);

        $component = Livewire::test(ConsolidationHistoryComponent::class)
            ->call('showHistory', $this->consolidatedPackage)
            ->assertSet('showModal', true)
            ->assertSet('consolidatedPackage.id', $this->consolidatedPackage->id);
    }

    /** @test */
    public function it_paginates_history_records()
    {
        $this->actingAs($this->admin);

        // Create more than 10 history records (default pagination limit)
        ConsolidationHistory::factory()->count(15)->create([
            'consolidated_package_id' => $this->consolidatedPackage->id,
        ]);

        $component = Livewire::test(ConsolidationHistoryComponent::class, [
            'consolidatedPackage' => $this->consolidatedPackage
        ])
            ->call('showHistory', $this->consolidatedPackage);

        $history = $component->get('history');
        $this->assertEquals(10, $history->perPage());
        $this->assertTrue($history->hasPages());
    }

    /** @test */
    public function it_shows_empty_state_when_no_history_exists()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(ConsolidationHistoryComponent::class, [
            'consolidatedPackage' => $this->consolidatedPackage
        ])
            ->call('showHistory', $this->consolidatedPackage)
            ->assertSee('No history records found');
    }

    /** @test */
    public function it_displays_formatted_details_for_each_action_type()
    {
        $this->actingAs($this->admin);

        $consolidatedHistory = ConsolidationHistory::factory()->consolidated()->create([
            'consolidated_package_id' => $this->consolidatedPackage->id,
            'details' => [
                'package_count' => 3,
                'total_weight' => 15.5,
                'total_cost' => 125.00,
            ],
        ]);

        $statusChangedHistory = ConsolidationHistory::factory()->statusChanged()->create([
            'consolidated_package_id' => $this->consolidatedPackage->id,
            'details' => [
                'old_status' => 'processing',
                'new_status' => 'ready',
                'package_count' => 3,
            ],
        ]);

        $component = Livewire::test(ConsolidationHistoryComponent::class, [
            'consolidatedPackage' => $this->consolidatedPackage
        ])
            ->call('showHistory', $this->consolidatedPackage)
            ->assertSee('Package Count: 3')
            ->assertSee('Total Weight: 15.50 lbs')
            ->assertSee('Total Cost: $125.00')
            ->assertSee('From Status: Processing')
            ->assertSee('To Status: Ready');
    }
}