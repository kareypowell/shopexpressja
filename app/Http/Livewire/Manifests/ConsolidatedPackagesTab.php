<?php

namespace App\Http\Livewire\Manifests;

use App\Enums\PackageStatus;
use App\Models\ConsolidatedPackage;
use App\Models\Manifest;
use App\Services\PackageConsolidationService;
use App\Services\PackageFeeService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ConsolidatedPackagesTab extends Component
{
    use WithPagination;

    public Manifest $manifest;
    
    // Search and filtering
    public $search = '';
    public $statusFilter = '';
    public $sortBy = 'consolidated_tracking_number';
    public $sortDirection = 'desc';
    
    // Selection and bulk operations
    public $selectedConsolidatedPackages = [];
    public $selectAll = false;
    public $bulkStatus = '';
    public $showBulkConfirmModal = false;
    public $confirmingStatus = '';
    public $confirmingStatusLabel = '';
    public $bulkNotes = '';
    
    // Fee entry modal for consolidated packages
    public $showConsolidatedFeeModal = false;
    public $feeConsolidatedPackageId = null;
    public $feeConsolidatedPackage = null;
    public $consolidatedPackagesNeedingFees = [];
    
    // Unconsolidation modal
    public $showUnconsolidationModal = false;
    public $unconsolidatingPackageId = null;
    public $unconsolidationNotes = '';
    
    protected $listeners = [
        'preserveTabState' => 'handlePreserveState',
        'tabSwitched' => 'handleTabSwitch',
        'refreshConsolidatedPackages' => '$refresh',
        'packageStatusUpdated' => '$refresh'
    ];

    protected $rules = [
        'bulkStatus' => 'required|string',
        'selectedConsolidatedPackages' => 'required|array|min:1',
        'unconsolidationNotes' => 'nullable|string|max:500'
    ];

    public function mount(Manifest $manifest)
    {
        $this->manifest = $manifest;
    }

    public function render()
    {
        $consolidatedPackages = $this->getConsolidatedPackages();
        $statusOptions = $this->getStatusOptions();
        
        return view('livewire.manifests.consolidated-packages-tab', [
            'consolidatedPackages' => $consolidatedPackages,
            'statusOptions' => $statusOptions,
        ]);
    }

    public function getConsolidatedPackages()
    {
        $query = ConsolidatedPackage::whereHas('packages', function ($q) {
                $q->where('manifest_id', $this->manifest->id);
            })
            ->with([
                'packages' => function ($q) {
                    $q->where('manifest_id', $this->manifest->id);
                },
                'customer.profile'
            ])
            ->active();

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('consolidated_tracking_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('customer', function ($customerQuery) {
                      $customerQuery->where('first_name', 'like', '%' . $this->search . '%')
                                   ->orWhere('last_name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('packages', function ($packageQuery) {
                      $packageQuery->where('tracking_number', 'like', '%' . $this->search . '%')
                                  ->orWhere('description', 'like', '%' . $this->search . '%');
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
            $this->selectedConsolidatedPackages = $this->getConsolidatedPackages()->pluck('id')->toArray();
        } else {
            $this->selectedConsolidatedPackages = [];
        }
    }

    public function updatedSelectedConsolidatedPackages()
    {
        $totalPackages = $this->getConsolidatedPackages()->count();
        $this->selectAll = count($this->selectedConsolidatedPackages) === $totalPackages;
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
        $this->sortBy = 'consolidated_tracking_number';
        $this->sortDirection = 'desc';
        $this->clearSelection();
        $this->resetPage();
    }

    public function clearSelection()
    {
        $this->selectedConsolidatedPackages = [];
        $this->selectAll = false;
    }

    public function confirmBulkStatusUpdate()
    {
        $this->validate([
            'bulkStatus' => 'required|string',
            'selectedConsolidatedPackages' => 'required|array|min:1',
        ], [
            'bulkStatus.required' => 'Please select a status to update to.',
            'selectedConsolidatedPackages.required' => 'Please select at least one consolidated package.',
            'selectedConsolidatedPackages.min' => 'Please select at least one consolidated package.',
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
        if (empty($this->confirmingStatus) || empty($this->selectedConsolidatedPackages)) {
            return;
        }

        $consolidationService = app(PackageConsolidationService::class);
        $successCount = 0;
        $errorCount = 0;

        foreach ($this->selectedConsolidatedPackages as $consolidatedPackageId) {
            try {
                $consolidatedPackage = ConsolidatedPackage::find($consolidatedPackageId);
                if ($consolidatedPackage) {
                    $result = $consolidationService->updateConsolidatedStatus(
                        $consolidatedPackage,
                        $this->confirmingStatus,
                        Auth::user(),
                        ['reason' => $this->bulkNotes ?: 'Bulk status update from consolidated packages tab']
                    );

                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                }
            } catch (\Exception $e) {
                $errorCount++;
                \Log::error('Failed to update consolidated package status in bulk update', [
                    'consolidated_package_id' => $consolidatedPackageId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->cancelBulkUpdate();

        if ($successCount > 0) {
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => $successCount . ' consolidated package(s) updated successfully.'
            ]);
        }

        if ($errorCount > 0) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => $errorCount . ' consolidated package(s) failed to update. Please check the logs.'
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

    public function updateConsolidatedPackageStatus($consolidatedPackageId, $newStatus)
    {
        try {
            $consolidatedPackage = ConsolidatedPackage::findOrFail($consolidatedPackageId);
            
            // Check if transitioning to READY status - show fee modal for packages that need fees
            if ($newStatus === PackageStatus::READY) {
                $this->showConsolidatedFeeEntryModal($consolidatedPackageId);
                return;
            }
            
            $consolidationService = app(PackageConsolidationService::class);

            $result = $consolidationService->updateConsolidatedStatus(
                $consolidatedPackage,
                $newStatus,
                Auth::user(),
                ['reason' => 'Status update from consolidated packages tab']
            );

            if ($result['success']) {
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => "Consolidated package status updated successfully."
                ]);
                $this->emit('packageStatusUpdated');
                $this->emit('packagesChanged');
            } else {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => $result['message']
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to update consolidated package status.'
            ]);
            \Log::error('Failed to update consolidated package status', [
                'consolidated_package_id' => $consolidatedPackageId,
                'new_status' => $newStatus,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function showConsolidatedFeeEntryModal($consolidatedPackageId)
    {
        $this->feeConsolidatedPackageId = $consolidatedPackageId;
        $this->feeConsolidatedPackage = ConsolidatedPackage::with(['packages.user.profile'])->findOrFail($consolidatedPackageId);
        
        // Check which packages in the consolidation need fee entry
        $this->consolidatedPackagesNeedingFees = [];
        foreach ($this->feeConsolidatedPackage->packages as $package) {
            $this->consolidatedPackagesNeedingFees[] = [
                'id' => $package->id,
                'tracking_number' => $package->tracking_number,
                'description' => $package->description,
                'customs_duty' => $package->customs_duty ?? 0,
                'storage_fee' => $package->storage_fee ?? 0,
                'delivery_fee' => $package->delivery_fee ?? 0,
                'needs_fees' => $this->packageNeedsFeeEntry($package),
            ];
        }
        
        $this->showConsolidatedFeeModal = true;
    }

    private function packageNeedsFeeEntry($package): bool
    {
        return ($package->customs_duty ?? 0) == 0 || 
               ($package->storage_fee ?? 0) == 0 || 
               ($package->delivery_fee ?? 0) == 0;
    }

    public function closeConsolidatedFeeModal()
    {
        $this->showConsolidatedFeeModal = false;
        $this->feeConsolidatedPackageId = null;
        $this->feeConsolidatedPackage = null;
        $this->consolidatedPackagesNeedingFees = [];
    }

    public function processConsolidatedFeeUpdate()
    {
        try {
            $consolidatedPackage = ConsolidatedPackage::findOrFail($this->feeConsolidatedPackageId);
            
            // Update fees for each package in the consolidation
            foreach ($this->consolidatedPackagesNeedingFees as $packageData) {
                $package = \App\Models\Package::findOrFail($packageData['id']);
                
                $package->update([
                    'customs_duty' => $packageData['customs_duty'] ?? 0,
                    'storage_fee' => $packageData['storage_fee'] ?? 0,
                    'delivery_fee' => $packageData['delivery_fee'] ?? 0,
                ]);
            }
            
            // Update consolidated package status to ready
            $consolidationService = app(PackageConsolidationService::class);
            $result = $consolidationService->updateConsolidatedStatus(
                $consolidatedPackage,
                PackageStatus::READY,
                Auth::user(),
                ['reason' => 'Status update to ready after fee entry']
            );

            if ($result['success']) {
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => 'Consolidated package fees updated and status set to ready successfully!'
                ]);
                
                $this->closeConsolidatedFeeModal();
                $this->emit('packageStatusUpdated');
                $this->emit('packagesChanged');
            } else {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => 'Failed to update consolidated package status: ' . ($result['message'] ?? 'Unknown error')
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error updating consolidated package fees: ' . $e->getMessage());
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'An error occurred while updating consolidated package fees.'
            ]);
            $this->closeConsolidatedFeeModal();
        }
    }

    public function showUnconsolidationModal($consolidatedPackageId)
    {
        $this->unconsolidatingPackageId = $consolidatedPackageId;
        $this->unconsolidationNotes = '';
        $this->showUnconsolidationModal = true;
    }

    public function cancelUnconsolidation()
    {
        $this->showUnconsolidationModal = false;
        $this->unconsolidatingPackageId = null;
        $this->unconsolidationNotes = '';
    }

    public function confirmUnconsolidation()
    {
        $this->validate([
            'unconsolidationNotes' => 'nullable|string|max:500'
        ]);

        try {
            $consolidatedPackage = ConsolidatedPackage::findOrFail($this->unconsolidatingPackageId);
            $consolidationService = app(PackageConsolidationService::class);

            $result = $consolidationService->unconsolidatePackages(
                $consolidatedPackage,
                Auth::user(),
                ['notes' => $this->unconsolidationNotes ?: 'Unconsolidated from consolidated packages tab']
            );

            if ($result['success']) {
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => 'Packages unconsolidated successfully.'
                ]);
                $this->emit('packageStatusUpdated');
                $this->emit('packagesChanged');
                $this->cancelUnconsolidation();
            } else {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => $result['message']
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to unconsolidate packages.'
            ]);
            \Log::error('Failed to unconsolidate packages from consolidated packages tab', [
                'consolidated_package_id' => $this->unconsolidatingPackageId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function handlePreserveState($activeTab)
    {
        // Preserve component state when tab switches
        if ($activeTab !== 'consolidated') {
            // Store current state in session for restoration
            session()->put('consolidated_tab_state', [
                'search' => $this->search,
                'statusFilter' => $this->statusFilter,
                'sortBy' => $this->sortBy,
                'sortDirection' => $this->sortDirection,
                'selectedConsolidatedPackages' => $this->selectedConsolidatedPackages,
                'page' => $this->page
            ]);
        }
    }

    public function handleTabSwitch($tab)
    {
        // Handle tab switch events
        if ($tab === 'consolidated') {
            // Restore state from session if available
            $state = session()->get('consolidated_tab_state', []);
            
            $this->search = $state['search'] ?? '';
            $this->statusFilter = $state['statusFilter'] ?? '';
            $this->sortBy = $state['sortBy'] ?? 'consolidated_tracking_number';
            $this->sortDirection = $state['sortDirection'] ?? 'desc';
            $this->selectedConsolidatedPackages = $state['selectedConsolidatedPackages'] ?? [];
            
            if (isset($state['page'])) {
                $this->page = $state['page'];
            }
        }
    }

    public function getSelectedConsolidatedPackagesProperty()
    {
        return ConsolidatedPackage::whereIn('id', $this->selectedConsolidatedPackages)->get();
    }


}