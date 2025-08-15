<?php

namespace App\Http\Livewire\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\User;
use App\Enums\PackageStatus;
use Livewire\Component;
use Livewire\WithPagination;

class PackageManagement extends Component
{
    use AuthorizesRequests, WithPagination;

    // Search and filtering
    public string $search = '';
    public string $statusFilter = '';
    public string $customerFilter = '';
    public string $typeFilter = ''; // 'individual', 'consolidated', 'all'
    public bool $showSearchResults = false;
    public array $searchMatches = [];

    // UI state
    public string $successMessage = '';
    public string $errorMessage = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'customerFilter' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function mount()
    {
        $this->typeFilter = 'all';
    }

    /**
     * Handle search input updates
     */
    public function updatedSearch()
    {
        $this->resetPage();
        $this->showSearchResults = !empty($this->search);
        $this->updateSearchMatches();
    }

    /**
     * Handle filter updates
     */
    public function updatedStatusFilter()
    {
        $this->resetPage();
        $this->updateSearchMatches();
    }

    public function updatedCustomerFilter()
    {
        $this->resetPage();
        $this->updateSearchMatches();
    }

    public function updatedTypeFilter()
    {
        $this->resetPage();
        $this->updateSearchMatches();
    }

    /**
     * Clear search and filters
     */
    public function clearSearch()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->customerFilter = '';
        $this->typeFilter = 'all';
        $this->showSearchResults = false;
        $this->searchMatches = [];
        $this->resetPage();
    }

    /**
     * Update search matches for highlighting
     */
    protected function updateSearchMatches()
    {
        $this->searchMatches = [];
        
        if (empty($this->search)) {
            return;
        }

        // Get matches from individual packages
        if ($this->typeFilter === 'all' || $this->typeFilter === 'individual') {
            $individualPackages = $this->getFilteredIndividualPackages();
            foreach ($individualPackages as $package) {
                $matches = $package->getSearchMatchDetails($this->search);
                if (!empty($matches)) {
                    $this->searchMatches['individual'][$package->id] = $matches;
                }
            }
        }

        // Get matches from consolidated packages
        if ($this->typeFilter === 'all' || $this->typeFilter === 'consolidated') {
            $consolidatedPackages = $this->getFilteredConsolidatedPackages();
            foreach ($consolidatedPackages as $consolidatedPackage) {
                $matches = $consolidatedPackage->getSearchMatchDetails($this->search);
                if (!empty($matches)) {
                    $this->searchMatches['consolidated'][$consolidatedPackage->id] = $matches;
                }
            }
        }
    }

    /**
     * Get filtered individual packages
     */
    protected function getFilteredIndividualPackages()
    {
        $query = Package::with(['user.profile', 'manifest', 'items', 'shipper', 'office', 'consolidatedPackage'])
            ->individual();

        // Apply search
        if (!empty($this->search)) {
            $query->searchWithConsolidated($this->search);
        }

        // Apply status filter
        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        // Apply customer filter
        if (!empty($this->customerFilter)) {
            $query->where('user_id', $this->customerFilter);
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    /**
     * Get filtered consolidated packages
     */
    protected function getFilteredConsolidatedPackages()
    {
        $query = ConsolidatedPackage::with(['customer.profile', 'packages.manifest', 'packages.items', 'packages.shipper', 'packages.office', 'createdBy'])
            ->active();

        // Apply search
        if (!empty($this->search)) {
            $query->search($this->search);
        }

        // Apply status filter
        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        // Apply customer filter
        if (!empty($this->customerFilter)) {
            $query->where('customer_id', $this->customerFilter);
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    /**
     * Get individual packages property
     */
    public function getIndividualPackagesProperty()
    {
        if ($this->typeFilter === 'consolidated') {
            return collect();
        }
        return $this->getFilteredIndividualPackages();
    }

    /**
     * Get consolidated packages property
     */
    public function getConsolidatedPackagesProperty()
    {
        if ($this->typeFilter === 'individual') {
            return collect();
        }
        return $this->getFilteredConsolidatedPackages();
    }

    /**
     * Get search matches for a specific package
     */
    public function getPackageSearchMatches($packageId, $type = 'individual')
    {
        return $this->searchMatches[$type][$packageId] ?? [];
    }

    /**
     * Check if a package has search matches
     */
    public function hasSearchMatches($packageId, $type = 'individual')
    {
        return !empty($this->searchMatches[$type][$packageId] ?? []);
    }

    /**
     * Get available status options for filtering
     */
    public function getAvailableStatusesProperty()
    {
        return collect(PackageStatus::cases())->map(function($status) {
            return [
                'value' => $status->value,
                'label' => $status->getLabel()
            ];
        });
    }

    /**
     * Get available customers for filtering
     */
    public function getAvailableCustomersProperty()
    {
        return User::where(function($query) {
                $query->whereHas('packages')
                      ->orWhereHas('consolidatedPackages');
            })
            ->with('profile')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(function($user) {
                return [
                    'value' => $user->id,
                    'label' => $user->full_name . ($user->profile && $user->profile->account_number ? ' (' . $user->profile->account_number . ')' : '')
                ];
            });
    }

    /**
     * Get search results summary
     */
    public function getSearchSummaryProperty()
    {
        $individualCount = $this->typeFilter !== 'consolidated' ? $this->individualPackages->total() : 0;
        $consolidatedCount = $this->typeFilter !== 'individual' ? $this->consolidatedPackages->total() : 0;
        $totalIndividualInConsolidated = $this->typeFilter !== 'individual' 
            ? $this->consolidatedPackages->sum(function($cp) { return $cp->packages->count(); }) 
            : 0;

        return [
            'individual_count' => $individualCount,
            'consolidated_count' => $consolidatedCount,
            'total_individual_in_consolidated' => $totalIndividualInConsolidated,
            'total_packages' => $individualCount + $totalIndividualInConsolidated
        ];
    }

    public function render()
    {
        return view('livewire.admin.package-management');
    }
}