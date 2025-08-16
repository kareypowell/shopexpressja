<?php

namespace App\Http\Livewire\Manifests;

use App\Enums\PackageStatus;
use App\Models\Manifest;
use App\Models\Package;
use App\Services\PackageStatusService;
use App\Services\PackageConsolidationService;
use App\Services\PackageFeeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class IndividualPackagesTab extends Component
{
    use WithPagination;

    public Manifest $manifest;
    
    // Search and filtering
    public $search = '';
    public $statusFilter = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    
    // Selection and bulk operations
    public $selectedPackages = [];
    public $selectAll = false;
    public $bulkStatus = '';
    public $showBulkConfirmModal = false;
    public $confirmingStatus = '';
    public $confirmingStatusLabel = '';
    public $bulkNotes = '';
    
    // Fee entry modal for individual packages
    public $showFeeModal = false;
    public $feePackageId = null;
    public $feePackage = null;
    public $customsDuty = 0;
    public $storageFee = 0;
    public $deliveryFee = 0;
    
    // Consolidation modal
    public $showConsolidationModal = false;
    public $consolidationNotes = '';
    public $packagesForConsolidation = [];
    
    protected $listeners = [
        'preserveTabState' => 'handlePreserveState',
        'tabSwitched' => 'handleTabSwitch',
        'refreshIndividualPackages' => '$refresh',
        'packageStatusUpdated' => '$refresh'
    ];

    protected $rules = [
        'bulkStatus' => 'required|string',
        'selectedPackages' => 'required|array|min:1',
        'consolidationNotes' => 'nullable|string|max:500',
        'customsDuty' => 'required|numeric|min:0',
        'storageFee' => 'required|numeric|min:0',
        'deliveryFee' => 'required|numeric|min:0'
    ];

    public function mount(Manifest $manifest)
    {
        $this->manifest = $manifest;
    }

    public function render()
    {
        $packages = $this->getIndividualPackages();
        $statusOptions = $this->getStatusOptions();
        
        return view('livewire.manifests.individual-packages-tab', [
            'packages' => $packages,
            'statusOptions' => $statusOptions,
        ]);
    }

    public function getIndividualPackages()
    {
        $query = Package::where('manifest_id', $this->manifest->id)
            ->individual() // Only non-consolidated packages
            ->with(['user.profile', 'shipper', 'office']);

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('tracking_number', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhere('warehouse_receipt_no', 'like', '%' . $this->search . '%')
                  ->orWhereHas('user', function ($userQuery) {
                      $userQuery->where('first_name', 'like', '%' . $this->search . '%')
                               ->orWhere('last_name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        // Apply status filter
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(10);
    }

    public function getStatusOptions()
    {
        $options = [];
        foreach (PackageStatus::manualUpdateCases() as $status) {
            $options[$status->value] = $status->getLabel();
        }
        return $options;
    }

    public function updatedSearch()
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedPackages = $this->getIndividualPackages()->pluck('id')->toArray();
        } else {
            $this->selectedPackages = [];
        }
    }

    public function updatedSelectedPackages()
    {
        $totalPackages = $this->getIndividualPackages()->count();
        $this->selectAll = count($this->selectedPackages) === $totalPackages;
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';
        $this->clearSelection();
        $this->resetPage();
    }

    public function clearSelection()
    {
        $this->selectedPackages = [];
        $this->selectAll = false;
    }

    public function confirmBulkStatusUpdate()
    {
        $this->validate([
            'bulkStatus' => 'required|string',
            'selectedPackages' => 'required|array|min:1',
        ], [
            'bulkStatus.required' => 'Please select a status to update to.',
            'selectedPackages.required' => 'Please select at least one package.',
            'selectedPackages.min' => 'Please select at least one package.',
        ]);

        try {
            $newStatus = PackageStatus::from($this->bulkStatus);
            $this->confirmingStatus = $newStatus->value;
            $this->confirmingStatusLabel = $newStatus->getLabel();
            $this->showBulkConfirmModal = true;
        } catch (\InvalidArgumentException $e) {
            $this->addError('bulkStatus', 'Invalid status selected.');
        }
    }

    public function executeBulkStatusUpdate()
    {
        if (empty($this->confirmingStatus) || empty($this->selectedPackages)) {
            return;
        }

        $packageStatusService = app(PackageStatusService::class);
        $successCount = 0;
        $errorCount = 0;

        foreach ($this->selectedPackages as $packageId) {
            try {
                $package = Package::find($packageId);
                if ($package) {
                    $result = $packageStatusService->updateStatus(
                        $package,
                        PackageStatus::from($this->confirmingStatus),
                        Auth::user(),
                        $this->bulkNotes ?: 'Bulk status update from individual packages tab'
                    );

                    if ($result) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                }
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Failed to update package status in bulk update', [
                    'package_id' => $packageId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->cancelBulkUpdate();

        if ($successCount > 0) {
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => $successCount . ' package(s) updated successfully.'
            ]);
        }

        if ($errorCount > 0) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => $errorCount . ' package(s) failed to update. Please check the logs.'
            ]);
        }

        $this->emit('packageStatusUpdated');
        $this->emit('packagesChanged');
    }

    public function cancelBulkUpdate()
    {
        $this->showBulkConfirmModal = false;
        $this->confirmingStatus = '';
        $this->confirmingStatusLabel = '';
        $this->bulkStatus = '';
        $this->bulkNotes = '';
        $this->clearSelection();
    }

    public function updatePackageStatus($packageId, $newStatus)
    {
        try {
            $package = Package::findOrFail($packageId);
            
            // Check if transitioning to READY status - show fee modal for packages that need fees
            if ($newStatus === PackageStatus::READY && $this->packageNeedsFeeEntry($package)) {
                $this->showFeeEntryModal($packageId);
                return;
            }
            
            $packageStatusService = app(PackageStatusService::class);

            $result = $packageStatusService->updateStatus(
                $package,
                PackageStatus::from($newStatus),
                Auth::user(),
                'Status update from individual packages tab'
            );

            if ($result) {
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => "Package status updated successfully."
                ]);
                $this->emit('packageStatusUpdated');
                $this->emit('packagesChanged');
            } else {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => 'Failed to update package status.'
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to update package status.'
            ]);
            Log::error('Failed to update package status', [
                'package_id' => $packageId,
                'new_status' => $newStatus,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function showFeeEntryModal($packageId)
    {
        $this->feePackageId = $packageId;
        $this->feePackage = Package::with(['user.profile'])->findOrFail($packageId);
        
        // Pre-populate with existing fees
        $this->customsDuty = $this->feePackage->customs_duty ?? 0;
        $this->storageFee = $this->feePackage->storage_fee ?? 0;
        $this->deliveryFee = $this->feePackage->delivery_fee ?? 0;
        
        $this->showFeeModal = true;
    }

    private function packageNeedsFeeEntry($package): bool
    {
        return ($package->customs_duty ?? 0) == 0 || 
               ($package->storage_fee ?? 0) == 0 || 
               ($package->delivery_fee ?? 0) == 0;
    }

    public function closeFeeModal()
    {
        $this->showFeeModal = false;
        $this->feePackageId = null;
        $this->feePackage = null;
        $this->customsDuty = 0;
        $this->storageFee = 0;
        $this->deliveryFee = 0;
    }

    public function processFeeUpdate()
    {
        $this->validate([
            'customsDuty' => 'required|numeric|min:0',
            'storageFee' => 'required|numeric|min:0',
            'deliveryFee' => 'required|numeric|min:0'
        ]);

        try {
            $package = Package::findOrFail($this->feePackageId);
            
            // Update fees
            $package->update([
                'customs_duty' => $this->customsDuty,
                'storage_fee' => $this->storageFee,
                'delivery_fee' => $this->deliveryFee,
            ]);
            
            // Update status to ready
            $packageStatusService = app(PackageStatusService::class);
            $result = $packageStatusService->updateStatus(
                $package,
                PackageStatus::READY,
                Auth::user(),
                'Status update to ready after fee entry'
            );

            if ($result) {
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => 'Package fees updated and status set to ready successfully!'
                ]);
                
                $this->closeFeeModal();
                $this->emit('packageStatusUpdated');
                $this->emit('packagesChanged');
            } else {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => 'Failed to update package status after fee entry.'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error updating package fees: ' . $e->getMessage());
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'An error occurred while updating package fees.'
            ]);
            $this->closeFeeModal();
        }
    }

    public function showConsolidationModal()
    {
        if (empty($this->selectedPackages)) {
            $this->dispatchBrowserEvent('toastr:warning', [
                'message' => 'Please select at least 2 packages to consolidate.',
            ]);
            return;
        }

        if (count($this->selectedPackages) < 2) {
            $this->dispatchBrowserEvent('toastr:warning', [
                'message' => 'Please select at least 2 packages to consolidate.',
            ]);
            return;
        }

        // Validate consolidation eligibility
        $consolidationService = app(PackageConsolidationService::class);
        $validationResult = $consolidationService->validateConsolidation($this->selectedPackages);

        if (!$validationResult['valid']) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => $validationResult['message'],
            ]);
            return;
        }

        // Get packages for consolidation preview
        $packages = Package::whereIn('id', $this->selectedPackages)
            ->with(['user.profile'])
            ->get();

        $this->packagesForConsolidation = $packages->map(function ($package) {
            $user = $package->user;
            $fullName = 'N/A';
            
            if ($user) {
                try {
                    $fullName = $user->full_name;
                } catch (\Exception $e) {
                    $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                    if (empty($fullName)) {
                        $fullName = 'N/A';
                    }
                }
            }

            return [
                'id' => $package->id,
                'tracking_number' => $package->tracking_number,
                'description' => $package->description,
                'weight' => $package->weight,
                'user' => [
                    'id' => $user->id ?? null,
                    'full_name' => $fullName,
                    'first_name' => $user->first_name ?? '',
                    'last_name' => $user->last_name ?? '',
                    'profile' => $user && $user->profile ? [
                        'account_number' => $user->profile->account_number ?? ''
                    ] : null
                ]
            ];
        })->toArray();

        $this->showConsolidationModal = true;
    }

    public function cancelConsolidation()
    {
        $this->showConsolidationModal = false;
        $this->consolidationNotes = '';
        $this->packagesForConsolidation = [];
    }

    public function confirmConsolidation()
    {
        if (empty($this->selectedPackages) || count($this->selectedPackages) < 2) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Please select at least 2 packages to consolidate.',
            ]);
            return;
        }

        try {
            $consolidationService = app(PackageConsolidationService::class);
            $result = $consolidationService->consolidatePackages(
                $this->selectedPackages,
                Auth::user(),
                ['notes' => $this->consolidationNotes]
            );

            if ($result['success']) {
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => 'Packages consolidated successfully! Consolidated tracking number: ' . $result['consolidated_package']->consolidated_tracking_number,
                ]);

                // Reset state
                $this->cancelConsolidation();
                $this->clearSelection();
            } else {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => $result['message'],
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to consolidate packages. Please try again.',
            ]);
            Log::error('Failed to consolidate packages from individual packages tab', [
                'package_ids' => $this->selectedPackages,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function handlePreserveState($activeTab)
    {
        // Preserve component state when tab switches
        if ($activeTab !== 'individual') {
            // Store current state in session for restoration
            session()->put('individual_tab_state', [
                'search' => $this->search,
                'statusFilter' => $this->statusFilter,
                'sortBy' => $this->sortBy,
                'sortDirection' => $this->sortDirection,
                'selectedPackages' => $this->selectedPackages,
                'page' => $this->page
            ]);
        }
    }

    public function handleTabSwitch($tab)
    {
        // Handle tab switch events
        if ($tab === 'individual') {
            // Restore state from session if available
            $state = session()->get('individual_tab_state', []);
            
            $this->search = $state['search'] ?? '';
            $this->statusFilter = $state['statusFilter'] ?? '';
            $this->sortBy = $state['sortBy'] ?? 'created_at';
            $this->sortDirection = $state['sortDirection'] ?? 'desc';
            $this->selectedPackages = $state['selectedPackages'] ?? [];
            
            if (isset($state['page'])) {
                $this->page = $state['page'];
            }
        }
    }

    public function getSelectedPackagesProperty()
    {
        return Package::whereIn('id', $this->selectedPackages)->get();
    }

    public function togglePackageDetails($packageId)
    {
        $this->dispatchBrowserEvent('toggle-package-details', [
            'packageId' => $packageId
        ]);
    }
}