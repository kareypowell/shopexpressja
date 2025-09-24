<?php

namespace App\Http\Livewire\Manifests\Packages;

use Livewire\Component;
use App\Models\User;
use App\Models\Shipper;
use App\Models\Office;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\Rate;
use App\Models\Manifest;
use App\Services\SeaRateCalculator;
use App\Services\PackageFeeService;
use App\Rules\ValidContainerDimensions;
use App\Rules\ValidPackageItems;
use App\Exceptions\SeaRateNotFoundException;
use Illuminate\Support\Facades\Log;

class EditManifestPackage extends Component
{
    public int $user_id = 0;
    public ?int $manifest_id = null;
    public int $shipper_id = 0;
    public int $office_id = 0;
    public ?int $package_id = null;
    public string $warehouse_receipt_no = '';
    public string $tracking_number = '';
    public string $description = '';
    public string $weight = '';
    public string $status = '';
    public string $estimated_value = '';
    public float $freight_price = 0;
    public float $clearance_fee = 0;
    public float $storage_fee = 0;
    public float $delivery_fee = 0;
    public $customerList = [];
    public $shipperList = [];
    public $officeList = [];
    
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
    public $isSeaManifest = null;


    public function mount()
    {
        $this->customerList = User::customerUsers()
            ->where('email_verified_at', '!=', '')
            ->orderBy('last_name', 'asc')->get();

        $this->officeList = Office::orderBy('name', 'asc')->get();

        $this->shipperList = Shipper::orderBy('name', 'asc')->get();

        // Handle route model binding - extract IDs from model instances
        $manifestParam = request()->route('manifest');
        $this->manifest_id = $manifestParam instanceof Manifest ? $manifestParam->id : (int) $manifestParam;

        $packageParam = request()->route('package');
        $this->package_id = $packageParam instanceof Package ? $packageParam->id : (int) $packageParam;

        // Validate required parameters
        if (!$this->manifest_id || !$this->package_id) {
            abort(404, 'Invalid manifest or package ID');
        }

        // Determine if this is a sea manifest
        $manifest = Manifest::find($this->manifest_id);
        $this->isSeaManifest = $manifest && $manifest->type === 'sea';

        // Initialize filtered customers as empty
        $this->filteredCustomers = collect();

        // Load the package data
        $package = Package::with('items')->where('id', $this->package_id)->where('manifest_id', $this->manifest_id)->first();
        if ($package) {
            $this->user_id = $package->user_id;
            $this->shipper_id = $package->shipper_id;
            $this->office_id = $package->office_id;
            $this->warehouse_receipt_no = $package->warehouse_receipt_no;
            $this->tracking_number = $package->tracking_number;
            $this->description = $package->description;
            $this->weight = $package->weight;
            $this->status = $package->status;
            $this->estimated_value = $package->estimated_value;
            $this->freight_price = $package->freight_price ?? 0;
            $this->clearance_fee = $package->clearance_fee ?? 0;
            $this->storage_fee = $package->storage_fee ?? 0;
            $this->delivery_fee = $package->delivery_fee ?? 0;
            
            // Set customer search display
            $customer = User::find($this->user_id);
            if ($customer && $customer->profile) {
                $this->selectedCustomerDisplay = $customer->full_name . " (" . $customer->profile->account_number . ")";
                $this->customerSearch = $this->selectedCustomerDisplay;
            }

            // Load sea-specific data if this is a sea package
            if ($this->isSeaManifest) {
                $this->container_type = $package->container_type ?? '';
                $this->length_inches = $package->length_inches ?? '';
                $this->width_inches = $package->width_inches ?? '';
                $this->height_inches = $package->height_inches ?? '';
                $this->cubic_feet = $package->cubic_feet ?? 0;

                // Load existing package items
                $this->items = $package->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'weight_per_item' => $item->weight_per_item ?? ''
                    ];
                })->toArray();

