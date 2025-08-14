<?php

namespace App\Http\Livewire\Customers;

use App\Http\Livewire\Concerns\HasBreadcrumbs;
use App\Models\User;
use App\Services\CustomerStatisticsService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CustomerProfile extends Component
{
    use WithPagination, AuthorizesRequests, HasBreadcrumbs;

    public User $customer;
    public $packageStats = [];
    public $financialSummary = [];
    public $recentPackages = [];
    public $showAllPackages = false;
    public $shippingPatterns = [];
    public $cacheStatus = [];
    public $isLoadingStats = false;

    protected $paginationTheme = 'bootstrap';

    public function mount(User $customer)
    {
        // Use customer-specific authorization
        $this->authorize('customer.view', $customer);
        
        $this->customer = $customer->load('profile', 'role');
        $this->setCustomerProfileBreadcrumbs($customer);
        
        // Load data with caching
        $this->loadAllData();
    }

    /**
     * Get the statistics service instance
     */
    protected function getStatisticsService(): CustomerStatisticsService
    {
        return app(CustomerStatisticsService::class);
    }

    /**
     * Load all customer data with caching
     */
    public function loadAllData($forceRefresh = false)
    {
        $this->isLoadingStats = true;
        
        try {
            $this->loadPackageStats($forceRefresh);
            $this->loadFinancialSummary($forceRefresh);
            $this->loadShippingPatterns($forceRefresh);
            $this->loadRecentPackages();
            $this->loadCacheStatus();
        } finally {
            $this->isLoadingStats = false;
        }
    }

    public function loadPackageStats($forceRefresh = false)
    {
        $this->packageStats = $this->getStatisticsService()->getPackageMetrics($this->customer, $forceRefresh);
    }

    public function loadFinancialSummary($forceRefresh = false)
    {
        // Check if user can view financial information
        if (auth()->user()->can('customer.viewFinancials', $this->customer)) {
            $this->financialSummary = $this->getStatisticsService()->getFinancialSummary($this->customer, $forceRefresh);
        } else {
            $this->financialSummary = [];
        }
    }

    public function loadShippingPatterns($forceRefresh = false)
    {
        // Check if user can view shipping patterns
        if (auth()->user()->can('customer.viewPackages', $this->customer)) {
            $this->shippingPatterns = $this->getStatisticsService()->getShippingPatterns($this->customer, $forceRefresh);
        } else {
            $this->shippingPatterns = [];
        }
    }

    public function loadRecentPackages()
    {
        // Check if user can view package information
        if (auth()->user()->can('customer.viewPackages', $this->customer)) {
            $this->recentPackages = $this->customer->packages()
                ->with(['manifest', 'office', 'shipper'])
                ->latest()
                ->take(5)
                ->get();
        } else {
            $this->recentPackages = [];
        }
    }

    public function loadCacheStatus()
    {
        $this->cacheStatus = $this->getStatisticsService()->getCacheStatus($this->customer);
    }

    public function togglePackageView()
    {
        // Check if user can view packages before toggling
        $this->authorize('customer.viewPackages', $this->customer);
        
        $this->showAllPackages = !$this->showAllPackages;
        $this->resetPage();
    }

    /**
     * Refresh customer data and clear cache
     */
    public function refreshData()
    {
        $this->getStatisticsService()->clearCustomerCache($this->customer);
        $this->loadAllData(true);
        
        $this->dispatchBrowserEvent('show-alert', [
            'type' => 'success',
            'message' => 'Customer data refreshed successfully.'
        ]);
    }

    /**
     * Refresh specific data type
     */
    public function refreshDataType($type)
    {
        $this->getStatisticsService()->clearCustomerCacheType($this->customer, $type);
        
        switch ($type) {
            case 'packages':
                $this->loadPackageStats(true);
                break;
            case 'financial':
                $this->loadFinancialSummary(true);
                break;
            case 'patterns':
                $this->loadShippingPatterns(true);
                break;
        }
        
        $this->loadCacheStatus();
        
        $this->dispatchBrowserEvent('show-alert', [
            'type' => 'success',
            'message' => ucfirst($type) . ' data refreshed successfully.'
        ]);
    }

    /**
     * Warm up cache for this customer
     */
    public function warmUpCache()
    {
        $this->getStatisticsService()->warmUpCustomerCache($this->customer);
        $this->loadCacheStatus();
        
        $this->dispatchBrowserEvent('show-alert', [
            'type' => 'success',
            'message' => 'Cache warmed up successfully.'
        ]);
    }

    public function exportCustomerData()
    {
        // Check if user can export customer data
        $this->authorize('customer.export');
        
        // This method can be implemented later for data export functionality
        $this->dispatchBrowserEvent('show-alert', [
            'type' => 'info',
            'message' => 'Export functionality will be implemented in a future update.'
        ]);
    }

    public function render()
    {
        $packages = collect([]);
        
        // Only load packages if user has permission
        if (auth()->user()->can('customer.viewPackages', $this->customer)) {
            $packages = $this->showAllPackages 
                ? $this->customer->packages()
                    ->with(['manifest', 'office', 'shipper'])
                    ->latest()
                    ->paginate(10)
                : collect($this->recentPackages);
        }

        return view('livewire.customers.customer-profile', [
            'packages' => $packages,
            'canViewFinancials' => auth()->user()->can('customer.viewFinancials', $this->customer),
            'canViewPackages' => auth()->user()->can('customer.viewPackages', $this->customer),
            'canExport' => auth()->user()->can('customer.export'),
            'cacheMetrics' => $this->getStatisticsService()->getCachePerformanceMetrics(),
        ]);
    }
}