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
use Illuminate\Support\Facades\Log;
use App\Notifications\MissingPreAlertNotification;
use App\Services\SeaRateCalculator;
use App\Rules\ValidContainerDimensions;
use App\Rules\ValidPackageItems;
use App\Exceptions\SeaRateNotFoundException;

class ManifestPackage extends Component
{
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

    // Sea-specific properties
    public string $container_type = '';
    public string $length_inches = '';
    public string $width_inches = '';
    public string $height_inches = '';
    public float $cubic_feet = 0;
    public array $items = [];


    public function mount()
    {
        $this->customerList = User::where('role_id', 3)
                                  ->where('email_verified_at', '!=', '')
                                  ->orderBy('last_name', 'asc')->get();

        $this->officeList = Office::orderBy('name', 'asc')->get();

        $this->shipperList = Shipper::orderBy('name', 'asc')->get();

        // Get manifest_id from route if not already set (for testing)
        if (!$this->manifest_id) {
            $this->manifest_id = request()->route('manifest_id');
        }

        $manifest = Manifest::find($this->manifest_id);
        $this->isSeaManifest = $manifest && $manifest->type === 'sea';
        
        // Initialize items array for sea manifests
        $this->initializeItems();
    }

    public function create()
    {
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
        $manifest = Manifest::find($this->manifest_id);
        $this->isSeaManifest = $manifest && $manifest->type === 'sea';
        $this->initializeItems();
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
     * @return string The package status ('Processing' or 'Pending')
     */
    private function updatePackageStatus($preAlert): string
    {
        // $preAlert = PreAlert::where('tracking_number', $this->tracking_number)->first();
        $status = $preAlert ? 'processing' : 'pending';

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

    public function render()
    {
        return view('livewire.manifests.packages.manifest-package');
    }
}
