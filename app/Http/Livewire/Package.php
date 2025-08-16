<?php

namespace App\Http\Livewire;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\User;
use App\Models\Package as PackageModel;
use App\Models\ConsolidatedPackage;
use App\Services\PackageConsolidationService;
use Livewire\Component;
use Illuminate\Support\Facades\Session;

class Package extends Component
{
    use AuthorizesRequests;

    public int $inComingAir = 0;
    public int $inComingSea = 0;
    public int $availableAir = 0;
    public int $availableSea = 0;
    public float $accountBalance = 0;
    public int $delayedPackages = 0;
    public float $creditBalance = 0;
    public float $pendingPackageCharges = 0;
    public float $totalAvailableBalance = 0;
    public float $totalAmountNeeded = 0;

    // Consolidation properties
    public bool $consolidationMode = false;
    public array $selectedPackagesForConsolidation = [];
    public bool $showConsolidatedView = false;
    public string $consolidationNotes = '';

    // Search and filtering
    public string $search = '';
    public string $statusFilter = '';
    public bool $showSearchResults = false;
    public array $searchMatches = [];

    // UI state
    public string $successMessage = '';
    public string $errorMessage = '';
    public bool $showHistoryModal = false;
    public $selectedConsolidatedPackageForHistory = null;

    protected $consolidationService;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function __construct()
    {
        $this->consolidationService = app(PackageConsolidationService::class);
    }

    public function mount()
    {
        // Load consolidation mode from session
        $this->consolidationMode = Session::get('consolidation_mode', false);
        // Always show consolidated view by default so customers can see their consolidated packages
        $this->showConsolidatedView = true;
        // Update session to ensure it's always true
        Session::put('show_consolidated_view', true);
    }

    /**
     * Handle search input updates
     */
    public function updatedSearch()
    {
        $this->showSearchResults = !empty($this->search);
        $this->updateSearchMatches();
    }

    /**
     * Handle status filter updates
     */
    public function updatedStatusFilter()
    {
        $this->updateSearchMatches();
    }

    /**
     * Clear search and filters
     */
    public function clearSearch()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->showSearchResults = false;
        $this->searchMatches = [];
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
        $individualPackages = $this->getFilteredIndividualPackages();
        foreach ($individualPackages as $package) {
            $matches = $package->getSearchMatchDetails($this->search);
            if (!empty($matches)) {
                $this->searchMatches['individual'][$package->id] = $matches;
            }
        }

