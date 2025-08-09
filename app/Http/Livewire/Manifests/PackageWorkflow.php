<?php

namespace App\Http\Livewire\Manifests;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Services\PackageStatusService;
use App\Services\PackageFeeService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class PackageWorkflow extends Component
{
    use WithPagination;

    public $selectedPackages = [];
    public $selectAll = false;
    public $bulkStatus = '';
    public $showConfirmModal = false;
    public $confirmingStatus = '';
    public $confirmingStatusLabel = '';
    public $confirmingPackages = [];
    public $statusFilter = '';
    public $search = '';
    public $notes = '';
    public $manifestId = null;

    // Fee entry modal properties
    public $showFeeModal = false;
    public $feePackageId = null;
    public $feePackage = null;
    public $customsDuty = 0;
    public $storageFee = 0;
    public $deliveryFee = 0;
    public $applyCreditBalance = false;
    public $feePreview = null;

    protected $packageStatusService;

    protected $listeners = [
        'refreshPackages' => '$refresh',
        'packageStatusUpdated' => 'handleStatusUpdate'
    ];

    protected $rules = [
        'customsDuty' => 'required|numeric|min:0',
        'storageFee' => 'required|numeric|min:0',
        'deliveryFee' => 'required|numeric|min:0',
    ];

    public function boot(PackageStatusService $packageStatusService)
    {
        $this->packageStatusService = $packageStatusService;
    }

    public function mount($manifest = null)
    {
        if ($manifest instanceof \App\Models\Manifest) {
            $this->manifestId = $manifest->id;
        } else {
            $this->manifestId = $manifest;
        }
    }

    public function render()
    {
        $packages = $this->getPackages();
        $statusOptions = $this->getStatusOptions();
        $statusStatistics = $this->packageStatusService->getStatusStatistics();

        return view('livewire.manifests.package-workflow', [
            'packages' => $packages,
            'statusOptions' => $statusOptions,
            'statusStatistics' => $statusStatistics,
        ]);
    }

    public function getPackages()
    {
        $query = Package::with(['user', 'manifest', 'office', 'shipper']);

        // Filter by manifest if specified
        if ($this->manifestId) {
            $query->where('manifest_id', $this->manifestId);
        }

        // Apply status filter
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // Apply search
        if ($this->search) {
            $query->search($this->search);
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    public function getStatusOptions()
    {
        $options = [];
        // Use manualUpdateCases to exclude DELIVERED status from manual updates
        foreach (PackageStatus::manualUpdateCases() as $status) {
            $options[$status->value] = $status->getLabel();
        }
        return $options;
    }

    public function updatedSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedPackages = $this->getPackages()->pluck('id')->toArray();
        } else {
            $this->selectedPackages = [];
        }
    }

    public function updatedSelectedPackages()
    {
        $totalPackages = $this->getPackages()->count();
        $this->selectAll = count($this->selectedPackages) === $totalPackages;
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
        $this->selectedPackages = [];
        $this->selectAll = false;
    }

    public function updatedSearch()
    {
        $this->resetPage();
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
            $packages = Package::whereIn('id', $this->selectedPackages)->get();
            
            // Validate transitions for all selected packages
            $invalidTransitions = [];
            foreach ($packages as $package) {
                if (!$package->canTransitionTo($newStatus)) {
                    $invalidTransitions[] = [
                        'tracking_number' => $package->tracking_number,
                        'current_status' => $package->status->getLabel(),
                        'attempted_status' => $newStatus->getLabel(),
                    ];
                }
            }

            if (!empty($invalidTransitions)) {
                $this->addError('bulkStatus', 'Some packages cannot transition to the selected status. Please check individual package statuses.');
                return;
            }

            $this->confirmingStatus = $newStatus->value;
            $this->confirmingStatusLabel = $newStatus->getLabel();
            $this->confirmingPackages = $packages->toArray();
            $this->showConfirmModal = true;



        } catch (\InvalidArgumentException $e) {
            $this->addError('bulkStatus', 'Invalid status selected.');
        }
    }

    public function executeBulkStatusUpdate()
    {
        if (empty($this->confirmingStatus) || empty($this->confirmingPackages)) {
            return;
        }

        // Prevent manual updates to DELIVERED status
        if ($this->confirmingStatus === PackageStatus::DELIVERED) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Packages can only be marked as delivered through the distribution process.'
            ]);
            $this->showConfirmModal = false;
            return;
        }

        $packageIds = collect($this->confirmingPackages)->pluck('id')->toArray();
        $statusEnum = PackageStatus::from($this->confirmingStatus);
        $results = $this->packageStatusService->bulkUpdateStatus(
            $packageIds,
            $statusEnum,
            Auth::user(),
            $this->notes ?: null
        );

        $this->showConfirmModal = false;
        $this->selectedPackages = [];
        $this->selectAll = false;
        $this->bulkStatus = '';
        $this->notes = '';
        $this->confirmingStatus = '';
        $this->confirmingStatusLabel = '';
        $this->confirmingPackages = [];

        if (count($results['success']) > 0) {
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => count($results['success']) . ' package(s) updated successfully.'
            ]);
        }

        if (count($results['failed']) > 0) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => count($results['failed']) . ' package(s) failed to update. Please check the logs.'
            ]);
        }

        $this->emit('packageStatusUpdated');
    }

    public function cancelBulkUpdate()
    {
        $this->showConfirmModal = false;
        $this->confirmingStatus = '';
        $this->confirmingStatusLabel = '';
        $this->confirmingPackages = [];
        $this->notes = '';
    }

    public function updateSinglePackageStatus($packageId, $newStatusValue)
    {
        try {
            $package = Package::findOrFail($packageId);
            $newStatus = PackageStatus::from($newStatusValue);

            // Prevent manual updates to DELIVERED status
            if ($newStatus->value === PackageStatus::DELIVERED) {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => "Packages can only be marked as delivered through the distribution process."
                ]);
                return;
            }

            // Check if transitioning to READY status - show fee modal
            if ($newStatus->value === PackageStatus::READY) {
                $this->showFeeEntryModal($packageId);
                return;
            }

            if (!$package->canTransitionTo($newStatus)) {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => "Cannot transition package {$package->tracking_number} from {$package->status->getLabel()} to {$newStatus->getLabel()}."
                ]);
                return;
            }

            if ($this->packageStatusService->updateStatus($package, $newStatus, Auth::user())) {
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => "Package {$package->tracking_number} status updated to {$newStatus->getLabel()}."
                ]);
                $this->emit('packageStatusUpdated');
            } else {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => "Failed to update package {$package->tracking_number} status."
                ]);
            }

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'An error occurred while updating the package status.'
            ]);
        }
    }

    public function getValidTransitionsForPackage($packageId)
    {
        $package = Package::find($packageId);
        if (!$package) {
            return [];
        }

        $transitions = [];
        foreach ($package->getValidStatusTransitions() as $status) {
            $transitions[$status->value] = $status->getLabel();
        }

        return $transitions;
    }

    public function handleStatusUpdate()
    {
        // Refresh the component when status is updated
        $this->render();
    }

    public function clearFilters()
    {
        $this->statusFilter = '';
        $this->search = '';
        $this->selectedPackages = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    public function getSelectedPackagesProperty()
    {
        return Package::whereIn('id', $this->selectedPackages)->get();
    }

    public function canDistributeSelected()
    {
        if (empty($this->selectedPackages)) {
            return false;
        }

        $packages = Package::whereIn('id', $this->selectedPackages)->get();
        return $packages->every(function ($package) {
            return $package->canBeDistributed();
        });
    }

    public function getNextLogicalStatus($currentStatusValue)
    {
        $currentStatus = PackageStatus::from($currentStatusValue);
        $validTransitions = $currentStatus->getValidTransitions();
        
        // Define the logical progression order (excluding DELIVERED for manual updates)
        $progressionOrder = [
            PackageStatus::PENDING => PackageStatus::PROCESSING(),
            PackageStatus::PROCESSING => PackageStatus::SHIPPED(),
            PackageStatus::SHIPPED => PackageStatus::CUSTOMS(),
            PackageStatus::CUSTOMS => PackageStatus::READY(),
            // PackageStatus::READY => PackageStatus::DELIVERED(), // Removed - only through distribution
        ];
        
        // Return the next logical status if it's a valid transition and not DELIVERED
        if (isset($progressionOrder[$currentStatus->value])) {
            $nextStatus = $progressionOrder[$currentStatus->value];
            foreach ($validTransitions as $transition) {
                if ($transition->value === $nextStatus->value && $nextStatus->value !== PackageStatus::DELIVERED) {
                    return $nextStatus;
                }
            }
        }
        
        // If no logical next status, return the first valid transition (excluding DELIVERED)
        foreach ($validTransitions as $transition) {
            if ($transition->value !== PackageStatus::DELIVERED) {
                return $transition;
            }
        }
        return null;
    }

    public function getCommonNextStatus()
    {
        if (empty($this->selectedPackages)) {
            return null;
        }

        $packages = Package::whereIn('id', $this->selectedPackages)->get();
        $nextStatuses = [];

        foreach ($packages as $package) {
            $nextStatus = $this->getNextLogicalStatus($package->status);
            if ($nextStatus) {
                $nextStatuses[] = $nextStatus->value;
            }
        }

        // If all packages have the same next status, return it
        $uniqueNextStatuses = array_unique($nextStatuses);
        if (count($uniqueNextStatuses) === 1) {
            return PackageStatus::from($uniqueNextStatuses[0]);
        }

        return null;
    }

    public function bulkAdvanceToNext()
    {
        $commonNextStatus = $this->getCommonNextStatus();
        if (!$commonNextStatus) {
            session()->flash('error', 'Selected packages cannot be advanced to a common next status.');
            return;
        }

        $this->bulkStatus = $commonNextStatus->value;
        $this->confirmBulkStatusUpdate();
    }

    public function initiateDistribution($packageIds = null)
    {
        // If specific package IDs are provided, use those; otherwise use selected packages
        $packagesToDistribute = $packageIds ?: $this->selectedPackages;
        
        if (empty($packagesToDistribute)) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Please select packages to distribute.'
            ]);
            return;
        }

        // Validate that all packages can be distributed
        $packages = Package::whereIn('id', $packagesToDistribute)->get();
        $invalidPackages = $packages->filter(function ($package) {
            return !$package->canBeDistributed();
        });

        if ($invalidPackages->count() > 0) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Only packages with "Ready for Pickup" status can be distributed.'
            ]);
            return;
        }

        // Store selected packages in session for the distribution page
        session(['distribution_packages' => $packagesToDistribute]);
        
        // Redirect to the new standalone distribution page
        try {
            return redirect()->route('package-distribution');
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Unable to navigate to distribution page. Please try again.'
            ]);
            \Log::error('Distribution redirect error', [
                'error' => $e->getMessage(),
                'packages' => $packagesToDistribute
            ]);
        }
    }

    /**
     * Show fee entry modal for transitioning to ready status
     */
    public function showFeeEntryModal($packageId)
    {
        $this->feePackageId = $packageId;
        $this->feePackage = Package::with('user.profile')->findOrFail($packageId);
        
        // Reset form fields
        $this->customsDuty = $this->feePackage->customs_duty ?? 0;
        $this->storageFee = $this->feePackage->storage_fee ?? 0;
        $this->deliveryFee = $this->feePackage->delivery_fee ?? 0;
        $this->applyCreditBalance = false;
        $this->feePreview = null;
        
        $this->showFeeModal = true;
        
        // Generate initial preview
        $this->updateFeePreview();
    }

    /**
     * Update fee preview when values change
     */
    public function updateFeePreview()
    {
        if (!$this->feePackage) {
            return;
        }

        $fees = [
            'customs_duty' => $this->customsDuty,
            'storage_fee' => $this->storageFee,
            'delivery_fee' => $this->deliveryFee,
        ];

        $feeService = app(PackageFeeService::class);
        $this->feePreview = $feeService->getFeeUpdatePreview(
            $this->feePackage,
            $fees,
            $this->applyCreditBalance
        );
    }

    /**
     * Update fees when form values change
     */
    public function updatedCustomsDuty()
    {
        $this->updateFeePreview();
    }

    public function updatedStorageFee()
    {
        $this->updateFeePreview();
    }

    public function updatedDeliveryFee()
    {
        $this->updateFeePreview();
    }

    public function updatedApplyCreditBalance()
    {
        $this->updateFeePreview();
    }

    /**
     * Process fee update and set package to ready
     */
    public function processFeeUpdate()
    {
        $this->validate();

        if (!$this->feePackage) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Package not found.'
            ]);
            return;
        }

        $fees = [
            'customs_duty' => $this->customsDuty,
            'storage_fee' => $this->storageFee,
            'delivery_fee' => $this->deliveryFee,
        ];

        $feeService = app(PackageFeeService::class);
        $result = $feeService->updatePackageFeesAndSetReady(
            $this->feePackage,
            $fees,
            Auth::user(),
            $this->applyCreditBalance
        );

        if ($result['success']) {
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => $result['message']
            ]);
            
            $this->closeFeeModal();
            $this->emit('packageStatusUpdated');
        } else {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => $result['message']
            ]);
        }
    }

    /**
     * Close fee entry modal
     */
    public function closeFeeModal()
    {
        $this->showFeeModal = false;
        $this->feePackageId = null;
        $this->feePackage = null;
        $this->customsDuty = 0;
        $this->storageFee = 0;
        $this->deliveryFee = 0;
        $this->applyCreditBalance = false;
        $this->feePreview = null;
        $this->resetErrorBag();
    }
}