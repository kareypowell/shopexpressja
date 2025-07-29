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
use Illuminate\Support\Facades\Log;

class EditManifestPackage extends Component
{
    public int $user_id = 0;
    public int $manifest_id = 0;
    public int $shipper_id = 0;
    public int $office_id = 0;
    public int $package_id = 0;
    public string $warehouse_receipt_no = '';
    public string $tracking_number = '';
    public string $description = '';
    public string $weight = '';
    public string $status = '';
    public string $estimated_value = '';
    public $customerList = [];
    public $shipperList = [];
    public $officeList = [];

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
        $this->customerList = User::where('role_id', 3)
            ->where('email_verified_at', '!=', '')
            ->orderBy('last_name', 'asc')->get();

        $this->officeList = Office::orderBy('name', 'asc')->get();

        $this->shipperList = Shipper::orderBy('name', 'asc')->get();

        $this->manifest_id = request()->route('manifest_id');

        $this->package_id = request()->route('package_id');

        // Determine if this is a sea manifest
        $manifest = Manifest::find($this->manifest_id);
        $this->isSeaManifest = $manifest && $manifest->type === 'sea';

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
        ];

        // Add sea-specific validation rules
        if ($this->isSeaManifest()) {
            $rules = array_merge($rules, [
                'container_type' => ['required', 'in:box,barrel,pallet'],
                'length_inches' => ['required', 'numeric', 'min:0.1'],
                'width_inches' => ['required', 'numeric', 'min:0.1'],
                'height_inches' => ['required', 'numeric', 'min:0.1'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.description' => ['required', 'string', 'max:255'],
                'items.*.quantity' => ['required', 'integer', 'min:1'],
                'items.*.weight_per_item' => ['nullable', 'numeric', 'min:0'],
            ]);

            // Ensure cubic feet is calculated for sea packages
            $this->calculateCubicFeet();
            
            // Validate that cubic feet is greater than 0
            if ($this->cubic_feet <= 0) {
                $this->dispatchBrowserEvent('toastr:error', [
                    'message' => 'Invalid dimensions. Cubic feet must be greater than 0.',
                ]);
                return;
            }
        }

        $this->validate($rules);

        // Update the package in the database
        $package = Package::with('items')->where('id', $this->package_id)->where('manifest_id', $this->manifest_id)->first();
        if ($package) {
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
            } catch (\Exception $e) {
                // Log the error but don't fail the package update
                Log::error('Failed to calculate freight price for package ' . $package->id . ': ' . $e->getMessage());
            }
        }

        return redirect(route('manifests.packages', ['manifest_id' => $this->manifest_id]))
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
        } catch (\Exception $e) {
            // Log the error and return 0
            Log::error('Sea freight price calculation failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function render()
    {
        return view('livewire.manifests.packages.edit-manifest-package');
    }
}
