<?php

namespace App\Http\Livewire\Customers;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CustomerProfile extends Component
{
    use WithPagination, AuthorizesRequests;

    public User $customer;
    public $packageStats = [];
    public $financialSummary = [];
    public $recentPackages = [];
    public $showAllPackages = false;

    protected $paginationTheme = 'bootstrap';

    public function mount(User $customer)
    {
        // Use customer-specific authorization
        $this->authorize('customer.view', $customer);
        
        $this->customer = $customer->load('profile', 'role');
        $this->loadPackageStats();
        $this->loadFinancialSummary();
        $this->loadRecentPackages();
    }

    public function loadPackageStats()
    {
        $this->packageStats = $this->customer->getPackageStats();
    }

    public function loadFinancialSummary()
    {
        // Check if user can view financial information
        if (auth()->user()->can('customer.viewFinancials', $this->customer)) {
            $this->financialSummary = $this->customer->getFinancialSummary();
        } else {
            $this->financialSummary = [];
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

    public function togglePackageView()
    {
        // Check if user can view packages before toggling
        $this->authorize('customer.viewPackages', $this->customer);
        
        $this->showAllPackages = !$this->showAllPackages;
        $this->resetPage();
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
        ]);
    }
}