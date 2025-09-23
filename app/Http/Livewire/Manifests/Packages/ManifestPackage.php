<?php

namespace App\Http\Livewire\Manifests\Packages;

use Livewire\Component;
use App\Models\Shipper;
use App\Models\User;
use App\Models\Office;
use App\Models\Package;
use App\Models\PackagePreAlert;
use App\Models\PreAlert;
use App\Models\Rate;
use App\Models\Manifest;
use App\Models\PackageItem;
use App\Models\ConsolidatedPackage;
use Illuminate\Support\Facades\Log;
use App\Notifications\MissingPreAlertNotification;
use App\Services\SeaRateCalculator;
use App\Rules\ValidContainerDimensions;
use App\Rules\ValidPackageItems;
use App\Exceptions\SeaRateNotFoundException;
use App\Enums\PackageStatus;
use App\Services\PackageStatusService;
use App\Services\ManifestLockService;
use Livewire\WithPagination;

class ManifestPackage extends Component
{
    use WithPagination;

    protected $listeners = ['manifestUnlocked' => 'refreshManifestData'];

    public bool $isOpen = false;
    public int $user_id = 0;
    public $manifest_id = 0;
    public int $shipper_id = 0;
    public int $office_id = 0;
    public string $warehouse_receipt_no = '';
    public string $tracking_number = '';
    public string $description = '';
    public string $weight = '';
    public string $status = '';
    public string $estimated_value = '';
    public $customerList = [];
    public $shipperList = [];
    public $officeList = [];
    public $isSeaManifest = null;

    // Package workflow properties
    public array $selectedPackages = [];
    public bool $selectAll = false;
    public string $bulkStatus = '';
    public string $statusFilter = '';
    public string $searchTerm = '';
    public bool $showBulkActions = false;
    public bool $showStatusUpdateModal = false;
    public string $confirmationMessage = '';

    // Consolidation properties
    public bool $showConsolidationModal = false;
    public string $consolidationNotes = '';
    public array $packagesForConsolidation = [];

    // Consolidated package fee modal properties
    public bool $showConsolidatedFeeModal = false;
    public $feeConsolidatedPackageId = null;
    public $feeConsolidatedPackage = null;
    public array $consolidatedPackagesNeedingFees = [];

    // Services
    protected $packageStatusService;
    protected $manifestLockService;
    
    // Customer search properties
    public string $customerSearch = '';
    public bool $showCustomerDropdown = false;
    public $filteredCustomers = [];
    public string $selectedCustomerDisplay = '';

    // Sea-specific properties
    public string $container_type = '';
    public string $length_inches = '';
    public string $width_inches = '';
    public string $height_inches = '';
    public float $cubic_feet = 0;
    public array $items = [];


    public function mount($manifest = null)
    {
        $this->customerList = User::customerUsers()
                                  ->where('email_verified_at', '!=', '')
                                  ->orderBy('last_name', 'asc')->get();

        $this->officeList = Office::orderBy('name', 'asc')->get();

        $this->shipperList = Shipper::orderBy('name', 'asc')->get();

        // Handle manifest parameter from route model binding or fallback to route parameter
        if ($manifest instanceof Manifest) {
            $this->manifest_id = $manifest->id;
            $manifestModel = $manifest;
        } else {
            // Fallback for testing or when manifest is not bound
            $this->manifest_id = $manifest ?? request()->route('manifest_id') ?? request()->route('manifest');
            $manifestModel = Manifest::find($this->manifest_id);
        }

        $this->isSeaManifest = $manifestModel && $manifestModel->type === 'sea';
        
        // Initialize items array for sea manifests
        $this->initializeItems();
        
        // Initialize filtered customers as empty
        $this->filteredCustomers = collect();

        // Initialize package status service
        $this->packageStatusService = app(PackageStatusService::class);
        
        // Initialize manifest lock service
        $this->manifestLockService = app(ManifestLockService::class);
        
        // Ensure selected packages array is properly initialized
        $this->selectedPackages = [];
        $this->selectAll = false;
    }

    /**
     * Get the package status service instance
     */
    protected function getPackageStatusService(): PackageStatusService
    {
        if (!$this->packageStatusService) {
            $this->packageStatusService = app(PackageStatusService::class);
        }
        return $this->packageStatusService;
    }

    /**
     * Get the manifest lock service instance
     */
    protected function getManifestLockService(): ManifestLockService
    {
        if (!$this->manifestLockService) {
            $this->manifestLockService = app(ManifestLockService::class);
        }
        return $this->manifestLockService;
    }

