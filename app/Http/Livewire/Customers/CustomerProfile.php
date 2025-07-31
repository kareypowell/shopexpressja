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
        $this->authorize('view', $customer);
        
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
        $this->financialSummary = $this->customer->getFinancialSummary();
    }

    public function loadRecentPackages()
    {
        $this->recentPackages = $this->customer->packages()
            ->with(['manifest', 'office', 'shipper'])
            ->latest()
            ->take(5)
            ->get();
    }

    public function togglePackageView()
    {
        $this->showAllPackages = !$this->showAllPackages;
        $this->resetPage();
    }

    public function exportCustomerData()
    {
        // This method can be implemented later for data export functionality
        $this->dispatchBrowserEvent('show-alert', [
            'type' => 'info',
            'message' => 'Export functionality will be implemented in a future update.'
        ]);
    }

    public function render()
    {
        $packages = $this->showAllPackages 
            ? $this->customer->packages()
                ->with(['manifest', 'office', 'shipper'])
                ->latest()
                ->paginate(10)
            : collect($this->recentPackages);

        return view('livewire.customers.customer-profile', [
            'packages' => $packages
        ]);
    }
}