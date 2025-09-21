<?php

namespace Tests\Feature;

use App\Http\Livewire\Admin\AuditLogManagement;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogUITest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles first
        Role::firstOrCreate(['name' => 'superadmin'], ['description' => 'Super Administrator']);
        Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);
        Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer']);
        
        // Create a super admin user for testing
        $superAdminRole = Role::where('name', 'superadmin')->first();
        $this->superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
        ]);
    }

    /** @test */
    public function it_shows_clean_search_interface()
    {
        $component = Livewire::actingAs($this->superAdmin)
            ->test(AuditLogManagement::class);

        // Should show main search bar
        $component->assertSee('Search audit logs...');
        
        // Should show quick action buttons
        $component->assertSee('Quick Dates');
        $component->assertSee('Presets');
        $component->assertSee('Filters');
    }

    /** @test */
    public function it_shows_filter_count_when_filters_applied()
    {
        $component = Livewire::actingAs($this->superAdmin)
            ->test(AuditLogManagement::class);

        // Apply some filters
        $component->set('eventType', 'authentication')
            ->set('action', 'login');

        // Should show filter count badge
        $component->assertSee('2'); // Filter count badge
    }

    /** @test */
    public function it_shows_filtered_results_count()
    {
        $customerRole = Role::where('name', 'customer')->first();
        $user = User::factory()->create(['role_id' => $customerRole->id]);
        
        AuditLog::create([
            'user_id' => $user->id,
            'event_type' => 'authentication',
            'action' => 'login',
        ]);

        $component = Livewire::actingAs($this->superAdmin)
            ->test(AuditLogManagement::class);

        $component->set('eventType', 'authentication');

        // Should show filtered results count
        $component->assertSee('filtered results');
    }

    /** @test */
    public function it_can_clear_filters_and_reset_ui()
    {
        $component = Livewire::actingAs($this->superAdmin)
            ->test(AuditLogManagement::class);

        // Apply filters
        $component->set('eventType', 'authentication')
            ->set('action', 'login')
            ->set('search', 'test');

        // Clear filters
        $component->call('clearFilters');

        // Should reset all filters
        $component->assertSet('eventType', '')
            ->assertSet('action', '')
            ->assertSet('search', '');
    }

    /** @test */
    public function it_maintains_clean_layout_structure()
    {
        $component = Livewire::actingAs($this->superAdmin)
            ->test(AuditLogManagement::class);

        // Should have proper section structure
        $component->assertSee('Audit Logs'); // Header
        $component->assertSee('Search audit logs...'); // Search section
        $component->assertSee('Show:'); // Pagination controls
    }
}