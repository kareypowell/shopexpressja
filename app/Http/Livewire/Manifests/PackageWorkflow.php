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

    // Consolidation properties
    public $showConsolidationModal = false;
    public $consolidationNotes = '';
    public $packagesForConsolidation = [];

    // Fee entry modal properties
    public $showFeeModal = false;
    public $feePackageId = null;
    public $feePackage = null;
    public $customsDuty = 0;
    public $storageFee = 0;
    public $deliveryFee = 0;
    public $applyCreditBalance = false;
    public $feePreview = null;

    // Consolidated package fee modal properties
    public $showConsolidatedFeeModal = false;
    public $feeConsolidatedPackageId = null;
    public $feeConsolidatedPackage = null;
    public $consolidatedPackagesNeedingFees = [];

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

    /**
     * Get the manifest instance
     */
    public function getManifestProperty()
    {
        return $this->manifestId ? \App\Models\Manifest::find($this->manifestId) : null;
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
        $query = Package::with(['user', 'manifest', 'office', 'shipper', 'consolidatedPackage']);

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
            $query->searchWithConsolidated($this->search);
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    /**
     * Get consolidated packages for the current manifest
     */
    public function getConsolidatedPackagesProperty()
    {
        if (!$this->manifestId) {
            return collect();
        }

        return \App\Models\ConsolidatedPackage::whereHas('packages', function ($query) {
                $query->where('manifest_id', $this->manifestId);
            })
            ->with(['packages' => function ($query) {
                $query->where('manifest_id', $this->manifestId);
            }, 'customer.profile'])
            ->active()
            ->orderBy('consolidated_tracking_number', 'desc')
            ->get();
    }

    /**
     * Get manifest totals including consolidated packages
     */
    public function getManifestTotalsProperty()
    {
        if (!$this->manifestId) {
            return [
                'individual_packages' => 0,
                'consolidated_packages' => 0,
                'total_packages_in_consolidated' => 0,
                'total_weight' => 0,
                'total_cost' => 0,
            ];
        }

        $individualPackages = Package::where('manifest_id', $this->manifestId)
            ->individual()
            ->get();

        $consolidatedPackages = $this->consolidatedPackages;

        $totals = [
            'individual_packages' => $individualPackages->count(),
            'consolidated_packages' => $consolidatedPackages->count(),
            'total_packages_in_consolidated' => $consolidatedPackages->sum('total_quantity'),
            'total_weight' => $individualPackages->sum('weight') + $consolidatedPackages->sum('total_weight'),
            'total_freight_price' => $individualPackages->sum('freight_price') + $consolidatedPackages->sum('total_freight_price'),
            'total_customs_duty' => $individualPackages->sum('customs_duty') + $consolidatedPackages->sum('total_customs_duty'),
            'total_storage_fee' => $individualPackages->sum('storage_fee') + $consolidatedPackages->sum('total_storage_fee'),
            'total_delivery_fee' => $individualPackages->sum('delivery_fee') + $consolidatedPackages->sum('total_delivery_fee'),
        ];

        $totals['total_cost'] = $totals['total_freight_price'] + 
                               $totals['total_customs_duty'] + 
                               $totals['total_storage_fee'] + 
                               $totals['total_delivery_fee'];

        return $totals;
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
        
        // Handle consolidated packages separately
        $consolidationService = app(\App\Services\PackageConsolidationService::class);
        $consolidatedPackageIds = [];
        $individualPackageIds = [];

        foreach ($packageIds as $packageId) {
            $package = Package::find($packageId);
            if ($package && $package->isConsolidated()) {
                $consolidatedPackageIds[] = $package->consolidated_package_id;
            } else {
                $individualPackageIds[] = $packageId;
            }
        }

        $successCount = 0;
        $errorCount = 0;

        // Update individual packages
        if (!empty($individualPackageIds)) {
            $results = $this->packageStatusService->bulkUpdateStatus(
                $individualPackageIds,
                $statusEnum,
                Auth::user(),
                $this->notes ?: null
            );
            $successCount += count($results['success']);
            $errorCount += count($results['failed']);
        }

        // Update consolidated packages
        foreach (array_unique($consolidatedPackageIds) as $consolidatedPackageId) {
            try {
                $consolidatedPackage = \App\Models\ConsolidatedPackage::find($consolidatedPackageId);
                if ($consolidatedPackage) {
                    $result = $consolidationService->updateConsolidatedStatus(
                        $consolidatedPackage,
                        $this->confirmingStatus,
                        Auth::user(),
                        ['reason' => $this->notes ?: 'Bulk status update from manifest workflow']
                    );

                    if ($result['success']) {
                        $successCount += $consolidatedPackage->packages()->count();
                    } else {
                        $errorCount += $consolidatedPackage->packages()->count();
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

        $this->showConfirmModal = false;
        $this->selectedPackages = [];
        $this->selectAll = false;
        $this->bulkStatus = '';
        $this->notes = '';
        $this->confirmingStatus = '';
        $this->confirmingStatusLabel = '';
        $this->confirmingPackages = [];

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
    }

    /**
     * Update consolidated package status
     */
    public function updateConsolidatedPackageStatus($consolidatedPackageId, $newStatus)
    {
        try {
            $consolidatedPackage = \App\Models\ConsolidatedPackage::findOrFail($consolidatedPackageId);
            
            // Check if transitioning to READY status - show fee modal for packages that need fees
            if ($newStatus === \App\Enums\PackageStatus::READY) {
                $this->showConsolidatedFeeEntryModal($consolidatedPackageId);
                return;
            }
            
            $consolidationService = app(\App\Services\PackageConsolidationService::class);

            $result = $consolidationService->updateConsolidatedStatus(
                $consolidatedPackage,
                $newStatus,
                Auth::user(),
                ['reason' => 'Status update from manifest workflow']
            );

            if ($result['success']) {
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => "Consolidated package status updated successfully."
                ]);
                $this->emit('packageStatusUpdated');
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

    /**
     * Show consolidation modal for selected packages
     */
    public function showConsolidationModal()
    {
        if (empty($this->selectedPackages)) {
            $this->dispatchBrowserEvent('toastr:warning', [
                'message' => 'Please select at least 2 packages to consolidate.'
            ]);
            return;
        }

        if (count($this->selectedPackages) < 2) {
            $this->dispatchBrowserEvent('toastr:warning', [
                'message' => 'Please select at least 2 packages to consolidate.'
            ]);
            return;
        }

        // Validate consolidation eligibility
        $consolidationService = app(\App\Services\PackageConsolidationService::class);
        $validationResult = $consolidationService->validateConsolidation($this->selectedPackages);

        if (!$validationResult['valid']) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => $validationResult['message']
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
                // Try to get full name using the accessor
                try {
                    $fullName = $user->full_name;
                } catch (\Exception $e) {
                    // Fallback to manual concatenation
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

    /**
     * Cancel consolidation
     */
    public function cancelConsolidation()
    {
        $this->showConsolidationModal = false;
        $this->consolidationNotes = '';
        $this->packagesForConsolidation = [];
    }

    /**
     * Confirm and execute consolidation
     */
    public function confirmConsolidation()
    {
        if (empty($this->selectedPackages) || count($this->selectedPackages) < 2) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Please select at least 2 packages to consolidate.'
            ]);
            return;
        }

        try {
            $consolidationService = app(\App\Services\PackageConsolidationService::class);
            $result = $consolidationService->consolidatePackages(
                $this->selectedPackages,
                Auth::user(),
                ['notes' => $this->consolidationNotes]
            );

            if ($result['success']) {
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => 'Packages consolidated successfully! Consolidated tracking number: ' . $result['consolidated_package']->consolidated_tracking_number
                ]);

                // Reset state
                $this->cancelConsolidation();
                $this->selectedPackages = [];
                $this->selectAll = false;
                $this->emit('packageStatusUpdated');
            } else {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => $result['message']
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to consolidate packages. Please try again.'
            ]);
            \Log::error('Failed to consolidate packages from workflow view', [
                'package_ids' => $this->selectedPackages,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Unconsolidate a consolidated package
     */
    public function unconsolidatePackage($consolidatedPackageId)
    {
        try {
            $consolidatedPackage = \App\Models\ConsolidatedPackage::findOrFail($consolidatedPackageId);
            $consolidationService = app(\App\Services\PackageConsolidationService::class);

            $result = $consolidationService->unconsolidatePackages(
                $consolidatedPackage,
                Auth::user(),
                ['notes' => 'Unconsolidated from workflow view']
            );

            if ($result['success']) {
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => 'Packages unconsolidated successfully.'
                ]);
                $this->emit('packageStatusUpdated');
            } else {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => $result['message']
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to unconsolidate packages.'
            ]);
            \Log::error('Failed to unconsolidate packages from workflow view', [
                'consolidated_package_id' => $consolidatedPackageId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if selected packages can be consolidated
     */
    public function getCanConsolidateSelectedProperty()
    {
        if (count($this->selectedPackages) < 2) {
            return false;
        }

        $consolidationService = app(\App\Services\PackageConsolidationService::class);
        $validationResult = $consolidationService->validateConsolidation($this->selectedPackages);

        return $validationResult['valid'];
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

    /**
     * Show consolidated fee entry modal for transitioning to ready status
     */
    public function showConsolidatedFeeEntryModal($consolidatedPackageId)
    {
        $this->feeConsolidatedPackageId = $consolidatedPackageId;
        $this->feeConsolidatedPackage = \App\Models\ConsolidatedPackage::with(['packages.user.profile'])->findOrFail($consolidatedPackageId);
        
        // Check which packages in the consolidation need fee entry
        $this->consolidatedPackagesNeedingFees = [];
        foreach ($this->feeConsolidatedPackage->packages as $package) {
            // Always include all packages for fee review when transitioning to ready
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

    /**
     * Check if a package needs fee entry
     */
    private function packageNeedsFeeEntry($package): bool
    {
        // Package needs fee entry if any required fees are missing or zero
        return ($package->customs_duty ?? 0) == 0 || 
               ($package->storage_fee ?? 0) == 0 || 
               ($package->delivery_fee ?? 0) == 0;
    }

    /**
     * Close consolidated fee entry modal
     */
    public function closeConsolidatedFeeModal()
    {
        $this->showConsolidatedFeeModal = false;
        $this->feeConsolidatedPackageId = null;
        $this->feeConsolidatedPackage = null;
        $this->consolidatedPackagesNeedingFees = [];
    }

    /**
     * Process consolidated package fee updates and set status to ready
     */
    public function processConsolidatedFeeUpdate()
    {
        try {
            $consolidatedPackage = \App\Models\ConsolidatedPackage::findOrFail($this->feeConsolidatedPackageId);
            
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
            $consolidationService = app(\App\Services\PackageConsolidationService::class);
            $result = $consolidationService->updateConsolidatedStatus(
                $consolidatedPackage,
                \App\Enums\PackageStatus::READY,
                Auth::user(),
                ['reason' => 'Status update to ready after fee entry']
            );

            if ($result['success']) {
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => 'Consolidated package fees updated and status set to ready successfully!'
                ]);
                
                $this->closeConsolidatedFeeModal();
                $this->emit('packageStatusUpdated');
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

    /**
     * Process consolidated package status update to ready (after fee entry)
     */
    public function processConsolidatedStatusUpdate()
    {
        try {
            $consolidatedPackage = \App\Models\ConsolidatedPackage::findOrFail($this->feeConsolidatedPackageId);
            $consolidationService = app(\App\Services\PackageConsolidationService::class);

            $result = $consolidationService->updateConsolidatedStatus(
                $consolidatedPackage,
                \App\Enums\PackageStatus::READY,
                Auth::user(),
                ['reason' => 'Status update to ready after fee entry']
            );

            if ($result['success']) {
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => "Consolidated package status updated to ready successfully."
                ]);
                $this->emit('packageStatusUpdated');
                $this->closeConsolidatedFeeModal();
            } else {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => $result['message']
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to update consolidated package status.'
            ]);
            \Log::error('Failed to update consolidated package status after fee entry', [
                'consolidated_package_id' => $this->feeConsolidatedPackageId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update fees for packages in consolidated package
     */
    public function updateConsolidatedPackageFees()
    {
        try {
            $feeService = app(\App\Services\PackageFeeService::class);
            $updatedCount = 0;

            foreach ($this->consolidatedPackagesNeedingFees as $packageData) {
                $package = \App\Models\Package::find($packageData['id']);
                if ($package) {
                    $fees = [
                        'customs_duty' => $packageData['customs_duty'],
                        'storage_fee' => $packageData['storage_fee'],
                        'delivery_fee' => $packageData['delivery_fee'],
                    ];

                    $result = $feeService->updatePackageFees($package, $fees, Auth::user());
                    if ($result['success']) {
                        $updatedCount++;
                    }
                }
            }

            if ($updatedCount > 0) {
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => "Updated fees for {$updatedCount} package(s)."
                ]);
                
                // Now proceed with status update
                $this->processConsolidatedStatusUpdate();
            } else {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => 'Failed to update package fees.'
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'An error occurred while updating fees.'
            ]);
            \Log::error('Failed to update consolidated package fees', [
                'consolidated_package_id' => $this->feeConsolidatedPackageId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function showPackageDetails($packageId)
    {
        $package = Package::find($packageId);
        
        if (!$package) {
            session()->flash('error', 'Package not found.');
            return;
        }

        // If we have a manifest context, redirect to the manifest packages page
        if ($this->manifestId && $package->manifest_id == $this->manifestId) {
            return redirect()->route('admin.manifests.packages', $package->manifest_id);
        }
        
        // Otherwise, emit an event or show a notification
        $this->dispatchBrowserEvent('show-package-details', [
            'package' => [
                'id' => $package->id,
                'tracking_number' => $package->tracking_number,
                'status' => $package->status,
                'customer' => $package->user->full_name ?? 'N/A',
                'weight' => $package->weight,
                'description' => $package->description,
                'total_cost' => $package->total_cost,
            ]
        ]);
    }
}