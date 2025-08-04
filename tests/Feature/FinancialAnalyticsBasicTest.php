<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Livewire\FinancialAnalytics;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class FinancialAnalyticsBasicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create and authenticate a test user
        $role = Role::factory()->create(['name' => 'superadmin']);
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'Admin',
            'role_id' => $role->id,
        ]);
        $this->actingAs($user);
    }

    /** @test */
    public function financial_analytics_component_can_render_without_errors()
    {
        $component = Livewire::test(FinancialAnalytics::class);
        
        $component->assertStatus(200);
        $component->assertSee('Total Revenue');
        $component->assertSee('Average Order Value');
    }

    /** @test */
    public function financial_analytics_handles_empty_data_gracefully()
    {
        $component = Livewire::test(FinancialAnalytics::class);
        
        // Should not throw errors even with no data
        $component->assertStatus(200);
        
        // Should show zero values for empty data
        $component->assertSee('$0.00'); // Total revenue should be 0
    }

    /** @test */
    public function financial_analytics_can_update_filters()
    {
        $component = Livewire::test(FinancialAnalytics::class);
        
        $filters = [
            'date_range' => '90',
            'custom_start' => '',
            'custom_end' => ''
        ];
        
        $component->call('updateFilters', $filters);
        
        $component->assertSet('dateRange', '90');
        $component->assertStatus(200);
    }
}