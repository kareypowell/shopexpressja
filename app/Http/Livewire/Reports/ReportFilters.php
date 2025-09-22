<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use App\Models\Office;
use App\Models\User;
use App\Models\SavedReportFilter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReportFilters extends Component
{
    // Report type context
    public string $reportType = 'sales_collections';
    
    // Date range filters
    public string $dateRange = '30';
    public string $customStartDate = '';
    public string $customEndDate = '';
    
    // Manifest and shipping filters
    public array $manifestTypes = [];
    public array $selectedManifestTypes = [];
    
    // Office/Location filters
    public array $offices = [];
    public array $selectedOffices = [];
    
    // Customer filters
    public string $customerSearch = '';
    public array $selectedCustomers = [];
    public string $customerType = 'all'; // all, new, returning, premium
    
    // Financial filters
    public string $minAmount = '';
    public string $maxAmount = '';
    public string $paymentStatus = 'all'; // all, paid, unpaid, partial
    
    // Package status filters (for manifest reports)
    public array $packageStatuses = [];
    public array $selectedPackageStatuses = [];
    
    // Saved filters
    public array $savedFilters = [];
    public string $selectedSavedFilter = '';
    public string $newFilterName = '';
    public bool $showSaveDialog = false;
    
    // UI state
    public bool $showAdvancedFilters = false;
    public bool $isLoading = false;
    public int $activeFiltersCount = 0;
    
    // Prevent loops
    private bool $isUpdatingInternally = false;
    private ?array $lastEmittedFilters = null;

    protected $listeners = [
        'resetFilters' => 'resetAllFilters',
        'loadSavedFilter' => 'loadSavedFilter',
    ];

    public function mount(string $reportType = 'sales_collections')
    {
        $this->reportType = $reportType;
        $this->initializeFilterOptions();
        $this->loadSavedFilters();
        $this->loadFiltersFromSession();
        $this->calculateActiveFilters();
    }

    /**
     * Initialize available filter options based on report type
     */
    protected function initializeFilterOptions(): void
    {
        // Manifest types
        $this->manifestTypes = [
            'sea' => 'Sea Freight',
            'air' => 'Air Freight'
        ];

        // Load offices from database
        $this->offices = Office::select('id', 'name')
            ->orderBy('name')
            ->get()
            ->pluck('name', 'id')
            ->toArray();

        // Package statuses (for manifest reports)
        if (class_exists('\App\Enums\PackageStatus')) {
            $this->packageStatuses = collect(\App\Enums\PackageStatus::cases())
                ->mapWithKeys(function ($status) {
                    return [$status->value => $status->getLabel()];
                })
                ->toArray();
        }
    }

    /**
     * Load saved filters for current user and report type
     */
    protected function loadSavedFilters(): void
    {
        $user = Auth::user();
        if (!$user) return;

        $this->savedFilters = SavedReportFilter::where('user_id', $user->id)
            ->where('report_type', $this->reportType)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($filter) {
                return [$filter->id => $filter->name];
            })
            ->toArray();
    }

    /**
     * Handle date range selection
     */
    public function updatedDateRange($value): void
    {
        if ($value !== 'custom') {
            $this->customStartDate = '';
            $this->customEndDate = '';
        }
        $this->applyFilters();
    }

    /**
     * Handle custom date updates
     */
    public function updatedCustomStartDate(): void
    {
        if ($this->customStartDate && $this->customEndDate) {
            $this->dateRange = 'custom';
            $this->applyFilters();
        }
    }

    public function updatedCustomEndDate(): void
    {
        if ($this->customStartDate && $this->customEndDate) {
            $this->dateRange = 'custom';
            $this->applyFilters();
        }
    }

    /**
     * Handle manifest type filter updates
     */
    public function updatedSelectedManifestTypes(): void
    {
        $this->applyFilters();
    }

    /**
     * Handle office filter updates
     */
    public function updatedSelectedOffices(): void
    {
        $this->applyFilters();
    }

    /**
     * Handle customer search
     */
    public function updatedCustomerSearch(): void
    {
        // Debounce customer search
        $this->applyFilters();
    }

    /**
     * Handle customer type filter
     */
    public function updatedCustomerType(): void
    {
        $this->applyFilters();
    }

    /**
     * Handle financial filter updates
     */
    public function updatedMinAmount(): void
    {
        $this->applyFilters();
    }

    public function updatedMaxAmount(): void
    {
        $this->applyFilters();
    }

    public function updatedPaymentStatus(): void
    {
        $this->applyFilters();
    }

    /**
     * Handle package status filter updates
     */
    public function updatedSelectedPackageStatuses(): void
    {
        $this->applyFilters();
    }

    /**
     * Apply filters and emit to parent component
     */
    public function applyFilters(): void
    {
        if ($this->isLoading || $this->isUpdatingInternally) {
            return;
        }

        $this->isLoading = true;

        $filters = $this->getFilterArray();

        // Check if filters actually changed
        if ($this->lastEmittedFilters !== null && $this->lastEmittedFilters === $filters) {
            $this->isLoading = false;
            return;
        }

        // Save to session
        $this->saveFiltersToSession($filters);

        // Calculate active filters count
        $this->calculateActiveFilters();

        // Emit to parent component
        $this->emit('filtersUpdated', $filters);

        $this->lastEmittedFilters = $filters;
        $this->isLoading = false;
    }

    /**
     * Reset all filters to default values
     */
    public function resetAllFilters(): void
    {
        $this->isUpdatingInternally = true;

        $this->dateRange = '30';
        $this->customStartDate = '';
        $this->customEndDate = '';
        $this->selectedManifestTypes = [];
        $this->selectedOffices = [];
        $this->customerSearch = '';
        $this->selectedCustomers = [];
        $this->customerType = 'all';
        $this->minAmount = '';
        $this->maxAmount = '';
        $this->paymentStatus = 'all';
        $this->selectedPackageStatuses = [];
        $this->selectedSavedFilter = '';

        $this->clearFiltersFromSession();
        $this->calculateActiveFilters();

        $filters = $this->getFilterArray();
        $this->emit('filtersUpdated', $filters);
        $this->lastEmittedFilters = $filters;

        $this->isUpdatingInternally = false;

        $this->dispatchBrowserEvent('toastr:success', [
            'message' => 'Filters reset to default values'
        ]);
    }

    /**
     * Toggle advanced filters visibility
     */
    public function toggleAdvancedFilters(): void
    {
        $this->showAdvancedFilters = !$this->showAdvancedFilters;
    }

    /**
     * Load a saved filter
     */
    public function loadSavedFilter(int $filterId): void
    {
        try {
            $savedFilter = SavedReportFilter::where('id', $filterId)
                ->where('user_id', Auth::id())
                ->where('report_type', $this->reportType)
                ->first();

            if (!$savedFilter) {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => 'Saved filter not found'
                ]);
                return;
            }

            $this->isUpdatingInternally = true;

            $config = $savedFilter->filter_config;
            $this->dateRange = $config['date_range'] ?? '30';
            $this->customStartDate = $config['custom_start'] ?? '';
            $this->customEndDate = $config['custom_end'] ?? '';
            $this->selectedManifestTypes = $config['manifest_types'] ?? [];
            $this->selectedOffices = $config['offices'] ?? [];
            $this->customerSearch = $config['customer_search'] ?? '';
            $this->customerType = $config['customer_type'] ?? 'all';
            $this->minAmount = $config['min_amount'] ?? '';
            $this->maxAmount = $config['max_amount'] ?? '';
            $this->paymentStatus = $config['payment_status'] ?? 'all';
            $this->selectedPackageStatuses = $config['package_statuses'] ?? [];

            $this->selectedSavedFilter = (string) $filterId;

            $this->isUpdatingInternally = false;
            $this->applyFilters();

            $this->dispatchBrowserEvent('toastr:success', [
                'message' => "Loaded filter: {$savedFilter->name}"
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading saved filter: ' . $e->getMessage());
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to load saved filter'
            ]);
        }
    }

    /**
     * Save current filter configuration
     */
    public function saveCurrentFilters(): void
    {
        if (empty($this->newFilterName)) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Please enter a name for the filter'
            ]);
            return;
        }

        try {
            $user = Auth::user();
            
            SavedReportFilter::create([
                'user_id' => $user->id,
                'name' => $this->newFilterName,
                'report_type' => $this->reportType,
                'filter_config' => $this->getFilterArray(),
                'is_shared' => false,
            ]);

            $this->newFilterName = '';
            $this->showSaveDialog = false;
            $this->loadSavedFilters();

            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Filter saved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving filter: ' . $e->getMessage());
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to save filter'
            ]);
        }
    }

    /**
     * Delete a saved filter
     */
    public function deleteSavedFilter(int $filterId): void
    {
        try {
            $deleted = SavedReportFilter::where('id', $filterId)
                ->where('user_id', Auth::id())
                ->delete();

            if ($deleted) {
                $this->loadSavedFilters();
                if ($this->selectedSavedFilter === (string) $filterId) {
                    $this->selectedSavedFilter = '';
                }

                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => 'Filter deleted successfully'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error deleting saved filter: ' . $e->getMessage());
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to delete filter'
            ]);
        }
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
            'manifest_types' => $this->selectedManifestTypes,
            'offices' => $this->selectedOffices,
            'customer_search' => $this->customerSearch,
            'customer_type' => $this->customerType,
            'min_amount' => $this->minAmount,
            'max_amount' => $this->maxAmount,
            'payment_status' => $this->paymentStatus,
            'package_statuses' => $this->selectedPackageStatuses,
            'report_type' => $this->reportType,
        ];
    }

    /**
     * Calculate number of active filters
     */
    protected function calculateActiveFilters(): void
    {
        $count = 0;

        // Date range (if not default 30 days)
        if ($this->dateRange !== '30') {
            $count++;
        }

        // Array filters
        $count += count($this->selectedManifestTypes);
        $count += count($this->selectedOffices);
        $count += count($this->selectedPackageStatuses);

        // Text filters
        if (!empty($this->customerSearch)) {
            $count++;
        }

        // Value filters
        if (!empty($this->minAmount)) {
            $count++;
        }
        if (!empty($this->maxAmount)) {
            $count++;
        }

        // Status filters (if not default 'all')
        if ($this->customerType !== 'all') {
            $count++;
        }
        if ($this->paymentStatus !== 'all') {
            $count++;
        }

        $this->activeFiltersCount = $count;
    }

    /**
     * Save filters to session
     */
    protected function saveFiltersToSession(array $filters): void
    {
        Session::put("report_filters_{$this->reportType}", $filters);
    }

    /**
     * Load filters from session
     */
    protected function loadFiltersFromSession(): void
    {
        $savedFilters = Session::get("report_filters_{$this->reportType}", []);

        if (!empty($savedFilters)) {
            $this->isUpdatingInternally = true;

            $this->dateRange = $savedFilters['date_range'] ?? '30';
            $this->customStartDate = $savedFilters['custom_start'] ?? '';
            $this->customEndDate = $savedFilters['custom_end'] ?? '';
            $this->selectedManifestTypes = $savedFilters['manifest_types'] ?? [];
            $this->selectedOffices = $savedFilters['offices'] ?? [];
            $this->customerSearch = $savedFilters['customer_search'] ?? '';
            $this->customerType = $savedFilters['customer_type'] ?? 'all';
            $this->minAmount = $savedFilters['min_amount'] ?? '';
            $this->maxAmount = $savedFilters['max_amount'] ?? '';
            $this->paymentStatus = $savedFilters['payment_status'] ?? 'all';
            $this->selectedPackageStatuses = $savedFilters['package_statuses'] ?? [];

            $this->lastEmittedFilters = $savedFilters;
            $this->isUpdatingInternally = false;
        }
    }

    /**
     * Clear filters from session
     */
    protected function clearFiltersFromSession(): void
    {
        Session::forget("report_filters_{$this->reportType}");
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
     * Get active filters summary
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

        // Manifest types
        if (!empty($this->selectedManifestTypes)) {
            $values = array_map(fn($key) => $this->manifestTypes[$key] ?? $key, $this->selectedManifestTypes);
            $summary[] = [
                'type' => 'manifest_types',
                'label' => 'Manifest Types',
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

        // Customer search
        if (!empty($this->customerSearch)) {
            $summary[] = [
                'type' => 'customer_search',
                'label' => 'Customer Search',
                'value' => $this->customerSearch
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

        // Amount range
        if (!empty($this->minAmount) || !empty($this->maxAmount)) {
            $min = !empty($this->minAmount) ? '$' . number_format($this->minAmount) : 'Any';
            $max = !empty($this->maxAmount) ? '$' . number_format($this->maxAmount) : 'Any';
            $summary[] = [
                'type' => 'amount_range',
                'label' => 'Amount Range',
                'value' => "{$min} - {$max}"
            ];
        }

        // Payment status
        if ($this->paymentStatus !== 'all') {
            $summary[] = [
                'type' => 'payment_status',
                'label' => 'Payment Status',
                'value' => ucfirst($this->paymentStatus)
            ];
        }

        return $summary;
    }

    /**
     * Remove specific filter
     */
    public function removeFilter(string $filterType): void
    {
        switch ($filterType) {
            case 'date_range':
                $this->dateRange = '30';
                $this->customStartDate = '';
                $this->customEndDate = '';
                break;
            case 'manifest_types':
                $this->selectedManifestTypes = [];
                break;
            case 'offices':
                $this->selectedOffices = [];
                break;
            case 'customer_search':
                $this->customerSearch = '';
                break;
            case 'customer_type':
                $this->customerType = 'all';
                break;
            case 'amount_range':
                $this->minAmount = '';
                $this->maxAmount = '';
                break;
            case 'payment_status':
                $this->paymentStatus = 'all';
                break;
            case 'package_statuses':
                $this->selectedPackageStatuses = [];
                break;
        }

        $this->applyFilters();
    }

    /**
     * Check if report type supports specific filters
     */
    public function supportsFilter(string $filterType): bool
    {
        $supportMatrix = [
            'sales_collections' => ['date_range', 'offices', 'customer_search', 'customer_type', 'amount_range', 'payment_status'],
            'manifest_performance' => ['date_range', 'manifest_types', 'offices', 'package_statuses'],
            'customer_analytics' => ['date_range', 'offices', 'customer_search', 'customer_type', 'amount_range'],
            'financial_summary' => ['date_range', 'offices', 'amount_range', 'payment_status'],
        ];

        return in_array($filterType, $supportMatrix[$this->reportType] ?? []);
    }

    public function render()
    {
        return view('livewire.reports.report-filters', [
            'dateRangeOptions' => $this->getDateRangeOptions(),
            'activeFiltersSummary' => $this->getActiveFiltersSummary(),
            'formattedDateRange' => $this->getFormattedDateRange(),
        ]);
    }
}