    public function create()
    {
        if (!$this->canEditManifest) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Cannot add packages to a closed manifest.',
            ]);
            return;
        }

        $this->resetInputFields();
        $this->openModal();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public function openModal()
    {
        $this->isOpen = true;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public function closeModal()
    {
        $this->isOpen = false;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    private function resetInputFields()
    {
        $this->user_id = 0;
        $this->shipper_id = 0;
        $this->office_id = 0;
        $this->warehouse_receipt_no = '';
        $this->tracking_number = '';
        $this->description = '';
        $this->weight = '';
        $this->status = '';
        $this->estimated_value = '';
        
        // Reset customer search fields
        $this->customerSearch = '';
        $this->selectedCustomerDisplay = '';
        $this->showCustomerDropdown = false;
        $this->filteredCustomers = collect();
        
        // Reset sea-specific fields
        $this->container_type = '';
        $this->length_inches = '';
        $this->width_inches = '';
        $this->height_inches = '';
        $this->cubic_feet = 0;
        $this->items = [];
        $this->initializeItems();
    }

    /**
     * Initialize items array with one empty item for sea manifests
     */
    private function initializeItems()
    {
        if ($this->isSeaManifest() && empty($this->items)) {
            $this->items = [
                [
                    'description' => '',
                    'quantity' => 1,
                    'weight_per_item' => ''
                ]
            ];
        }
    }

    /**
     * Determine if the current manifest is a sea manifest
     */
    public function isSeaManifest(): bool
    {
        // Use cached value if available
        if ($this->isSeaManifest !== null) {
            return $this->isSeaManifest;
        }
        
        if (!$this->manifest_id) {
            return false;
        }
        
        $manifest = Manifest::find($this->manifest_id);
        $this->isSeaManifest = $manifest && $manifest->type === 'sea';
        return $this->isSeaManifest;
    }

    /**
     * Calculate cubic feet from dimensions with real-time calculation
     * Formula: (length × width × height) ÷ 1728
     */
    public function calculateCubicFeet()
    {
        if ($this->length_inches && $this->width_inches && $this->height_inches) {
            $this->cubic_feet = round(
                ($this->length_inches * $this->width_inches * $this->height_inches) / 1728, 
                3
            );
        } else {
            $this->cubic_feet = 0;
        }
    }

    /**
     * Add a new item to the items array
     */
    public function addItem()
    {
        $this->items[] = [
            'description' => '',
            'quantity' => 1,
            'weight_per_item' => ''
        ];
    }

    /**
     * Remove an item from the items array
     */
    public function removeItem($index)
    {
        if (isset($this->items[$index])) {
            unset($this->items[$index]);
            $this->items = array_values($this->items); // Re-index array
        }
    }

    /**
     * Update cubic feet when dimensions change
     */
    public function updatedLengthInches()
    {
        $this->calculateCubicFeet();
    }

    public function updatedWidthInches()
    {
        $this->calculateCubicFeet();
    }

    public function updatedHeightInches()
    {
        $this->calculateCubicFeet();
    }

    /**
     * Real-time calculation method that can be called from the frontend
     */
    public function recalculateCubicFeet()
    {
        $this->calculateCubicFeet();
    }

    /**
     * Handle when manifest_id is updated (for testing purposes)
     */
    public function updatedManifestId()
    {
        if ($this->manifest_id) {
            $manifest = Manifest::find($this->manifest_id);
            $this->isSeaManifest = $manifest && $manifest->type === 'sea';
            $this->initializeItems();
        }
    }

    /**
     * Handle customer search input changes
     */
    public function updatedCustomerSearch()
    {
        if (strlen($this->customerSearch) >= 1) {
            $this->filteredCustomers = User::customerUsers()
                ->where('email_verified_at', '!=', '')
                ->search($this->customerSearch)
                ->orderBy('last_name', 'asc')
                ->limit(10)
                ->get();
            $this->showCustomerDropdown = true;
        } else {
            $this->filteredCustomers = collect();
            $this->showCustomerDropdown = false;
        }
        
        // Reset user_id if search is cleared
        if (empty($this->customerSearch)) {
            $this->user_id = 0;
            $this->selectedCustomerDisplay = '';
        }
    }

    /**
     * Select a customer from search results
     */
    public function selectCustomer($customerId)
    {
        $customer = User::find($customerId);
        if ($customer && $customer->profile) {
            $this->user_id = $customerId;
            $this->selectedCustomerDisplay = $customer->full_name . " (" . $customer->profile->account_number . ")";
            $this->customerSearch = $this->selectedCustomerDisplay;
            $this->showCustomerDropdown = false;
            $this->filteredCustomers = collect();
        }
    }

    /**
     * Show all customers when focusing on search field
     */
    public function showAllCustomers()
    {
        if (empty($this->customerSearch)) {
            $this->filteredCustomers = User::customerUsers()
                ->where('email_verified_at', '!=', '')
                ->orderBy('last_name', 'asc')
                ->limit(10)
                ->get();
            $this->showCustomerDropdown = true;
        }
    }

    /**
     * Hide customer dropdown
     */
    public function hideCustomerDropdown()
    {
        $this->showCustomerDropdown = false;
    }

    /**
     * Clear customer selection
     */
    public function clearCustomerSelection()
    {
        $this->user_id = 0;
        $this->customerSearch = '';
        $this->selectedCustomerDisplay = '';
        $this->filteredCustomers = collect();
        $this->showCustomerDropdown = false;
    }

    // Package Workflow Methods

    /**
     * Get packages for the current manifest with filtering and search
     * Includes both individual packages and consolidated packages
     */
    public function getPackagesProperty()
    {
        $query = Package::where('manifest_id', $this->manifest_id)
            ->with(['user.profile', 'shipper', 'office', 'consolidatedPackage']);

        // Apply status filter
        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        // Apply search filter
        if (!empty($this->searchTerm)) {
            $query->where(function ($q) {
                $q->where('tracking_number', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('warehouse_receipt_no', 'like', '%' . $this->searchTerm . '%')
                  ->orWhereHas('user', function ($userQuery) {
                      $userQuery->where('first_name', 'like', '%' . $this->searchTerm . '%')
                               ->orWhere('last_name', 'like', '%' . $this->searchTerm . '%');
                  })
                  // Search within consolidated packages
                  ->orWhereHas('consolidatedPackage', function ($consolidatedQuery) {
                      $consolidatedQuery->where('consolidated_tracking_number', 'like', '%' . $this->searchTerm . '%')
                                       ->orWhere('notes', 'like', '%' . $this->searchTerm . '%');
                  });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    /**
     * Get consolidated packages for the current manifest
     */
    public function getConsolidatedPackagesProperty()
    {
        return ConsolidatedPackage::whereHas('packages', function ($query) {
                $query->where('manifest_id', $this->manifest_id);
            })
            ->with(['packages' => function ($query) {
                $query->where('manifest_id', $this->manifest_id);
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
        $individualPackages = Package::where('manifest_id', $this->manifest_id)
            ->individual()
            ->get();

        $consolidatedPackages = $this->consolidatedPackages;

        $totals = [
            'individual_packages' => $individualPackages->count(),
            'consolidated_packages' => $consolidatedPackages->count(),
            'total_packages_in_consolidated' => $consolidatedPackages->sum('total_quantity'),
            'total_weight' => $individualPackages->sum('weight') + $consolidatedPackages->sum('total_weight'),
            'total_freight_price' => $individualPackages->sum('freight_price') + $consolidatedPackages->sum('total_freight_price'),
            'total_clearance_fee' => $individualPackages->sum('clearance_fee') + $consolidatedPackages->sum('total_clearance_fee'),
            'total_storage_fee' => $individualPackages->sum('storage_fee') + $consolidatedPackages->sum('total_storage_fee'),
            'total_delivery_fee' => $individualPackages->sum('delivery_fee') + $consolidatedPackages->sum('total_delivery_fee'),
        ];

        $totals['total_cost'] = $totals['total_freight_price'] + 
                               $totals['total_clearance_fee'] + 
                               $totals['total_storage_fee'] + 
                               $totals['total_delivery_fee'];

        return $totals;
    }



    /**
     * Update bulk actions visibility
     */
    private function updateBulkActionsVisibility()
    {
        $this->showBulkActions = count($this->selectedPackages) > 0;
    }

    /**
     * Update select all state based on selected packages
     */
    private function updateSelectAllState()
    {
        $totalPackages = $this->packages->count();
        $selectedCount = count($this->selectedPackages);
        
        $this->selectAll = $totalPackages > 0 && $selectedCount === $totalPackages;
    }

    /**
     * Clear all selections
     */
    public function clearSelections()
    {
        $this->selectedPackages = [];
        $this->selectAll = false;
        $this->showBulkActions = false;
    }

    /**
     * Debug method to check selection state
     */
    public function debugSelection()
    {
        \Log::info('Selection Debug', [
            'selectedPackages' => $this->selectedPackages,
            'selectAll' => $this->selectAll,
            'showBulkActions' => $this->showBulkActions,
            'packageIds' => $this->packages->pluck('id')->toArray()
        ]);
    }

    /**
     * Show bulk status update modal
     */
    public function showBulkStatusUpdate()
    {
        if (!$this->canEditManifest) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Cannot update package status on a closed manifest.',
            ]);
            return;
        }

        if (empty($this->selectedPackages)) {
            $this->dispatchBrowserEvent('toastr:warning', [
                'message' => 'Please select packages to update.',
            ]);
            return;
        }

        $this->showStatusUpdateModal = true;
        $this->confirmationMessage = 'Update status for ' . count($this->selectedPackages) . ' selected package(s)? Email notifications will be sent to customers automatically.';
    }

    /**
     * Cancel status update
     */
    public function cancelStatusUpdate()
    {
        $this->showStatusUpdateModal = false;
        $this->bulkStatus = '';
        $this->confirmationMessage = '';
    }

    /**
     * Confirm bulk status update
     */
    public function confirmBulkStatusUpdate()
    {
        if (!$this->canEditManifest) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Cannot update package status on a closed manifest.',
            ]);
            return;
        }

        if (empty($this->bulkStatus)) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Please select a status to update to.',
            ]);
            return;
        }

        // Prevent manual updates to DELIVERED status
        if ($this->bulkStatus === PackageStatus::DELIVERED) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Packages can only be marked as delivered through the distribution process.',
            ]);
            return;
        }

        $successCount = 0;
        $errorCount = 0;
        $packageStatusService = $this->getPackageStatusService();
        $consolidationService = app(\App\Services\PackageConsolidationService::class);

        foreach ($this->selectedPackages as $packageId) {
            try {
                $package = Package::find($packageId);
                if ($package) {
                    // Check if package is consolidated
                    if ($package->isConsolidated()) {
                        // Update the entire consolidated package status
                        $consolidatedPackage = $package->consolidatedPackage;
                        $result = $consolidationService->updateConsolidatedStatus(
                            $consolidatedPackage,
                            $this->bulkStatus,
                            auth()->user(),
                            ['reason' => 'Bulk status update from manifest package view']
                        );

                        if ($result['success']) {
                            $successCount++;
                        } else {
                            $errorCount++;
                        }
                    } else {
                        // Update individual package status
                        $result = $packageStatusService->updateStatus(
                            $package,
                            PackageStatus::from($this->bulkStatus),
                            auth()->user(),
                            'Bulk status update from manifest package view'
                        );

                        if ($result) {
                            $successCount++;
                        } else {
                            $errorCount++;
                        }
                    }
                } else {
                    $errorCount++;
                    Log::warning('Package not found during bulk status update', [
                        'package_id' => $packageId,
                        'status' => $this->bulkStatus,
                    ]);
                }
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Failed to update package status', [
                    'package_id' => $packageId,
                    'status' => $this->bulkStatus,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Show results
        if ($successCount > 0) {
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => "Successfully updated {$successCount} package(s). Email notifications have been sent to customers.",
            ]);
        }

        if ($errorCount > 0) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => "Failed to update {$errorCount} package(s).",
            ]);
        }

        // Reset state
        $this->cancelStatusUpdate();
        $this->clearSelections();
    }

    /**
     * Update consolidated package status
     */
    public function updateConsolidatedPackageStatus($consolidatedPackageId, $newStatus)
    {
        if (!$this->canEditManifest) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Cannot update package status on a closed manifest.',
            ]);
            return;
        }

        try {
            $consolidatedPackage = ConsolidatedPackage::findOrFail($consolidatedPackageId);
            
            // Check if transitioning to READY status - show fee modal for packages that need fees
            if ($newStatus === PackageStatus::READY) {
                $this->showConsolidatedFeeEntryModal($consolidatedPackageId);
                return;
            }
            
            $consolidationService = app(\App\Services\PackageConsolidationService::class);

            $result = $consolidationService->updateConsolidatedStatus(
                $consolidatedPackage,
                $newStatus,
                auth()->user(),
                ['reason' => 'Status update from manifest view']
            );

            if ($result['success']) {
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => "Consolidated package status updated successfully.",
                ]);
            } else {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => $result['message'],
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to update consolidated package status.',
            ]);
            Log::error('Failed to update consolidated package status', [
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
        if (!$this->canEditManifest) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Cannot consolidate packages on a closed manifest.',
            ]);
            return;
        }

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
        $consolidationService = app(\App\Services\PackageConsolidationService::class);
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
        if (!$this->canEditManifest) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Cannot consolidate packages on a closed manifest.',
            ]);
            return;
        }

        if (empty($this->selectedPackages) || count($this->selectedPackages) < 2) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Please select at least 2 packages to consolidate.',
            ]);
            return;
        }

        try {
            $consolidationService = app(\App\Services\PackageConsolidationService::class);
            $result = $consolidationService->consolidatePackages(
                $this->selectedPackages,
                auth()->user(),
                ['notes' => $this->consolidationNotes]
            );

            if ($result['success']) {
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => 'Packages consolidated successfully! Consolidated tracking number: ' . $result['consolidated_package']->consolidated_tracking_number,
                ]);

                // Reset state
                $this->cancelConsolidation();
                $this->clearSelections();
            } else {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => $result['message'],
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to consolidate packages. Please try again.',
            ]);
            Log::error('Failed to consolidate packages from manifest view', [
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
        if (!$this->canEditManifest) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Cannot unconsolidate packages on a closed manifest.',
            ]);
            return;
        }

        try {
            $consolidatedPackage = ConsolidatedPackage::findOrFail($consolidatedPackageId);
            $consolidationService = app(\App\Services\PackageConsolidationService::class);

            $result = $consolidationService->unconsolidatePackages(
                $consolidatedPackage,
                auth()->user(),
                ['notes' => 'Unconsolidated from manifest view']
            );

            if ($result['success']) {
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => 'Packages unconsolidated successfully.',
                ]);
            } else {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => $result['message'],
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to unconsolidate packages.',
            ]);
            Log::error('Failed to unconsolidate packages from manifest view', [
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

    /**
     * Get ready packages for distribution
     */
    public function getReadyPackagesProperty()
    {
        return Package::where('manifest_id', $this->manifest_id)
            ->where('status', PackageStatus::READY)
            ->with(['user.profile'])
            ->get();
    }

    /**
     * Navigate to distribution page
     */
    public function goToDistribution()
    {
        $readyPackagesCount = $this->readyPackages->count();
        
        if ($readyPackagesCount === 0) {
            $this->dispatchBrowserEvent('toastr:warning', [
                'message' => 'No packages are ready for distribution.',
            ]);
            return;
        }

        return redirect()->route('admin.manifests.distribution', ['manifest' => $this->manifest_id]);
    }

    /**
     * Navigate to workflow page
     */
    public function goToWorkflow()
    {
        return redirect()->route('admin.manifests.workflow', ['manifest' => $this->manifest_id]);
    }

    /**
     * Get available status options for bulk update
     * Excludes 'DELIVERED' status as it can only be set through distribution process
     */
    public function getStatusOptionsProperty()
    {
        return collect(PackageStatus::manualUpdateCases())->map(function ($status) {
            return [
                'value' => $status->value,
                'label' => $status->getLabel(),
                'badge_class' => $status->getBadgeClass()
            ];
        });
    }

    /**
     * Reset filters
     */
    public function resetFilters()
    {
        $this->statusFilter = '';
        $this->searchTerm = '';
        $this->resetPage();
    }

    /**
     * Updated search term
     */
    public function updatedSearchTerm()
    {
        $this->resetPage();
    }

    /**
     * Updated status filter
     */
    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    /**
     * Toggle package selection
     */
    public function togglePackageSelection($packageId)
    {
        // Ensure packageId is an integer
        $packageId = (int) $packageId;
        
        // Ensure selectedPackages is an array of integers
        $this->selectedPackages = array_map('intval', $this->selectedPackages);
        
        if (in_array($packageId, $this->selectedPackages)) {
            // Remove the package ID and reindex the array
            $this->selectedPackages = array_values(array_diff($this->selectedPackages, [$packageId]));
        } else {
            // Add the package ID if it's not already selected
            if (!in_array($packageId, $this->selectedPackages)) {
                $this->selectedPackages[] = $packageId;
            }
        }

        $this->updateBulkActionsVisibility();
        $this->updateSelectAllState();
    }

    /**
     * Toggle select all packages
     */
    public function toggleSelectAll()
    {
        // Toggle the selectAll state first
        $this->selectAll = !$this->selectAll;
        
        if ($this->selectAll) {
            // Select all packages on current page
            $this->selectedPackages = $this->packages->pluck('id')->map(function($id) {
                return (int) $id;
            })->toArray();
        } else {
            // Clear all selections
            $this->selectedPackages = [];
        }

        $this->updateBulkActionsVisibility();
    }

    /**
     * Store a new package with support for both air and sea manifests
     *
     * @return void
     */
    public function store()
    {
        // Base validation rules for all packages
        $rules = [
            'user_id' => ['required', 'integer'],
            'shipper_id' => ['required', 'integer'],
            'office_id' => ['required', 'integer'],
            'tracking_number' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'weight' => ['required', 'numeric'],
            'estimated_value' => ['required', 'numeric'],
        ];

        // Add sea-specific validation rules
        if ($this->isSeaManifest()) {
            $rules = array_merge($rules, [
                'container_type' => ['required', 'in:box,barrel,pallet'],
                'length_inches' => ['required', 'numeric', 'min:0.1', 'max:1000', new ValidContainerDimensions($this->isSeaManifest(), 'length_inches')],
                'width_inches' => ['required', 'numeric', 'min:0.1', 'max:1000', new ValidContainerDimensions($this->isSeaManifest(), 'width_inches')],
                'height_inches' => ['required', 'numeric', 'min:0.1', 'max:1000', new ValidContainerDimensions($this->isSeaManifest(), 'height_inches')],
                'items' => ['required', 'array', 'min:1', new ValidPackageItems($this->isSeaManifest())],
                'items.*.description' => ['required', 'string', 'max:255', 'min:2'],
                'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
                'items.*.weight_per_item' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            ]);
        }

        // Validate all fields first
        $this->validate($rules, $this->getValidationMessages());

        // Additional sea-specific validation after basic validation passes
        if ($this->isSeaManifest()) {
            // Ensure cubic feet is calculated for sea packages
            $this->calculateCubicFeet();
            
            // Validate that cubic feet is greater than 0
            if ($this->cubic_feet <= 0) {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => 'Invalid dimensions. Please check length, width, and height values. Cubic feet must be greater than 0.',
                ]);
                return;
            }

            // Validate that cubic feet is reasonable (not too large)
            if ($this->cubic_feet > 10000) {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => 'Container dimensions are too large. Maximum cubic feet allowed is 10,000.',
                ]);
                return;
            }
        }

        // Check if a package already exists for the tracking number
        $existingPackage = Package::where('tracking_number', $this->tracking_number)->first();
        if ($existingPackage) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Tracking number already exists in Packages.',
            ]);
            return;
        }

        // Check if a pre-alert exists for the tracking number
        $preAlert = PreAlert::where('tracking_number', $this->tracking_number)->first();

        // Prepare base package data
        $packageData = [
            'user_id' => $this->user_id,
            'shipper_id' => $this->shipper_id,
            'office_id' => $this->office_id,
            'warehouse_receipt_no' => $this->generateWarehouseReceiptNumber(),
            'tracking_number' => $this->tracking_number,
            'description' => $this->description,
            'weight' => $this->weight,
            'status' => $this->updatePackageStatus($preAlert),
            'estimated_value' => $this->estimated_value,
            'manifest_id' => $this->manifest_id,
        ];

        // Add sea-specific fields if this is a sea manifest
        if ($this->isSeaManifest()) {
            $packageData = array_merge($packageData, [
                'container_type' => $this->container_type,
                'length_inches' => $this->length_inches,
                'width_inches' => $this->width_inches,
                'height_inches' => $this->height_inches,
                'cubic_feet' => $this->cubic_feet,
            ]);
        }

        // Create the package first (without freight_price to avoid circular dependency)
        $package = Package::create($packageData);

        if (!$package) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Package Creation Failed.',
            ]);
            return;
        }

        // Create package items for sea manifests
        if ($this->isSeaManifest()) {
            foreach ($this->items as $item) {
                if (!empty($item['description'])) {
                    PackageItem::create([
                        'package_id' => $package->id,
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'weight_per_item' => $item['weight_per_item'] ?: null,
                    ]);
                }
            }
        }

        // Calculate and update freight price after package and items are created
        try {
            $freightPrice = $this->calculateFreightPrice($package);
            $package->update(['freight_price' => $freightPrice]);
        } catch (SeaRateNotFoundException $e) {
            // Log the error but don't fail the package creation
            Log::error('Sea rate not found for package ' . $package->id . ': ' . $e->getMessage());
            
            $this->dispatchBrowserEvent('toastr:warning', [
                'message' => $e->getUserMessage(),
            ]);
        } catch (\Exception $e) {
            // Log the error but don't fail the package creation
            Log::error('Failed to calculate freight price for package ' . $package->id . ': ' . $e->getMessage());
            
            $this->dispatchBrowserEvent('toastr:warning', [
                'message' => 'Package created but freight price calculation failed. Please contact support for assistance.',
            ]);
        }

        // Create the package pre-alert if it doesn't exist
        // and associate it with the package
        if ($preAlert == null) {
            $pre_alert = PreAlert::create([
                'user_id' => $this->user_id,
                'shipper_id' => $this->shipper_id,
                'tracking_number' => $this->tracking_number,
                'description' => $this->description,
                'value' => $this->estimated_value,
                'file_path' => 'Not available',
            ]);

            $packagePreAlert = PackagePreAlert::create([
                'user_id' => $this->user_id,
                'package_id' => $package->id,
                'pre_alert_id' => $pre_alert->id,
                'status' => 'pending',
            ]);

            // If no pre-alert exists, send notification email to the customer
            $this->sendMissingPreAlertNotification($pre_alert);

            if ($packagePreAlert) {
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => 'Package Pre-Alert Created Successfully.',
                ]);
            } else {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => 'Package Pre-Alert Creation Failed.',
                ]);
            }
        }

        $this->dispatchBrowserEvent('toastr:success', [
            'message' => 'Package Created Successfully.',
        ]);

        $this->closeModal();
        $this->resetInputFields();
    }

    /**
     * Generate a unique warehouse receipt number with 'SHS' prefix.
     * Starts at 0001 and increments by 1, ensuring no collision with existing numbers.
     *
     * @return string
     */
    private function generateWarehouseReceiptNumber(): string
    {
        // Find the highest existing receipt number
        $highestReceiptNumber = Package::where('warehouse_receipt_no', 'like', 'SHS%')
            ->get()
            ->map(function ($package) {
                // Extract the numeric part from the warehouse_receipt_no
                $numericPart = (int) substr($package->warehouse_receipt_no, 3);
                return $numericPart;
            })
            ->max();

        // Start with 1 or increment the highest by 1
        $nextNumber = $highestReceiptNumber ? $highestReceiptNumber + 1 : 1;

        // Format to ensure 4 digits with leading zeros
        $formattedNumber = sprintf('SHS%04d', $nextNumber);

        return $formattedNumber;
    }

    /**
     * Determine package status based on Pre-Alerts and notify customer if needed.
     * 
     * @return string The package status using normalized PackageStatus enum
     */
    private function updatePackageStatus($preAlert): string
    {
        $status = $preAlert ? PackageStatus::PROCESSING : PackageStatus::PENDING;
        return $status;
    }

    /**
     * Send notification email to customer about missing pre-alert.
     *
     * @return void
     */
    private function sendMissingPreAlertNotification($preAlert): void
    {
        // Get the user/customer associated with this package
        $user = User::find($this->user_id);

        // Send the email notification
        $user->notify(new MissingPreAlertNotification($user, $this->tracking_number, $this->description, $preAlert));

        // Optionally log that the notification was sent
        Log::info("Missing pre-alert notification sent for tracking number: {$this->tracking_number}");
    }

    /**
     * Calculate the freight price based on manifest type (air vs sea)
     *
     * @param Package|null $package Optional package instance for sea calculations
     * @return float The calculated freight price
     */
    public function calculateFreightPrice(?Package $package = null): float
    {
        if ($this->isSeaManifest()) {
            return $this->calculateSeaFreightPrice($package);
        } else {
            return $this->calculateAirFreightPrice();
        }
    }

    /**
     * Calculate freight price for air packages (existing logic)
     *
     * @return float The calculated freight price
     */
    private function calculateAirFreightPrice(): float
    {
        // Get the exchange rate for the manifest
        $xrt = Manifest::find($this->manifest_id)->exchange_rate ?: 1;

        // Get the weight of the package (rounded up)
        $weight = ceil($this->weight);

        // Get the rate for the weight using the proper scope
        $rate = Rate::forAirShipment($weight)->first();
        if ($rate) {
            // Calculate the freight price
            $freightPrice = ($rate->price + $rate->processing_fee) * $xrt;
            return $freightPrice;
        }

        // Fallback: try to find any air rate that matches the weight (for backward compatibility)
        $fallbackRate = Rate::where('weight', $weight)->first();
        if ($fallbackRate) {
            $freightPrice = ($fallbackRate->price + $fallbackRate->processing_fee) * $xrt;
            return $freightPrice;
        }

        return 0;
    }

    /**
     * Calculate freight price for sea packages using SeaRateCalculator
     *
     * @param Package|null $package Package instance for calculation
     * @return float The calculated freight price
     */
    private function calculateSeaFreightPrice(?Package $package = null): float
    {
        // If no package provided, we can't calculate sea freight price yet
        if (!$package) {
            return 0;
        }

        try {
            $seaRateCalculator = new SeaRateCalculator();
            return $seaRateCalculator->calculateFreightPrice($package);
        } catch (SeaRateNotFoundException $e) {
            // Re-throw the specific exception to be handled by caller
            throw $e;
        } catch (\Exception $e) {
            // Log the error and return 0
            Log::error('Sea freight price calculation failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get custom validation messages
     */
    protected function getValidationMessages(): array
    {
        return [
            // Base package validation messages
            'user_id.required' => 'Please select a customer.',
            'user_id.integer' => 'Invalid customer selection.',
            'shipper_id.required' => 'Please select a shipper.',
            'shipper_id.integer' => 'Invalid shipper selection.',
            'office_id.required' => 'Please select an office.',
            'office_id.integer' => 'Invalid office selection.',
            'tracking_number.required' => 'Tracking number is required.',
            'tracking_number.max' => 'Tracking number cannot exceed 255 characters.',
            'description.required' => 'Package description is required.',
            'description.min' => 'Package description must be at least 2 characters.',
            'description.max' => 'Package description cannot exceed 255 characters.',
            'weight.required' => 'Package weight is required.',
            'weight.numeric' => 'Package weight must be a valid number.',
            'estimated_value.required' => 'Estimated value is required.',
            'estimated_value.numeric' => 'Estimated value must be a valid number.',
            
            // Sea package specific validation messages
            'container_type.required' => 'Container type is required for sea packages.',
            'container_type.in' => 'Container type must be box, barrel, or pallet.',
            'length_inches.required' => 'Container length is required for sea packages.',
            'length_inches.numeric' => 'Container length must be a valid number.',
            'length_inches.min' => 'Container length must be at least 0.1 inches.',
            'length_inches.max' => 'Container length cannot exceed 1000 inches.',
            'width_inches.required' => 'Container width is required for sea packages.',
            'width_inches.numeric' => 'Container width must be a valid number.',
            'width_inches.min' => 'Container width must be at least 0.1 inches.',
            'width_inches.max' => 'Container width cannot exceed 1000 inches.',
            'height_inches.required' => 'Container height is required for sea packages.',
            'height_inches.numeric' => 'Container height must be a valid number.',
            'height_inches.min' => 'Container height must be at least 0.1 inches.',
            'height_inches.max' => 'Container height cannot exceed 1000 inches.',
            
            // Package items validation messages
            'items.required' => 'At least one item is required for sea packages.',
            'items.array' => 'Items must be provided as a list.',
            'items.min' => 'At least one item is required for sea packages.',
            'items.*.description.required' => 'Item description is required.',
            'items.*.description.string' => 'Item description must be text.',
            'items.*.description.min' => 'Item description must be at least 2 characters.',
            'items.*.description.max' => 'Item description cannot exceed 255 characters.',
            'items.*.quantity.required' => 'Item quantity is required.',
            'items.*.quantity.integer' => 'Item quantity must be a whole number.',
            'items.*.quantity.min' => 'Item quantity must be at least 1.',
            'items.*.quantity.max' => 'Item quantity cannot exceed 10,000.',
            'items.*.weight_per_item.numeric' => 'Weight per item must be a valid number.',
            'items.*.weight_per_item.min' => 'Weight per item cannot be negative.',
            'items.*.weight_per_item.max' => 'Weight per item cannot exceed 1000 lbs.',
        ];
    }

    /**
     * Show consolidated fee entry modal for transitioning to ready status
     */
    public function showConsolidatedFeeEntryModal($consolidatedPackageId)
    {
        $this->feeConsolidatedPackageId = $consolidatedPackageId;
        $this->feeConsolidatedPackage = ConsolidatedPackage::with(['packages.user.profile'])->findOrFail($consolidatedPackageId);
        
        // Check which packages in the consolidation need fee entry
        $this->consolidatedPackagesNeedingFees = [];
        foreach ($this->feeConsolidatedPackage->packages as $package) {
            // Always include all packages for fee review when transitioning to ready
            $this->consolidatedPackagesNeedingFees[] = [
                'id' => $package->id,
                'tracking_number' => $package->tracking_number,
                'description' => $package->description,
                'clearance_fee' => $package->clearance_fee ?? 0,
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
        return ($package->clearance_fee ?? 0) == 0 || 
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
            $consolidatedPackage = ConsolidatedPackage::findOrFail($this->feeConsolidatedPackageId);
            
            // Update fees for each package in the consolidation
            foreach ($this->consolidatedPackagesNeedingFees as $packageData) {
                $package = Package::findOrFail($packageData['id']);
                
                $package->update([
                    'clearance_fee' => $packageData['clearance_fee'] ?? 0,
                    'storage_fee' => $packageData['storage_fee'] ?? 0,
                    'delivery_fee' => $packageData['delivery_fee'] ?? 0,
                ]);
            }
            
            // Update consolidated package status to ready
            $consolidationService = app(\App\Services\PackageConsolidationService::class);
            $result = $consolidationService->updateConsolidatedStatus(
                $consolidatedPackage,
                PackageStatus::READY,
                auth()->user(),
                ['reason' => 'Status update to ready after fee entry']
            );

            if ($result['success']) {
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => 'Consolidated package fees updated and status set to ready successfully!'
                ]);
                
                $this->closeConsolidatedFeeModal();
            } else {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => 'Failed to update consolidated package status: ' . ($result['message'] ?? 'Unknown error')
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error updating consolidated package fees: ' . $e->getMessage());
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'An error occurred while updating consolidated package fees.'
            ]);
            $this->closeConsolidatedFeeModal();
        }
    }

    /**
     * Get the manifest instance
     */
    public function getManifestProperty()
    {
        return Manifest::find($this->manifest_id);
    }

    /**
     * Check if the current user can edit the manifest
     */
    public function getCanEditManifestProperty(): bool
    {
        $manifest = $this->manifest;
        $user = auth()->user();
        
        if (!$manifest || !$user) {
            return false;
        }

        $lockService = $this->getManifestLockService();
        return $lockService->canEdit($manifest, $user);
    }

    /**
     * Refresh manifest data when unlocked
     */
    public function refreshManifestData()
    {
        // Clear any cached manifest data
        unset($this->manifest);
        
        // Refresh the component to show updated editing capabilities
        $this->emit('$refresh');
    }

    public function render()
    {
        $manifest = Manifest::find($this->manifest_id);
        
        return view('livewire.manifests.packages.manifest-package', [
            'manifest' => $manifest,
            'canEdit' => $this->canEditManifest
        ]);
    }
}