        // Get matches from consolidated packages
        $consolidatedPackages = $this->getFilteredConsolidatedPackages();
        foreach ($consolidatedPackages as $consolidatedPackage) {
            $matches = $consolidatedPackage->getSearchMatchDetails($this->search);
            if (!empty($matches)) {
                $this->searchMatches['consolidated'][$consolidatedPackage->id] = $matches;
            }
        }
    }
    
    /**
     * Toggle package selection for consolidation
     */
    public function togglePackageSelection($packageId)
    {
        if (!$this->consolidationMode) {
            return;
        }

        if (in_array($packageId, $this->selectedPackagesForConsolidation)) {
            $this->selectedPackagesForConsolidation = array_filter(
                $this->selectedPackagesForConsolidation,
                fn($id) => $id != $packageId
            );
        } else {
            $this->selectedPackagesForConsolidation[] = $packageId;
        }

        // Reset array keys
        $this->selectedPackagesForConsolidation = array_values($this->selectedPackagesForConsolidation);
    }

    /**
     * Consolidate selected packages
     */
    public function consolidateSelectedPackages()
    {
        $this->resetMessages();

        if (empty($this->selectedPackagesForConsolidation)) {
            $this->errorMessage = 'Please select at least 2 packages to consolidate.';
            return;
        }

        if (count($this->selectedPackagesForConsolidation) < 2) {
            $this->errorMessage = 'At least 2 packages are required for consolidation.';
            return;
        }

        try {
            $result = $this->consolidationService->consolidatePackages(
                $this->selectedPackagesForConsolidation,
                auth()->user(),
                ['notes' => $this->consolidationNotes]
            );

            if ($result['success']) {
                $this->successMessage = $result['message'];
                $this->selectedPackagesForConsolidation = [];
                $this->consolidationNotes = '';
                
                // Emit event to refresh package lists
                $this->emit('packagesConsolidated');
            } else {
                $this->errorMessage = $result['message'];
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'An error occurred while consolidating packages: ' . $e->getMessage();
        }
    }

    /**
     * Unconsolidate a consolidated package
     */
    public function unconsolidatePackage($consolidatedPackageId)
    {
        $this->resetMessages();

        try {
            $consolidatedPackage = ConsolidatedPackage::findOrFail($consolidatedPackageId);
            
            $result = $this->consolidationService->unconsolidatePackages(
                $consolidatedPackage,
                auth()->user()
            );

            if ($result['success']) {
                $this->successMessage = $result['message'];
                
                // Emit event to refresh package lists
                $this->emit('packagesUnconsolidated');
            } else {
                $this->errorMessage = $result['message'];
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'An error occurred while unconsolidating packages: ' . $e->getMessage();
        }
    }

    /**
     * Toggle consolidation mode
     */
    public function toggleConsolidationMode()
    {
        $this->consolidationMode = !$this->consolidationMode;
        
        // Clear selections when toggling mode
        $this->selectedPackagesForConsolidation = [];
        $this->consolidationNotes = '';
        
        // Store in session
        Session::put('consolidation_mode', $this->consolidationMode);
        
        $this->resetMessages();
    }

    /**
     * Toggle consolidated view
     */
    public function toggleConsolidatedView()
    {
        $this->showConsolidatedView = !$this->showConsolidatedView;
        
        // Store in session
        Session::put('show_consolidated_view', $this->showConsolidatedView);
        
        // Clear selections when switching views
        $this->selectedPackagesForConsolidation = [];
        
        $this->resetMessages();
    }

    /**
     * Clear selected packages
     */
    public function clearSelectedPackages()
    {
        $this->selectedPackagesForConsolidation = [];
        $this->consolidationNotes = '';
        $this->resetMessages();
    }

    /**
     * Reset success and error messages
     */
    protected function resetMessages()
    {
        $this->successMessage = '';
        $this->errorMessage = '';
    }

    /**
     * Get individual packages for current user
     */
    public function getIndividualPackagesProperty()
    {
        return $this->getFilteredIndividualPackages();
    }

    /**
     * Get consolidated packages for current user
     */
    public function getConsolidatedPackagesProperty()
    {
        return $this->getFilteredConsolidatedPackages();
    }

    /**
     * Get filtered individual packages based on search and filters
     * Now includes consolidated packages so customers can see all their packages
     */
    protected function getFilteredIndividualPackages()
    {
        if (!auth()->check()) {
            return collect();
        }

        // Show all packages (both individual and consolidated) so customers can see everything
        $query = PackageModel::where('user_id', auth()->id())
            ->with(['manifest', 'items', 'shipper', 'office', 'consolidatedPackage']);

        // Apply search
        if (!empty($this->search)) {
            $query->searchWithConsolidated($this->search);
        }

        // Apply status filter
        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get all packages (both individual and consolidated) for display
     */
    public function getAllPackagesProperty()
    {
        if (!auth()->check()) {
            return collect();
        }

        $query = PackageModel::where('user_id', auth()->id())
            ->with(['manifest', 'items', 'shipper', 'office', 'consolidatedPackage']);

        // Apply search
        if (!empty($this->search)) {
            $query->searchWithConsolidated($this->search);
        }

        // Apply status filter
        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get filtered consolidated packages based on search and filters
     */
    protected function getFilteredConsolidatedPackages()
    {
        if (!auth()->check()) {
            return collect();
        }

        $query = ConsolidatedPackage::where('customer_id', auth()->id())
            ->active()
            ->with(['packages.manifest', 'packages.items', 'packages.shipper', 'packages.office', 'createdBy']);

        // Apply search
        if (!empty($this->search)) {
            $query->search($this->search);
        }

        // Apply status filter
        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get packages available for consolidation
     */
    public function getAvailablePackagesForConsolidationProperty()
    {
        if (!auth()->check()) {
            return collect();
        }

        return PackageModel::where('user_id', auth()->id())
            ->availableForConsolidation()
            ->with(['manifest', 'items', 'shipper', 'office'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Check if package is selected for consolidation
     */
    public function isPackageSelected($packageId)
    {
        return in_array($packageId, $this->selectedPackagesForConsolidation);
    }

    /**
     * Get count of selected packages
     */
    public function getSelectedPackagesCountProperty()
    {
        return count($this->selectedPackagesForConsolidation);
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
        return collect(\App\Enums\PackageStatus::cases())->map(function($status) {
            return [
                'value' => $status->value,
                'label' => $status->getLabel()
            ];
        });
    }

    /**
     * Force consolidated view to be shown (for debugging)
     */
    public function forceConsolidatedView()
    {
        $this->showConsolidatedView = true;
        Session::put('show_consolidated_view', true);
        $this->resetMessages();
    }

    /**
     * Show consolidation history modal
     */
    public function showConsolidationHistory($consolidatedPackageId)
    {
        try {
            $consolidatedPackage = ConsolidatedPackage::findOrFail($consolidatedPackageId);
            
            // Authorize access to this consolidated package
            $this->authorize('view', $consolidatedPackage);
            
            $this->selectedConsolidatedPackageForHistory = $consolidatedPackage;
            $this->showHistoryModal = true;
            
            // Emit event to child component
            $this->emit('showConsolidationHistory', $consolidatedPackage->id);
            
        } catch (\Exception $e) {
            $this->errorMessage = 'Unable to load consolidation history: ' . $e->getMessage();
        }
    }

    /**
     * Close consolidation history modal
     */
    public function closeConsolidationHistory()
    {
        $this->showHistoryModal = false;
        $this->selectedConsolidatedPackageForHistory = null;
        $this->resetMessages();
    }

    /**
     * Get consolidation history for the selected package
     */
    public function getConsolidationHistoryProperty()
    {
        if (!$this->selectedConsolidatedPackageForHistory) {
            return collect();
        }

        return $this->consolidationService->getConsolidationHistory(
            $this->selectedConsolidatedPackageForHistory
        );
    }

    public function render()
    {
        // Debug: Log what the customer is seeing
        if (auth()->check()) {
            \Log::info('Customer package view render', [
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->full_name ?? 'N/A',
                'showConsolidatedView' => $this->showConsolidatedView,
                'consolidationMode' => $this->consolidationMode,
                'individual_packages_count' => $this->individualPackages->count(),
                'consolidated_packages_count' => $this->consolidatedPackages->count(),
                'search' => $this->search,
                'statusFilter' => $this->statusFilter,
            ]);
        }

        return view('livewire.packages.package');
    }
}
