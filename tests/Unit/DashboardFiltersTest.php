<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Livewire\DashboardFilters;
use App\Models\Role;
use App\Models\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class DashboardFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['id' => 1, 'name' => 'superadmin', 'description' => 'Super Administrator']);
        Role::create(['id' => 2, 'name' => 'admin', 'description' => 'Administrator']);
        Role::create(['id' => 3, 'name' => 'customer', 'description' => 'Customer']);
        
        // Create test offices
        Office::create(['id' => 1, 'name' => 'Main Office']);
        Office::create(['id' => 2, 'name' => 'Branch Office']);
    }

    /** @test */
    public function it_can_render_dashboard_filters_component()
    {
        Livewire::test(DashboardFilters::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.dashboard-filters');
    }

    /** @test */
    public function it_initializes_with_default_values()
    {
        $component = Livewire::test(DashboardFilters::class);
        
        $this->assertEquals('30', $component->get('dateRange'));
        $this->assertEquals('', $component->get('customStartDate'));
        $this->assertEquals('', $component->get('customEndDate'));
        $this->assertEquals([], $component->get('selectedServiceTypes'));
        $this->assertEquals([], $component->get('selectedCustomerSegments'));
        $this->assertEquals([], $component->get('selectedPackageStatuses'));
        $this->assertEquals([], $component->get('selectedOffices'));
        $this->assertEquals('', $component->get('minOrderValue'));
        $this->assertEquals('', $component->get('maxOrderValue'));
        $this->assertEquals('all', $component->get('customerType'));
        $this->assertEquals(0, $component->get('activeFiltersCount'));
        $this->assertFalse($component->get('filtersApplied'));
        $this->assertFalse($component->get('showAdvancedFilters'));
    }

    /** @test */
    public function it_updates_date_range_correctly()
    {
        $component = Livewire::test(DashboardFilters::class)
            ->set('dateRange', '7')
            ->assertSet('dateRange', '7')
            ->assertEmitted('filtersUpdated');
    }

    /** @test */
    public function it_handles_custom_date_range()
    {
        $startDate = Carbon::now()->subDays(14)->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');
        
        $component = Livewire::test(DashboardFilters::class)
            ->set('customStartDate', $startDate)
            ->set('customEndDate', $endDate)
            ->assertSet('dateRange', 'custom')
            ->assertSet('customStartDate', $startDate)
            ->assertSet('customEndDate', $endDate)
            ->assertEmitted('filtersUpdated');
    }

    /** @test */
    public function it_updates_service_type_filters()
    {
        $component = Livewire::test(DashboardFilters::class)
            ->set('selectedServiceTypes', ['sea', 'air'])
            ->assertSet('selectedServiceTypes', ['sea', 'air'])
            ->assertEmitted('filtersUpdated');
    }

    /** @test */
    public function it_updates_customer_segment_filters()
    {
        $component = Livewire::test(DashboardFilters::class)
            ->set('selectedCustomerSegments', ['premium', 'high_value'])
            ->assertSet('selectedCustomerSegments', ['premium', 'high_value'])
            ->assertEmitted('filtersUpdated');
    }

    /** @test */
    public function it_updates_package_status_filters()
    {
        $component = Livewire::test(DashboardFilters::class)
            ->set('selectedPackageStatuses', ['delivered', 'in_transit'])
            ->assertSet('selectedPackageStatuses', ['delivered', 'in_transit'])
            ->assertEmitted('filtersUpdated');
    }

    /** @test */
    public function it_updates_office_filters()
    {
        $component = Livewire::test(DashboardFilters::class)
            ->set('selectedOffices', [1, 2])
            ->assertSet('selectedOffices', [1, 2])
            ->assertEmitted('filtersUpdated');
    }

    /** @test */
    public function it_updates_order_value_filters()
    {
        $component = Livewire::test(DashboardFilters::class)
            ->set('minOrderValue', '100')
            ->set('maxOrderValue', '1000')
            ->assertSet('minOrderValue', '100')
            ->assertSet('maxOrderValue', '1000')
            ->assertEmitted('filtersUpdated');
    }

    /** @test */
    public function it_updates_customer_type_filter()
    {
        $component = Livewire::test(DashboardFilters::class)
            ->set('customerType', 'new')
            ->assertSet('customerType', 'new')
            ->assertEmitted('filtersUpdated');
    }

    /** @test */
    public function it_calculates_active_filters_count_correctly()
    {
        $component = Livewire::test(DashboardFilters::class);
        
        // Initially no active filters
        $this->assertEquals(0, $component->get('activeFiltersCount'));
        
        // Add some filters
        $component->set('dateRange', '7')
                  ->set('selectedServiceTypes', ['sea'])
                  ->set('minOrderValue', '100')
                  ->set('customerType', 'new');
        
        // Should have 4 active filters (date_range changed from default, service_types, min_order_value, customer_type)
        $this->assertEquals(4, $component->get('activeFiltersCount'));
    }

    /** @test */
    public function it_resets_all_filters_correctly()
    {
        $component = Livewire::test(DashboardFilters::class)
            ->set('dateRange', '7')
            ->set('selectedServiceTypes', ['sea'])
            ->set('minOrderValue', '100')
            ->set('customerType', 'new')
            ->call('resetAllFilters');
        
        $this->assertEquals('30', $component->get('dateRange'));
        $this->assertEquals([], $component->get('selectedServiceTypes'));
        $this->assertEquals('', $component->get('minOrderValue'));
        $this->assertEquals('all', $component->get('customerType'));
        $this->assertEquals(0, $component->get('activeFiltersCount'));
        $this->assertFalse($component->get('filtersApplied'));
        
        $component->assertEmitted('filtersUpdated');
    }

    /** @test */
    public function it_toggles_advanced_filters_visibility()
    {
        $component = Livewire::test(DashboardFilters::class);
        
        $this->assertFalse($component->get('showAdvancedFilters'));
        
        $component->call('toggleAdvancedFilters');
        $this->assertTrue($component->get('showAdvancedFilters'));
        
        $component->call('toggleAdvancedFilters');
        $this->assertFalse($component->get('showAdvancedFilters'));
    }

    /** @test */
    public function it_returns_correct_filter_array()
    {
        $component = Livewire::test(DashboardFilters::class)
            ->set('dateRange', '7')
            ->set('selectedServiceTypes', ['sea'])
            ->set('minOrderValue', '100')
            ->set('customerType', 'new');
        
        $filterArray = $component->instance()->getFilterArray();
        
        $this->assertEquals('7', $filterArray['date_range']);
        $this->assertEquals(['sea'], $filterArray['service_types']);
        $this->assertEquals('100', $filterArray['min_order_value']);
        $this->assertEquals('new', $filterArray['customer_type']);
    }

    /** @test */
    public function it_saves_and_loads_filters_from_session()
    {
        // Set some filters
        $component = Livewire::test(DashboardFilters::class)
            ->set('dateRange', '7')
            ->set('selectedServiceTypes', ['sea'])
            ->set('minOrderValue', '100');
        
        // Check that filters are saved to session
        $savedFilters = Session::get('dashboard_filters');
        $this->assertEquals('7', $savedFilters['date_range']);
        $this->assertEquals(['sea'], $savedFilters['service_types']);
        $this->assertEquals('100', $savedFilters['min_order_value']);
        
        // Create new component instance and check it loads from session
        $newComponent = Livewire::test(DashboardFilters::class);
        $this->assertEquals('7', $newComponent->get('dateRange'));
        $this->assertEquals(['sea'], $newComponent->get('selectedServiceTypes'));
        $this->assertEquals('100', $newComponent->get('minOrderValue'));
        $this->assertTrue($newComponent->get('filtersApplied'));
    }

    /** @test */
    public function it_gets_formatted_date_range_correctly()
    {
        $component = Livewire::test(DashboardFilters::class);
        
        // Test default range
        $formatted = $component->instance()->getFormattedDateRange();
        $this->assertEquals('Last 30 days', $formatted);
        
        // Test custom range
        $startDate = '2023-01-01';
        $endDate = '2023-01-31';
        $component->set('dateRange', 'custom')
                  ->set('customStartDate', $startDate)
                  ->set('customEndDate', $endDate);
        
        $formatted = $component->instance()->getFormattedDateRange();
        $this->assertStringContainsString('Jan 1, 2023', $formatted);
        $this->assertStringContainsString('Jan 31, 2023', $formatted);
    }

    /** @test */
    public function it_gets_active_filters_summary_correctly()
    {
        $component = Livewire::test(DashboardFilters::class)
            ->set('dateRange', '7')
            ->set('selectedServiceTypes', ['sea'])
            ->set('minOrderValue', '100')
            ->set('maxOrderValue', '1000');
        
        $summary = $component->instance()->getActiveFiltersSummary();
        
        $this->assertCount(3, $summary); // date_range, service_types, order_value
        
        // Check date range filter
        $dateFilter = collect($summary)->firstWhere('type', 'date_range');
        $this->assertEquals('Date Range', $dateFilter['label']);
        $this->assertEquals('Last 7 days', $dateFilter['value']);
        
        // Check service types filter
        $serviceFilter = collect($summary)->firstWhere('type', 'service_types');
        $this->assertEquals('Service Types', $serviceFilter['label']);
        $this->assertEquals('Sea Freight', $serviceFilter['value']);
        
        // Check order value filter
        $valueFilter = collect($summary)->firstWhere('type', 'order_value');
        $this->assertEquals('Order Value', $valueFilter['label']);
        $this->assertStringContainsString('$100', $valueFilter['value']);
        $this->assertStringContainsString('$1,000', $valueFilter['value']);
    }

    /** @test */
    public function it_removes_specific_filters_correctly()
    {
        $component = Livewire::test(DashboardFilters::class)
            ->set('dateRange', '7')
            ->set('selectedServiceTypes', ['sea'])
            ->set('minOrderValue', '100');
        
        // Remove date range filter
        $component->call('removeFilter', 'date_range');
        $this->assertEquals('30', $component->get('dateRange'));
        
        // Remove service types filter
        $component->call('removeFilter', 'service_types');
        $this->assertEquals([], $component->get('selectedServiceTypes'));
        
        // Remove order value filter
        $component->call('removeFilter', 'order_value');
        $this->assertEquals('', $component->get('minOrderValue'));
        
        $component->assertEmitted('filtersUpdated');
    }

    /** @test */
    public function it_exports_filters_correctly()
    {
        $component = Livewire::test(DashboardFilters::class)
            ->set('dateRange', '7')
            ->set('selectedServiceTypes', ['sea'])
            ->set('minOrderValue', '100');
        
        $export = $component->instance()->exportFilters();
        
        $this->assertArrayHasKey('filters', $export);
        $this->assertArrayHasKey('active_count', $export);
        $this->assertArrayHasKey('summary', $export);
        $this->assertArrayHasKey('date_range_formatted', $export);
        $this->assertArrayHasKey('exported_at', $export);
        
        $this->assertEquals('7', $export['filters']['date_range']);
        $this->assertEquals(['sea'], $export['filters']['service_types']);
        $this->assertEquals('100', $export['filters']['min_order_value']);
        $this->assertEquals(3, $export['active_count']);
        $this->assertEquals('Last 7 days', $export['date_range_formatted']);
    }

    /** @test */
    public function it_applies_filters_and_emits_event()
    {
        $component = Livewire::test(DashboardFilters::class)
            ->set('dateRange', '7')
            ->call('applyFilters');
        
        $this->assertTrue($component->get('filtersApplied'));
        $component->assertEmitted('filtersUpdated');
    }
}