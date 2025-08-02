<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Role;
use App\Http\Livewire\Customers\AdminCustomersTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class CustomerAdvancedFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $customerRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'superadmin', 'description' => 'Super Administrator']);
        $adminRole = Role::create(['name' => 'admin', 'description' => 'Administrator']);
        $this->customerRole = Role::create(['name' => 'customer', 'description' => 'Customer']);
        
        // Create admin user for authorization
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
    }

    /** @test */
    public function it_can_toggle_advanced_filters()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(AdminCustomersTable::class);

        $this->assertFalse($component->get('advancedFilters'));

        $component->call('toggleAdvancedFilters');

        $this->assertTrue($component->get('advancedFilters'));
    }

    /** @test */
    public function it_can_apply_advanced_search_criteria()
    {
        $customer = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role_id' => $this->customerRole->id,
        ]);

        Profile::factory()->create(['user_id' => $customer->id]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(AdminCustomersTable::class)
            ->set('advancedSearchCriteria.name', 'John')
            ->call('applyAdvancedSearch');

        $this->assertTrue($component->instance()->hasAdvancedSearchCriteria());
    }

    /** @test */
    public function it_can_clear_advanced_search_criteria()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(AdminCustomersTable::class)
            ->set('advancedSearchCriteria.name', 'John')
            ->set('advancedSearchCriteria.email', 'john@example.com')
            ->call('clearAdvancedSearch');

        $criteria = $component->get('advancedSearchCriteria');
        $this->assertEquals('', $criteria['name']);
        $this->assertEquals('', $criteria['email']);
        $this->assertEquals('active', $criteria['status']); // Should reset to default
    }

    /** @test */
    public function it_can_toggle_search_performance_mode()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(AdminCustomersTable::class);

        $this->assertFalse($component->get('searchPerformanceMode'));

        $component->call('toggleSearchPerformanceMode');

        $this->assertTrue($component->get('searchPerformanceMode'));
    }

    /** @test */
    public function it_saves_filter_state_to_session()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(AdminCustomersTable::class)
            ->set('advancedFilters', true)
            ->set('advancedSearchCriteria.name', 'John')
            ->set('searchPerformanceMode', true)
            ->call('saveFilterState');

        $savedFilters = session()->get('admin_customers_filters');

        $this->assertTrue($savedFilters['advancedFilters']);
        $this->assertEquals('John', $savedFilters['advancedSearchCriteria']['name']);
        $this->assertTrue($savedFilters['searchPerformanceMode']);
    }

    /** @test */
    public function it_loads_filter_state_from_session()
    {
        // Set up session data
        session()->put('admin_customers_filters', [
            'advancedFilters' => true,
            'advancedSearchCriteria' => ['name' => 'Jane', 'email' => 'jane@example.com'],
            'searchPerformanceMode' => true,
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(AdminCustomersTable::class)
            ->call('loadFilterState');

        $this->assertTrue($component->get('advancedFilters'));
        $this->assertEquals('Jane', $component->get('advancedSearchCriteria.name'));
        $this->assertEquals('jane@example.com', $component->get('advancedSearchCriteria.email'));
        $this->assertTrue($component->get('searchPerformanceMode'));
    }

    /** @test */
    public function it_can_clear_saved_filter_state()
    {
        // Set up session data
        session()->put('admin_customers_filters', [
            'advancedFilters' => true,
            'advancedSearchCriteria' => ['name' => 'Jane'],
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(AdminCustomersTable::class)
            ->call('clearSavedFilterState');

        $this->assertNull(session()->get('admin_customers_filters'));
    }

    /** @test */
    public function it_generates_filter_summary()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(AdminCustomersTable::class)
            ->set('advancedSearchCriteria.name', 'John')
            ->set('advancedSearchCriteria.email', 'john@example.com')
            ->set('advancedSearchCriteria.parish', 'Kingston')
            ->set('advancedSearchCriteria.status', 'deleted');

        $summary = $component->instance()->getFilterSummary();

        $this->assertContains('Name: John', $summary);
        $this->assertContains('Email: john@example.com', $summary);
        $this->assertContains('Parish: Kingston', $summary);
        $this->assertContains('Status: Deleted', $summary);
    }

    /** @test */
    public function it_detects_when_advanced_search_criteria_are_set()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(AdminCustomersTable::class);

        // Initially no criteria set
        $this->assertFalse($component->instance()->hasAdvancedSearchCriteria());

        // Set some criteria
        $component->set('advancedSearchCriteria.name', 'John');

        $this->assertTrue($component->instance()->hasAdvancedSearchCriteria());
    }

    /** @test */
    public function it_ignores_status_field_when_checking_for_advanced_criteria()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(AdminCustomersTable::class)
            ->set('advancedSearchCriteria.status', 'deleted');

        // Status alone should not count as advanced criteria
        $this->assertFalse($component->instance()->hasAdvancedSearchCriteria());

        // But adding another field should
        $component->set('advancedSearchCriteria.name', 'John');

        $this->assertTrue($component->instance()->hasAdvancedSearchCriteria());
    }

    /** @test */
    public function it_can_export_search_results()
    {
        $customer = User::factory()->create(['role_id' => $this->customerRole->id]);
        Profile::factory()->create(['user_id' => $customer->id]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(AdminCustomersTable::class)
            ->call('exportSearchResults');

        // Should not throw an error and should dispatch browser event
        $component->assertDispatchedBrowserEvent('show-alert');
    }

    /** @test */
    public function it_updates_filter_state_when_criteria_change()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(AdminCustomersTable::class)
            ->set('advancedSearchCriteria.name', 'John');

        // Check that session was updated
        $savedFilters = session()->get('admin_customers_filters');
        $this->assertEquals('John', $savedFilters['advancedSearchCriteria']['name']);
    }

    /** @test */
    public function it_provides_search_statistics()
    {
        $customer = User::factory()->create([
            'first_name' => 'John',
            'role_id' => $this->customerRole->id,
        ]);
        Profile::factory()->create(['user_id' => $customer->id]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(AdminCustomersTable::class)
            ->set('searchHighlight', 'John');

        $stats = $component->instance()->getSearchStats();

        $this->assertArrayHasKey('total_results', $stats);
        $this->assertArrayHasKey('search_term', $stats);
        $this->assertArrayHasKey('advanced_filters_active', $stats);
        $this->assertArrayHasKey('performance_mode', $stats);
        $this->assertEquals('John', $stats['search_term']);
    }
}