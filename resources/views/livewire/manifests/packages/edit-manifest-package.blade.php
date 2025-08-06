<div>
    <!-- <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Update Package
        </h3>
    </div> -->

    <div class="mt-1 py-4">
        <!-- Package Details Card -->
        <div class="w-full bg-white rounded-lg shadow h-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-auto">
                        <h1 class="text-lg font-bold text-gray-900 flex items-center">
                            @if($isSeaManifest)
                                <x-sea class="h-8 w-auto mr-2 text-wax-flower-600 flex-shrink-0" />
                                <span>Update sea package details</span>
                            @else
                                <x-air class="h-8 w-auto mr-2 text-wax-flower-600 flex-shrink-0" />
                                <span>Update air package details</span>
                            @endif
                        </h1>
                        <p class="mt-2 text-sm text-gray-700">You will only be able to update this package while it's still open.</p>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="bg-white px-4 pt-5 pb-4">
                        <div class="text-left">
                            <div class="mt-6 mb-5">
                                <label for="customerSearch" class="block text-gray-700 text-sm font-bold mb-2">Select customer</label>
                                <div class="relative mt-1">
                                    <div class="flex">
                                        <input 
                                            type="text" 
                                            wire:model.debounce.300ms="customerSearch"
                                            wire:focus="showAllCustomers"
                                            id="customerSearch" 
                                            placeholder="Search by name or account number..." 
                                            autocomplete="off"
                                            class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-l-md placeholder-gray-400 focus:outline-none focus:ring-blue focus:border-blue-300 transition duration-150 ease-in-out sm:text-sm sm:leading-5 @error('user_id') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror"
                                        >
                                        @if($user_id > 0)
                                            <button 
                                                type="button" 
                                                wire:click="clearCustomerSelection"
                                                class="px-3 py-2 border border-l-0 border-gray-300 rounded-r-md bg-gray-50 hover:bg-gray-100 focus:outline-none focus:ring-blue focus:border-blue-300 transition duration-150 ease-in-out"
                                                title="Clear selection"
                                            >
                                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        @else
                                            <div class="px-3 py-2 border border-l-0 border-gray-300 rounded-r-md bg-gray-50">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <!-- Customer Dropdown Results -->
                                    @if($showCustomerDropdown)
                                        <div class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
                                            @if($filteredCustomers->count() > 0)
                                                @foreach($filteredCustomers as $customer)
                                                    <div 
                                                        wire:click="selectCustomer({{ $customer->id }})"
                                                        class="px-3 py-2 cursor-pointer hover:bg-blue-50 hover:text-blue-900 border-b border-gray-100 last:border-b-0"
                                                    >
                                                        <div class="font-medium">{{ $customer->full_name }}</div>
                                                        <div class="text-sm text-gray-500">Account: {{ $customer->profile->account_number ?? 'N/A' }}</div>
                                                    </div>
                                                @endforeach
                                                @if($filteredCustomers->count() == 10)
                                                    <div class="px-3 py-2 text-sm text-gray-500 bg-gray-50 text-center">
                                                        Showing first 10 results. Type more to narrow search.
                                                    </div>
                                                @endif
                                            @else
                                                <div class="px-3 py-2 text-sm text-gray-500 text-center">
                                                    No customers found matching "{{ $customerSearch }}"
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                
                                @if($user_id > 0 && $selectedCustomerDisplay)
                                    <div class="mt-2 text-sm text-green-600 bg-green-50 px-2 py-1 rounded">
                                        ✓ Selected: {{ $selectedCustomerDisplay }}
                                    </div>
                                @endif
                                
                                @error('user_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Shipper and Destination Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 mb-5">
                                <div>
                                    <label for="shipper_id" class="block text-gray-700 text-sm font-bold mb-2">Select the shipper (carrier)</label>
                                    <div class="mt-1 rounded-md shadow-sm">
                                        <select wire:model.lazy="shipper_id" id="shipper_id" required autofocus class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue focus:border-blue-300 transition duration-150 ease-in-out sm:text-sm sm:leading-5 @error('shipper_id') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror">
                                            <option value="" selected>--- Select shipper ---</option>
                                            @foreach($shipperList as $shipper)
                                            <option value="{{ $shipper->id }}">{{ $shipper->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('shipper_id')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="office_id" class="block text-gray-700 text-sm font-bold mb-2">Select destination</label>
                                    <div class="mt-1 rounded-md shadow-sm">
                                        <select wire:model.lazy="office_id" id="office_id" required autofocus class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue focus:border-blue-300 transition duration-150 ease-in-out sm:text-sm sm:leading-5 @error('office_id') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror">
                                            <option value="" selected>--- Select location ---</option>
                                            @foreach($officeList as $office)
                                            <option value="{{ $office->id }}">{{ $office->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('office_id')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Tracking Number and Weight Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="tracking_number" class="block text-gray-700 text-sm font-bold mb-2">Tracking Number</label>
                                    <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="tracking_number" placeholder="Enter tracking number for the item" wire:model="tracking_number" autocomplete="off">
                                    @error('tracking_number') <span class="text-red-500">{{ $message }}</span>@enderror
                                </div>

                                <div>
                                    <label for="weight" class="block text-gray-700 text-sm font-bold mb-2">Weight (lbs)</label>
                                    <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="weight" placeholder="Enter weight for the item" wire:model="weight" autocomplete="off">
                                    @error('weight') <span class="text-red-500">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="description" placeholder="Briefly describe the item" wire:model="description" autocomplete="off" rows="4"></textarea>
                                @error('description') <span class="text-red-500">{{ $message }}</span>@enderror
                            </div>

                            <div class="mb-4">
                                <label for="estimated_value" class="block text-gray-700 text-sm font-bold mb-2">Estimated Value (USD)</label>
                                <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="estimated_value" placeholder="Enter the estimated value for the item" wire:model="estimated_value" autocomplete="off">
                                @error('estimated_value') <span class="text-red-500">{{ $message }}</span>@enderror
                            </div>

                            @if($isSeaManifest)
                              <!-- Container Type Selection -->
                              <div class="mb-4">
                                <label for="container_type" class="block text-gray-700 text-sm font-bold mb-2">Container Type</label>
                                <select wire:model="container_type" id="container_type" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('container_type') border-red-300 text-red-900 @enderror">
                                  <option value="">--- Select Container Type ---</option>
                                  <option value="box">Box</option>
                                  <option value="barrel">Barrel</option>
                                  <option value="pallet">Pallet</option>
                                </select>
                                @error('container_type') <span class="text-red-500">{{ $message }}</span>@enderror
                              </div>

                              <!-- Dimensional Fields -->
                              <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Container Dimensions (inches)</label>
                                <div class="grid grid-cols-3 gap-3">
                                  <div>
                                    <label for="length_inches" class="block text-gray-600 text-xs mb-1">Length</label>
                                    <input type="number" step="0.1" min="0.1" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('length_inches') border-red-300 @enderror" id="length_inches" placeholder="L" wire:model="length_inches" wire:input="calculateCubicFeet" autocomplete="off">
                                    @error('length_inches') <span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                                  </div>
                                  <div>
                                    <label for="width_inches" class="block text-gray-600 text-xs mb-1">Width</label>
                                    <input type="number" step="0.1" min="0.1" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('width_inches') border-red-300 @enderror" id="width_inches" placeholder="W" wire:model="width_inches" wire:input="calculateCubicFeet" autocomplete="off">
                                    @error('width_inches') <span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                                  </div>
                                  <div>
                                    <label for="height_inches" class="block text-gray-600 text-xs mb-1">Height</label>
                                    <input type="number" step="0.1" min="0.1" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('height_inches') border-red-300 @enderror" id="height_inches" placeholder="H" wire:model="height_inches" wire:input="calculateCubicFeet" autocomplete="off">
                                    @error('height_inches') <span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                                  </div>
                                </div>
                              </div>

                              <!-- Real-time Cubic Feet Display -->
                              <div class="mb-4">
                                <div class="bg-blue-50 border border-blue-200 rounded p-3">
                                  <div class="flex items-center">
                                    <svg class="w-4 h-4 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-blue-800 text-sm font-medium">
                                      Calculated Volume: <span class="font-bold">{{ number_format($cubic_feet, 3) }} cubic feet</span>
                                    </span>
                                  </div>
                                  @if($cubic_feet > 0)
                                    <div class="text-blue-600 text-xs mt-1">
                                      Formula: {{ $length_inches }} × {{ $width_inches }} × {{ $height_inches }} ÷ 1728 = {{ number_format($cubic_feet, 3) }} ft³
                                    </div>
                                  @endif
                                </div>
                              </div>

                              <!-- Package Items Management -->
                              <div class="mb-4">
                                <div class="flex justify-between items-center mb-2">
                                  <label class="block text-gray-700 text-sm font-bold">Container Items</label>
                                  <button type="button" wire:click="addItem" class="bg-green-500 hover:bg-green-600 text-white text-xs px-2 py-1 rounded">
                                    + Add Item
                                  </button>
                                </div>
                                
                                @foreach($items as $index => $item)
                                  <div class="border border-gray-200 rounded p-3 mb-2 bg-gray-50">
                                    <div class="flex justify-between items-start mb-2">
                                      <span class="text-sm font-medium text-gray-700">Item {{ $index + 1 }}</span>
                                      @if(count($items) > 1)
                                        <button type="button" wire:click="removeItem({{ $index }})" class="text-red-500 hover:text-red-700 text-xs">
                                          Remove
                                        </button>
                                      @endif
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                      <div class="md:col-span-2">
                                        <label for="items.{{ $index }}.description" class="block text-gray-600 text-xs mb-1">Description *</label>
                                        <input type="text" class="shadow appearance-none border rounded w-full py-1 px-2 text-gray-700 text-sm leading-tight focus:outline-none focus:shadow-outline @error('items.' . $index . '.description') border-red-300 @enderror" wire:model="items.{{ $index }}.description" placeholder="Item description" autocomplete="off">
                                        @error('items.' . $index . '.description') <span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                                      </div>
                                      
                                      <div>
                                        <label for="items.{{ $index }}.quantity" class="block text-gray-600 text-xs mb-1">Quantity *</label>
                                        <input type="number" min="1" class="shadow appearance-none border rounded w-full py-1 px-2 text-gray-700 text-sm leading-tight focus:outline-none focus:shadow-outline @error('items.' . $index . '.quantity') border-red-300 @enderror" wire:model="items.{{ $index }}.quantity" placeholder="1" autocomplete="off">
                                        @error('items.' . $index . '.quantity') <span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                                      </div>
                                    </div>
                                    
                                    <div class="mt-2">
                                      <label for="items.{{ $index }}.weight_per_item" class="block text-gray-600 text-xs mb-1">Weight per Item (lbs) - Optional</label>
                                      <input type="number" step="0.01" min="0" class="shadow appearance-none border rounded w-full py-1 px-2 text-gray-700 text-sm leading-tight focus:outline-none focus:shadow-outline @error('items.' . $index . '.weight_per_item') border-red-300 @enderror" wire:model="items.{{ $index }}.weight_per_item" placeholder="0.00" autocomplete="off">
                                      @error('items.' . $index . '.weight_per_item') <span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                                    </div>
                                  </div>
                                @endforeach
                                
                                @error('items') <span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                              </div>
                            @endif
                        </div>
                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                            <button wire:click.prevent="update()" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-wax-flower-600 text-base font-medium text-white hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500 sm:col-start-2 sm:text-sm">
                                Update
                            </button>
                            <a href="{{ route('admin.manifests.packages', $manifest_id) }}" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sea Freight Address Card -->
        <!-- <div class="w-full bg-white rounded-lg shadow h-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-auto">
                        <h1 class="text-lg font-bold text-gray-900 flex items-center">
                            <x-sea class="h-8 w-auto mr-2 text-wax-flower-600 flex-shrink-0" />
                            <span>Preview your invoice</span>
                        </h1>
                        <p class="mt-2 text-sm text-gray-700">.</p>
                    </div>
                </div>
                <div class="mt-4">

                </div>
            </div>
        </div> -->
    </div>
</div>
@push('scr
ipts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Real-time cubic feet calculation for sea manifests
    function calculateCubicFeetRealTime() {
        const lengthInput = document.getElementById('length_inches');
        const widthInput = document.getElementById('width_inches');
        const heightInput = document.getElementById('height_inches');
        
        if (lengthInput && widthInput && heightInput) {
            [lengthInput, widthInput, heightInput].forEach(input => {
                input.addEventListener('input', function() {
                    const length = parseFloat(lengthInput.value) || 0;
                    const width = parseFloat(widthInput.value) || 0;
                    const height = parseFloat(heightInput.value) || 0;
                    
                    if (length > 0 && width > 0 && height > 0) {
                        const cubicFeet = (length * width * height) / 1728;
                        
                        // Update the display immediately for better UX
                        const volumeDisplay = document.querySelector('.bg-blue-50 .font-bold');
                        const formulaDisplay = document.querySelector('.text-blue-600.text-xs');
                        
                        if (volumeDisplay) {
                            volumeDisplay.textContent = cubicFeet.toFixed(3) + ' cubic feet';
                        }
                        
                        if (formulaDisplay) {
                            formulaDisplay.textContent = `Formula: ${length} × ${width} × ${height} ÷ 1728 = ${cubicFeet.toFixed(3)} ft³`;
                        }
                    } else {
                        // Reset display when values are invalid
                        const volumeDisplay = document.querySelector('.bg-blue-50 .font-bold');
                        const formulaDisplay = document.querySelector('.text-blue-600.text-xs');
                        
                        if (volumeDisplay) {
                            volumeDisplay.textContent = '0.000 cubic feet';
                        }
                        
                        if (formulaDisplay) {
                            formulaDisplay.textContent = '';
                        }
                    }
                });
            });
        }
    }
    
    // Customer search dropdown functionality
    function initializeCustomerSearch() {
        const searchInput = document.getElementById('customerSearch');
        
        if (searchInput) {
            // Handle keyboard navigation
            searchInput.addEventListener('keydown', function(e) {
                const dropdown = document.querySelector('.absolute.z-50');
                if (!dropdown) return;
                
                const items = dropdown.querySelectorAll('[wire\\:click*="selectCustomer"]');
                let currentIndex = -1;
                
                // Find currently highlighted item
                items.forEach((item, index) => {
                    if (item.classList.contains('bg-blue-100')) {
                        currentIndex = index;
                    }
                });
                
                switch(e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        // Remove current highlight
                        if (currentIndex >= 0) {
                            items[currentIndex].classList.remove('bg-blue-100');
                        }
                        // Add highlight to next item
                        currentIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0;
                        items[currentIndex].classList.add('bg-blue-100');
                        items[currentIndex].scrollIntoView({ block: 'nearest' });
                        break;
                        
                    case 'ArrowUp':
                        e.preventDefault();
                        // Remove current highlight
                        if (currentIndex >= 0) {
                            items[currentIndex].classList.remove('bg-blue-100');
                        }
                        // Add highlight to previous item
                        currentIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
                        items[currentIndex].classList.add('bg-blue-100');
                        items[currentIndex].scrollIntoView({ block: 'nearest' });
                        break;
                        
                    case 'Enter':
                        e.preventDefault();
                        if (currentIndex >= 0) {
                            items[currentIndex].click();
                        }
                        break;
                        
                    case 'Escape':
                        e.preventDefault();
                        @this.call('hideCustomerDropdown');
                        break;
                }
            });
        }
        
        // Click outside to close dropdown
        document.addEventListener('click', function(e) {
            const searchContainer = document.querySelector('#customerSearch')?.closest('.relative');
            if (searchContainer && !searchContainer.contains(e.target)) {
                @this.call('hideCustomerDropdown');
            }
        });
    }
    
    // Initialize functions on page load
    calculateCubicFeetRealTime();
    initializeCustomerSearch();
    
    // Re-initialize when Livewire updates the DOM
    document.addEventListener('livewire:load', function() {
        calculateCubicFeetRealTime();
        initializeCustomerSearch();
    });
    
    document.addEventListener('livewire:update', function() {
        calculateCubicFeetRealTime();
        initializeCustomerSearch();
    });
});
</script>
@endpush