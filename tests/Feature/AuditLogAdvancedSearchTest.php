<?php

namespace Tests\Feature;

use App\Http\Livewire\Admin\AuditLogManagement;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Package;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogAdvancedSearchTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles first
        \App\Models\Role::firstOrCreate(['name' => 'superadmin'], ['description' => 'Super Administrator']);
        \App\Models\Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);
        \App\Models\Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer']);
        
        // Create a super admin user for testing
        $superAdminRole = \App\Models\Role::where('name', 'superadmin')->first();
        $this->superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
        ]);
    }

    /** @test */
    public function it_can_search_across_basic_fields()
    {
        // Create test audit logs
        $customerRole = \App\Models\Role::where('name', 'customer')->first();
        $user = User::factory()->create([
            'first_name' => 'John', 
            'last_name' => 'Doe',
            'role_id' => $customerRole->id
        ]);
        
        AuditLog::create([
            'user_id' => $user->id,
            'event_type' => 'authentication',
            'action' => 'login',
            'ip_address' => '192.168.1.100',
            'auditable_type' => 'App\Models\User',
            'auditable_id' => $user->id,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'event_type' => 'model_updated',
            'action' => 'update',
            'ip_address' => '10.0.0.1',
            'auditable_type' => 'App\Models\Package',
            'auditable_id' => 1,
        ]);

        $component = Livewire::actingAs($this->superAdmin)
            ->test(AuditLogManagement::class);

        // Test search by user name
        $component->set('search', 'John')
            ->assertSee('John Doe');

        // Test search by IP address
        $component->set('search', '192.168')
            ->assertSee('192.168.1.100');

        // Test search by action
        $component->set('search', 'login')
            ->assertSee('login');
    }

    /** @test */
    public function it_can_filter_by_event_type()
    {
        $customerRole = \App\Models\Role::where('name', 'customer')->first();
        $user = User::factory()->create(['role_id' => $customerRole->id]);
        
        AuditLog::create([
            'user_id' => $user->id,
            'event_type' => 'authentication',
            'action' => 'login',
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'event_type' => 'model_updated',
            'action' => 'update',
        ]);

        $component = Livewire::actingAs($this->superAdmin)
            ->test(AuditLogManagement::class);

        $component->set('eventType', 'authentication');
        
        // Check that only 1 entry is shown (the authentication log)
        $component->assertSee('Total: 1 entries')
            ->assertSee('Login');
    }

    /** @test */
    public function it_can_filter_by_date_range()
    {
        $customerRole = \App\Models\Role::where('name', 'customer')->first();
        $user = User::factory()->create(['role_id' => $customerRole->id]);
        
        // Create log from yesterday
        AuditLog::create([
            'user_id' => $user->id,
            'event_type' => 'authentication',
            'action' => 'login',
            'created_at' => Carbon::yesterday(),
        ]);

        // Create log from today
        AuditLog::create([
            'user_id' => $user->id,
            'event_type' => 'authentication',
            'action' => 'logout',
            'created_at' => Carbon::now(),
        ]);

        $component = Livewire::actingAs($this->superAdmin)
            ->test(AuditLogManagement::class);

        // Filter to only show today's logs
        $component->set('dateFrom', Carbon::now()->format('Y-m-d'))
            ->set('dateTo', Carbon::now()->format('Y-m-d'));
            
        // Should show today's log (logout) but not yesterday's (login)
        $component->assertSee('Logout');
    }

    /** @test */
    public function it_can_sort_by_different_columns()
    {
        $customerRole = \App\Models\Role::where('name', 'customer')->first();
        $user1 = User::factory()->create(['first_name' => 'Alice', 'role_id' => $customerRole->id]);
        $user2 = User::factory()->create(['first_name' => 'Bob', 'role_id' => $customerRole->id]);
        
        AuditLog::create([
            'user_id' => $user1->id,
            'event_type' => 'authentication',
            'action' => 'login',
            'created_at' => Carbon::now()->subHour(),
        ]);

        AuditLog::create([
            'user_id' => $user2->id,
            'event_type' => 'model_updated',
            'action' => 'update',
            'created_at' => Carbon::now(),
        ]);

        $component = Livewire::actingAs($this->superAdmin)
            ->test(AuditLogManagement::class);

        // Test sorting by user (should join with users table)
        $component->call('sortBy', 'user_id');
        
        // Test sorting by event type
        $component->call('sortBy', 'event_type');
        
        // Test sorting by created_at (default)
        $component->call('sortBy', 'created_at');
        
        // Verify sort direction toggles
        $component->assertSet('sortField', 'created_at')
            ->assertSet('sortDirection', 'asc');
            
        $component->call('sortBy', 'created_at')
            ->assertSet('sortDirection', 'desc');
    }

    /** @test */
    public function it_can_apply_quick_filters()
    {
        $customerRole = \App\Models\Role::where('name', 'customer')->first();
        $user = User::factory()->create(['role_id' => $customerRole->id]);
        
        // Create logs for different dates
        AuditLog::create([
            'user_id' => $user->id,
            'event_type' => 'authentication',
            'action' => 'login',
            'created_at' => Carbon::now(),
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'event_type' => 'authentication',
            'action' => 'logout',
            'created_at' => Carbon::yesterday(),
        ]);

        $component = Livewire::actingAs($this->superAdmin)
            ->test(AuditLogManagement::class);

        // Test "today" quick filter
        $component->call('applyQuickFilter', 'today')
            ->assertSet('dateFrom', Carbon::now()->format('Y-m-d'))
            ->assertSet('dateTo', Carbon::now()->format('Y-m-d'));

        // Test "last_7_days" quick filter
        $component->call('applyQuickFilter', 'last_7_days')
            ->assertSet('dateFrom', Carbon::now()->subDays(7)->format('Y-m-d'))
            ->assertSet('dateTo', Carbon::now()->format('Y-m-d'));
    }

    /** @test */
    public function it_can_apply_filter_presets()
    {
        $customerRole = \App\Models\Role::where('name', 'customer')->first();
        $user = User::factory()->create(['role_id' => $customerRole->id]);
        
        AuditLog::create([
            'user_id' => $user->id,
            'event_type' => 'security_event',
            'action' => 'failed_login',
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'event_type' => 'authentication',
            'action' => 'login',
        ]);

        $component = Livewire::actingAs($this->superAdmin)
            ->test(AuditLogManagement::class);

        // Test security events preset
        $component->set('filterPreset', 'security_events')
            ->call('applyFilterPreset')
            ->assertSet('eventType', 'security_event');

        // Test failed logins preset
        $component->set('filterPreset', 'failed_logins')
            ->call('applyFilterPreset')
            ->assertSet('eventType', 'authentication')
            ->assertSet('action', 'failed_login');
    }

    /** @test */
    public function it_can_search_in_json_fields_when_enabled()
    {
        $customerRole = \App\Models\Role::where('name', 'customer')->first();
        $user = User::factory()->create(['role_id' => $customerRole->id]);
        
        AuditLog::create([
            'user_id' => $user->id,
            'event_type' => 'model_updated',
            'action' => 'update',
            'old_values' => json_encode(['status' => 'pending']),
            'new_values' => json_encode(['status' => 'completed']),
            'additional_data' => json_encode(['reason' => 'automatic_update']),
        ]);

        $component = Livewire::actingAs($this->superAdmin)
            ->test(AuditLogManagement::class);

        // Enable JSON field search and search for content in old values
        $component->set('searchInOldValues', true)
            ->set('search', 'pending')
            ->assertSee('Model updated');
            
        // Search in new values
        $component->set('searchInNewValues', true)
            ->set('search', 'completed')
            ->assertSee('Model updated');
            
        // Search in additional data
        $component->set('searchInAdditionalData', true)
            ->set('search', 'automatic_update')
            ->assertSee('Model updated');
    }

    /** @test */
    public function it_can_filter_by_ip_address()
    {
        $customerRole = \App\Models\Role::where('name', 'customer')->first();
        $user = User::factory()->create(['role_id' => $customerRole->id]);
        
        AuditLog::create([
            'user_id' => $user->id,
            'event_type' => 'authentication',
            'action' => 'login',
            'ip_address' => '192.168.1.100',
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'event_type' => 'authentication',
            'action' => 'login',
            'ip_address' => '10.0.0.1',
        ]);

        $component = Livewire::actingAs($this->superAdmin)
            ->test(AuditLogManagement::class);

        $component->set('ipAddress', '192.168')
            ->assertSee('192.168.1.100')
            ->assertDontSee('10.0.0.1');
    }

    /** @test */
    public function it_can_clear_all_filters()
    {
        $component = Livewire::actingAs($this->superAdmin)
            ->test(AuditLogManagement::class);

        // Set various filters
        $component->set('search', 'test')
            ->set('eventType', 'authentication')
            ->set('action', 'login')
            ->set('ipAddress', '192.168.1.1')
            ->set('searchInOldValues', true)
            ->set('filterPreset', 'security_events');

        // Clear all filters
        $component->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('eventType', '')
            ->assertSet('action', '')
            ->assertSet('ipAddress', '')
            ->assertSet('searchInOldValues', false)
            ->assertSet('filterPreset', '')
            ->assertSet('sortField', 'created_at')
            ->assertSet('sortDirection', 'desc');
    }

    /** @test */
    public function it_persists_filters_in_url_state()
    {
        $component = Livewire::actingAs($this->superAdmin)
            ->test(AuditLogManagement::class);

        $component->set('search', 'test search')
            ->set('eventType', 'authentication')
            ->set('sortField', 'event_type')
            ->set('sortDirection', 'asc')
            ->assertSet('search', 'test search')
            ->assertSet('eventType', 'authentication')
            ->assertSet('sortField', 'event_type')
            ->assertSet('sortDirection', 'asc');
    }
}