<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class DashboardFilters extends Component
{
    // Date range filters
    public $dateRange = '30';
    public $customStartDate = '';
    public $customEndDate = '';
    
    // Service type filters
    public $serviceTypes = [];
    public $selectedServiceTypes = [];
    
    // Customer segment filters
    public $customerSegments = [];
    public $selectedCustomerSegments = [];
    
    // Package status filters
    public $packageStatuses = [];
    public $selectedPackageStatuses = [];
    
    // Office/Location filters
    public $offices = [];
    public $selectedOffices = [];
    
    // Additional filters
    public $minOrderValue = '';
    public $maxOrderValue = '';
    public $customerType = 'all'; // all, new, returning
    
    // Filter state management
    public $filtersApplied = false;
    public $activeFiltersCount = 0;
    
    // UI state
    public $showAdvancedFilters = false;
    public $isLoading = false;
    
    // Prevent loops and excessive updates
    private $lastEmittedFilters = null;
    private $isUpdatingInternally = false;

    protected $listeners = [
        'resetFilters' => 'resetAllFilters',
        'loadSavedFilters' => 'loadFiltersFromSession'
    ];

    public function mount()
    {
        $this->initializeFilterOptions();
        $this->loadFiltersFromSession();
        $this->calculateActiveFilters();
    }

    /**
     * Initialize available filter options
     */
    protected function initializeFilterOptions()
    {
        $this->serviceTypes = [
            'sea' => 'Sea Freight',
            'air' => 'Air Freight'
        ];

        $this->customerSegments = [
            'premium' => 'Premium',
            'high_value' => 'High Value',
            'regular' => 'Regular',
            'new_low_value' => 'New/Low Value'
        ];

        $this->packageStatuses = [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'in_transit' => 'In Transit',
            'shipped' => 'Shipped',
            'ready_for_pickup' => 'Ready for Pickup',
            'delivered' => 'Delivered',
            'delayed' => 'Delayed'
        ];

        // Load offices from database
        $this->offices = \App\Models\Office::select('id', 'name')
            ->orderBy('name')
            ->get()
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Handle date range selection
     */
    public function updatedDateRange($value)
    {
        if ($value !== 'custom') {
            $this->customStartDate = '';
            $this->customEndDate = '';
        }
        
        $this->applyFilters();
    }

    /**
     * Handle custom date range updates
     */
    public function updatedCustomStartDate()
    {
        if ($this->customStartDate && $this->customEndDate) {
            $this->dateRange = 'custom';
            $this->applyFilters();
        }
    }

    public function updatedCustomEndDate()
    {
        if ($this->customStartDate && $this->customEndDate) {
            $this->dateRange = 'custom';
            $this->applyFilters();
        }
    }

    /**
     * Handle service type filter updates
     */
    public function updatedSelectedServiceTypes()
    {
        $this->applyFilters();
    }

    /**
     * Handle customer segment filter updates
     */
    public function updatedSelectedCustomerSegments()
    {
        $this->applyFilters();
    }

    /**
     * Handle package status filter updates
     */
    public function updatedSelectedPackageStatuses()
    {
        $this->applyFilters();
    }

    /**
     * Handle office filter updates
     */
    public function updatedSelectedOffices()
    {
        $this->applyFilters();
    }

    /**
     * Handle order value filter updates
     */
    public function updatedMinOrderValue()
    {
        $this->applyFilters();
    }

    public function updatedMaxOrderValue()
    {
        $this->applyFilters();
    }

    /**
     * Handle customer type filter updates
     */
    public function updatedCustomerType()
    {
        $this->applyFilters();
    }

    /**
     * Apply filters and emit to other components
     */
    public function applyFilters()
    {
        // Prevent excessive emissions during rapid updates or internal updates
        if ($this->isLoading || $this->isUpdatingInternally) {
            return;
        }
        
        $this->isLoading = true;
        
        $filters = $this->getFilterArray();
        
        // Check if filters actually changed to prevent unnecessary emissions
        if ($this->lastEmittedFilters !== null && $this->lastEmittedFilters === $filters) {
            $this->isLoading = false;
            return;
        }
        
        // Save filters to session for persistence
        $this->saveFiltersToSession($filters);
        
        // Calculate active filters count
        $this->calculateActiveFilters();
        
        // Emit filter update to all dashboard components
        $this->emit('filtersUpdated', $filters);
        
        // Remember the last emitted filters
        $this->lastEmittedFilters = $filters;
        
        $this->filtersApplied = true;
        $this->isLoading = false;
    }

    /**
     * Reset all filters to default values
     */
    public function resetAllFilters()
    {
        $this->isUpdatingInternally = true;
        
        $this->dateRange = '30';
        $this->customStartDate = '';
        $this->customEndDate = '';
        $this->selectedServiceTypes = [];
        $this->selectedCustomerSegments = [];
        $this->selectedPackageStatuses = [];
        $this->selectedOffices = [];
        $this->minOrderValue = '';
        $this->maxOrderValue = '';
        $this->customerType = 'all';
        
        $this->clearFiltersFromSession();
        $this->calculateActiveFilters();
        
        $filters = $this->getFilterArray();
        $this->emit('filtersUpdated', $filters);
        $this->lastEmittedFilters = $filters;
        
        $this->filtersApplied = false;
        $this->isUpdatingInternally = false;
    }

    /**
     * Toggle advanced filters visibility
     */
    public function toggleAdvancedFilters()
    {
        $this->showAdvancedFilters = !$this->showAdvancedFilters;
    }

    /**
     * Get current filters as array
     */
    public function getFilterArray(): array
    {
        return [
            'date_range' => $this->dateRange,
            'custom_start' => $this->customStartDate,
            'custom_end' => $this->customEndDate,
            'service_types' => $this->selectedServiceTypes,
            'customer_segments' => $this->selectedCustomerSegments,
            'package_statuses' => $this->selectedPackageStatuses,
            'offices' => $this->selectedOffices,
            'min_order_value' => $this->minOrderValue,
            'max_order_value' => $this->maxOrderValue,
            'customer_type' => $this->customerType,
        ];
    }

    /**
     * Calculate number of active filters
     */
    protected function calculateActiveFilters()
    {
        $count = 0;
        
        // Count date range filter (if not default 30 days)
        if ($this->dateRange !== '30') {
            $count++;
        }
        
        // Count array filters
        $count += count($this->selectedServiceTypes);
        $count += count($this->selectedCustomerSegments);
        $count += count($this->selectedPackageStatuses);
        $count += count($this->selectedOffices);
        
        // Count value filters
        if (!empty($this->minOrderValue)) {
            $count++;
        }
        if (!empty($this->maxOrderValue)) {
            $count++;
        }
        
        // Count customer type filter (if not default 'all')
        if ($this->customerType !== 'all') {
            $count++;
        }
        
        $this->activeFiltersCount = $count;
    }

    /**
     * Save filters to session for persistence
     */
    protected function saveFiltersToSession(array $filters)
    {
        Session::put('dashboard_filters', $filters);
    }

    /**
     * Load filters from session
     */
    public function loadFiltersFromSession()
    {
        $savedFilters = Session::get('dashboard_filters', []);
        
        if (!empty($savedFilters)) {
            // Set flag to prevent triggering applyFilters during loading
            $this->isUpdatingInternally = true;
            
            $this->dateRange = $savedFilters['date_range'] ?? '30';
            $this->customStartDate = $savedFilters['custom_start'] ?? '';
            $this->customEndDate = $savedFilters['custom_end'] ?? '';
            $this->selectedServiceTypes = $savedFilters['service_types'] ?? [];
            $this->selectedCustomerSegments = $savedFilters['customer_segments'] ?? [];
            $this->selectedPackageStatuses = $savedFilters['package_statuses'] ?? [];
            $this->selectedOffices = $savedFilters['offices'] ?? [];
            $this->minOrderValue = $savedFilters['min_order_value'] ?? '';
            $this->maxOrderValue = $savedFilters['max_order_value'] ?? '';
            $this->customerType = $savedFilters['customer_type'] ?? 'all';
            
            $this->filtersApplied = true;
            $this->lastEmittedFilters = $savedFilters;
            
            // Reset the flag
            $this->isUpdatingInternally = false;
        }
    }

    /**
     * Clear filters from session
     */
    protected function clearFiltersFromSession()
    {
        Session::forget('dashboard_filters');
    }

    /**
     * Get predefined date range options
     */
    public function getDateRangeOptions(): array
    {
        return [
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 3 months',
            '180' => 'Last 6 months',
            '365' => 'Last year',
            'custom' => 'Custom range'
        ];
    }

    /**
     * Get formatted date range for display
     */
    public function getFormattedDateRange(): string
    {
        if ($this->dateRange === 'custom' && $this->customStartDate && $this->customEndDate) {
            $start = Carbon::parse($this->customStartDate)->format('M j, Y');
            $end = Carbon::parse($this->customEndDate)->format('M j, Y');
            return "{$start} - {$end}";
        }
        
        $options = $this->getDateRangeOptions();
        return $options[$this->dateRange] ?? 'Last 30 days';
    }

    /**
     * Get active filters summary for display
     */
    public function getActiveFiltersSummary(): array
    {
        $summary = [];
        
        // Date range
        if ($this->dateRange !== '30') {
            $summary[] = [
                'type' => 'date_range',
                'label' => 'Date Range',
                'value' => $this->getFormattedDateRange()
            ];
        }
        
        // Service types
        if (!empty($this->selectedServiceTypes)) {
            $values = array_map(fn($key) => $this->serviceTypes[$key] ?? $key, $this->selectedServiceTypes);
            $summary[] = [
                'type' => 'service_types',
                'label' => 'Service Types',
                'value' => implode(', ', $values)
            ];
        }
        
        // Customer segments
        if (!empty($this->selectedCustomerSegments)) {
            $values = array_map(fn($key) => $this->customerSegments[$key] ?? $key, $this->selectedCustomerSegments);
            $summary[] = [
                'type' => 'customer_segments',
                'label' => 'Customer Segments',
                'value' => implode(', ', $values)
            ];
        }
        
        // Package statuses
        if (!empty($this->selectedPackageStatuses)) {
            $values = array_map(fn($key) => $this->packageStatuses[$key] ?? $key, $this->selectedPackageStatuses);
            $summary[] = [
                'type' => 'package_statuses',
                'label' => 'Package Status',
                'value' => implode(', ', $values)
            ];
        }
        
        // Offices
        if (!empty($this->selectedOffices)) {
            $values = array_map(fn($id) => $this->offices[$id] ?? "Office {$id}", $this->selectedOffices);
            $summary[] = [
                'type' => 'offices',
                'label' => 'Offices',
                'value' => implode(', ', $values)
            ];
        }
        
        // Order value range
        if (!empty($this->minOrderValue) || !empty($this->maxOrderValue)) {
            $min = !empty($this->minOrderValue) ? '$' . number_format($this->minOrderValue) : 'Any';
            $max = !empty($this->maxOrderValue) ? '$' . number_format($this->maxOrderValue) : 'Any';
            $summary[] = [
                'type' => 'order_value',
                'label' => 'Order Value',
                'value' => "{$min} - {$max}"
            ];
        }
        
        // Customer type
        if ($this->customerType !== 'all') {
            $summary[] = [
                'type' => 'customer_type',
                'label' => 'Customer Type',
                'value' => ucfirst(str_replace('_', ' ', $this->customerType))
            ];
        }
        
        return $summary;
    }

    /**
     * Remove specific filter
     */
    public function removeFilter($filterType)
    {
        switch ($filterType) {
            case 'date_range':
                $this->dateRange = '30';
                $this->customStartDate = '';
                $this->customEndDate = '';
                break;
            case 'service_types':
                $this->selectedServiceTypes = [];
                break;
            case 'customer_segments':
                $this->selectedCustomerSegments = [];
                break;
            case 'package_statuses':
                $this->selectedPackageStatuses = [];
                break;
            case 'offices':
                $this->selectedOffices = [];
                break;
            case 'order_value':
                $this->minOrderValue = '';
                $this->maxOrderValue = '';
                break;
            case 'customer_type':
                $this->customerType = 'all';
                break;
        }
        
        $this->applyFilters();
    }

    /**
     * Export current filter configuration
     */
    public function exportFilters(): array
    {
        return [
            'filters' => $this->getFilterArray(),
            'active_count' => $this->activeFiltersCount,
            'summary' => $this->getActiveFiltersSummary(),
            'date_range_formatted' => $this->getFormattedDateRange(),
            'exported_at' => Carbon::now()->toISOString()
        ];
    }

    public function render()
    {
        return view('livewire.dashboard-filters', [
            'dateRangeOptions' => $this->getDateRangeOptions(),
            'activeFiltersSummary' => $this->getActiveFiltersSummary(),
            'formattedDateRange' => $this->getFormattedDateRange()
        ]);
    }
}