                // Ensure at least one item exists for editing
                if (empty($this->items)) {
                    $this->items = [
                        [
                            'id' => null,
                            'description' => '',
                            'quantity' => 1,
                            'weight_per_item' => ''
                        ]
                    ];
                }
            }
        }
    }

    /**
     * Determine if the current manifest is a sea manifest
     */
    public function isSeaManifest(): bool
    {
        return $this->isSeaManifest === true;
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
            'id' => null,
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

    public function update()
    {
        // Base validation rules for all packages
        $rules = [
            'user_id' => 'required|integer',
            'shipper_id' => 'required|integer',
            'office_id' => 'required|integer',
            'warehouse_receipt_no' => 'required|string|max:255',
            'tracking_number' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'weight' => 'required|numeric',
            'status' => 'required|string|max:255',
            'estimated_value' => 'nullable|numeric',
            'freight_price' => 'nullable|numeric|min:0',
            'clearance_fee' => 'nullable|numeric|min:0',
            'storage_fee' => 'nullable|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0',
        ];

        // Add specific validation for ready status
        if ($this->status === 'ready') {
            $rules['freight_price'] = 'required|numeric|min:0';
            $rules['clearance_fee'] = 'required|numeric|min:0';
            $rules['storage_fee'] = 'required|numeric|min:0';
            $rules['delivery_fee'] = 'required|numeric|min:0';
        }

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

        // Update the package in the database
        $package = Package::with('items')->where('id', $this->package_id)->where('manifest_id', $this->manifest_id)->first();
        if ($package) {
            $oldStatus = $package->status;
            $newStatus = $this->status;
            
            // Check if status is changing to 'ready' - this requires fee entry
            if ($oldStatus !== 'ready' && $newStatus === 'ready') {
                // Validate that required fees are set (use form values, not database values)
                if (!$this->freight_price || !$this->clearance_fee || !$this->storage_fee || !$this->delivery_fee) {
                    $this->dispatchBrowserEvent('toastr:error', [
                        'message' => 'Cannot set package to ready status. Please ensure all fees (freight, clearance, storage, delivery) are properly set before marking as ready.',
                    ]);
                    return;
                }
                
                // Update package with fees first
                $package->update([
                    'freight_price' => $this->freight_price,
                    'clearance_fee' => $this->clearance_fee,
                    'storage_fee' => $this->storage_fee,
                    'delivery_fee' => $this->delivery_fee,
                ]);
                
                // Use PackageFeeService to handle the status change properly
                $packageFeeService = app(\App\Services\PackageFeeService::class);
                $user = auth()->user();
                
                try {
                    $packageFeeService->setPackageReady(
                        $package->fresh(), // Get fresh instance with updated fees
                        [
                            'freight_price' => $this->freight_price,
                            'clearance_fee' => $this->clearance_fee,
                            'storage_fee' => $this->storage_fee,
                            'delivery_fee' => $this->delivery_fee,
                        ],
                        $user
                    );
                    
                    return redirect(route('admin.manifests.packages', ['manifest' => $this->manifest_id]))
                        ->with('message', __('Package updated successfully and set to ready for pickup.'));
                } catch (\Exception $e) {
                    $this->dispatchBrowserEvent('toastr:error', [
                        'message' => 'Error setting package to ready: ' . $e->getMessage(),
                    ]);
                    return;
                }
            }
            // Prepare base package data
            $packageData = [
                'user_id' => $this->user_id,
                'shipper_id' => $this->shipper_id,
                'office_id' => $this->office_id,
                'warehouse_receipt_no' => $this->warehouse_receipt_no,
                'tracking_number' => $this->tracking_number,
                'description' => $this->description,
                'weight' => $this->weight,
                'status' => $this->status,
                'estimated_value' => $this->estimated_value,
                'freight_price' => $this->freight_price,
                'clearance_fee' => $this->clearance_fee,
                'storage_fee' => $this->storage_fee,
                'delivery_fee' => $this->delivery_fee,
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

            // Update the package
            $package->update($packageData);

            // Handle package items for sea manifests
            if ($this->isSeaManifest()) {
                // Get existing item IDs
                $existingItemIds = $package->items->pluck('id')->toArray();
                $updatedItemIds = [];

                foreach ($this->items as $item) {
                    if (!empty($item['description'])) {
                        if ($item['id']) {
                            // Update existing item
                            $packageItem = PackageItem::find($item['id']);
                            if ($packageItem) {
                                $packageItem->update([
                                    'description' => $item['description'],
                                    'quantity' => $item['quantity'],
                                    'weight_per_item' => $item['weight_per_item'] ?: null,
                                ]);
                                $updatedItemIds[] = $item['id'];
                            }
                        } else {
                            // Create new item
                            $newItem = PackageItem::create([
                                'package_id' => $package->id,
                                'description' => $item['description'],
                                'quantity' => $item['quantity'],
                                'weight_per_item' => $item['weight_per_item'] ?: null,
                            ]);
                            $updatedItemIds[] = $newItem->id;
                        }
                    }
                }

                // Delete items that were removed
                $itemsToDelete = array_diff($existingItemIds, $updatedItemIds);
                if (!empty($itemsToDelete)) {
                    PackageItem::whereIn('id', $itemsToDelete)->delete();
                }
            }

            // Calculate and update freight price after package and items are updated
            try {
                $freightPrice = $this->calculateFreightPrice($package);
                $package->update(['freight_price' => $freightPrice]);
            } catch (SeaRateNotFoundException $e) {
                // Log the error but don't fail the package update
                Log::error('Sea rate not found for package ' . $package->id . ': ' . $e->getMessage());
                
                $this->dispatchBrowserEvent('toastr:warning', [
                    'message' => $e->getUserMessage(),
                ]);
            } catch (\Exception $e) {
                // Log the error but don't fail the package update
                Log::error('Failed to calculate freight price for package ' . $package->id . ': ' . $e->getMessage());
                
                $this->dispatchBrowserEvent('toastr:warning', [
                    'message' => 'Package updated but freight price calculation failed. Please contact support for assistance.',
                ]);
            }
        }

        return redirect(route('admin.manifests.packages', ['manifest' => $this->manifest_id]))
            ->with('message', __('Package updated successfully.'));
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
        $xrt = Manifest::find($this->manifest_id)->exchange_rate;

        // Get the weight of the package (rounded up)
        $weight = ceil($this->weight);

        // Get the rate for the weight
        $rate = Rate::where('weight', $weight)->first();
        if ($rate) {
            // Calculate the freight price
            $freightPrice = ($rate->price + $rate->processing_fee) * $xrt;
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
            'warehouse_receipt_no.required' => 'Warehouse receipt number is required.',
            'warehouse_receipt_no.max' => 'Warehouse receipt number cannot exceed 255 characters.',
            'tracking_number.required' => 'Tracking number is required.',
            'tracking_number.max' => 'Tracking number cannot exceed 255 characters.',
            'description.required' => 'Package description is required.',
            'description.min' => 'Package description must be at least 2 characters.',
            'description.max' => 'Package description cannot exceed 255 characters.',
            'weight.required' => 'Package weight is required.',
            'weight.numeric' => 'Package weight must be a valid number.',
            'status.required' => 'Package status is required.',
            'status.max' => 'Package status cannot exceed 255 characters.',
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

    public function render()
    {
        return view('livewire.manifests.packages.edit-manifest-package');
    }
}
