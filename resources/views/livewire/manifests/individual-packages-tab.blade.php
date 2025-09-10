<div class="individual-packages-tab" x-data="{}">
    <!-- Filters and Search -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg mb-4 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label for="individual-search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input 
                    type="text" 
                    id="individual-search"
                    wire:model.debounce.300ms="search"
                    placeholder="Search by tracking number, customer..."
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                >
            </div>

            <!-- Status Filter -->
            <div>
                <label for="individual-status-filter" class="block text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
                <select 
                    id="individual-status-filter"
                    wire:model="statusFilter"
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                >
                    <option value="">All Statuses</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Sort Options -->
            <div>
                <label for="individual-sort" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                <select 
                    wire:model="sortBy"
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                >
                    <option value="created_at">Date Created</option>
                    <option value="tracking_number">Tracking Number</option>
                    <option value="status">Status</option>
                    <option value="weight">Weight</option>
                    <option value="freight_price">Freight Price</option>
                </select>
            </div>

            <!-- Clear Filters -->
            <div class="flex items-end">
                <button 
                    wire:click="clearFilters"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Clear Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    @if(count($selectedPackages) > 0)
        <div class="bg-wax-flower-50 border border-wax-flower-200 rounded-lg p-4 mb-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <span class="text-sm font-medium text-wax-flower-800">
                        {{ count($selectedPackages) }} package(s) selected
                    </span>
                    
                    <!-- Bulk Actions -->
                    <div class="flex items-center space-x-2">
                        <!-- Status Update -->
                        <select 
                            wire:model="bulkStatus"
                            class="border-wax-flower-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                        >
                            <option value="">Select status...</option>
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>

                        <button 
                            wire:click="confirmBulkStatusUpdate"
                            class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-wax-flower-600 hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500"
                            @if(!$bulkStatus) disabled @endif
                        >
                            Update Status
                        </button>

                        <!-- Consolidation -->
                        @if(count($selectedPackages) >= 2)
                            <button 
                                wire:click="showConsolidationModal"
                                class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                            >
                                Consolidate
                            </button>
                        @endif
                    </div>
                </div>

                <button 
                    wire:click="clearSelection"
                    class="text-sm text-gray-500 hover:text-gray-700"
                >
                    Clear Selection
                </button>
            </div>

            @error('bulkStatus')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('selectedPackages')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <!-- Individual Packages Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Individual Packages ({{ $packages->total() }})
                </h3>
                
                <div class="flex items-center space-x-2">
                    <input 
                        type="checkbox" 
                        wire:model="selectAll"
                        class="h-4 w-4 text-wax-flower-600 focus:ring-wax-flower-500 border-gray-300 rounded"
                        onclick="event.stopPropagation()"
                    >
                    <label class="text-sm text-gray-700">Select All</label>
                </div>
            </div>
        </div>

        @if($packages->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($packages as $package)
                    <div class="px-4 py-4 sm:px-6 hover:bg-gray-50" onclick="event.stopPropagation()">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <input 
                                    type="checkbox" 
                                    wire:model="selectedPackages"
                                    value="{{ $package->id }}"
                                    class="h-4 w-4 text-wax-flower-600 focus:ring-wax-flower-500 border-gray-300 rounded"
                                    onclick="event.stopPropagation()"
                                >
                                
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-3">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ $package->tracking_number }}
                                        </p>
                                        @php
                                            $badgeClass = $package->status->getBadgeClass();
                                            $statusLabel = $package->status->getLabel();
                                        @endphp
                                        @if($badgeClass === 'default')
                                            <x-badges.default>{{ $statusLabel }}</x-badges.default>
                                        @elseif($badgeClass === 'primary')
                                            <x-badges.primary>{{ $statusLabel }}</x-badges.primary>
                                        @elseif($badgeClass === 'success')
                                            <x-badges.success>{{ $statusLabel }}</x-badges.success>
                                        @elseif($badgeClass === 'warning')
                                            <x-badges.warning>{{ $statusLabel }}</x-badges.warning>
                                        @elseif($badgeClass === 'danger')
                                            <x-badges.danger>{{ $statusLabel }}</x-badges.danger>
                                        @elseif($badgeClass === 'shs')
                                            <x-badges.shs>{{ $statusLabel }}</x-badges.shs>
                                        @else
                                            <x-badges.default>{{ $statusLabel }}</x-badges.default>
                                        @endif
                                    </div>
                                    
                                    <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                                        <span>Customer: {{ $package->user->full_name ?? 'N/A' }}</span>
                                        <span>•</span>
                                        <span>Weight: {{ number_format($package->weight, 2) }} lbs</span>
                                        @if($package->freight_price > 0)
                                            <span>•</span>
                                            <span>Freight: ${{ number_format($package->freight_price, 2) }}</span>
                                        @endif
                                    </div>
                                    
                                    <div class="mt-1 text-sm text-gray-600">
                                        <p>{{ Str::limit($package->description, 60) }}</p>
                                    </div>
                                    
                                    <div class="mt-1 grid grid-cols-4 gap-4 text-sm text-gray-600">
                                        <div>Customs: ${{ number_format($package->customs_duty ?? 0, 2) }}</div>
                                        <div>Storage: ${{ number_format($package->storage_fee ?? 0, 2) }}</div>
                                        <div>Delivery: ${{ number_format($package->delivery_fee ?? 0, 2) }}</div>
                                        <div>Total: ${{ number_format(($package->freight_price ?? 0) + ($package->customs_duty ?? 0) + ($package->storage_fee ?? 0) + ($package->delivery_fee ?? 0), 2) }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                <!-- Status Update Dropdown -->
                                <select 
                                    wire:change="updatePackageStatus({{ $package->id }}, $event.target.value)"
                                    class="text-sm border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500"
                                    onclick="event.stopPropagation()"
                                >
                                    <option value="">Change Status</option>
                                    @foreach($statusOptions as $value => $label)
                                        <option value="{{ $value }}" 
                                                @if($package->status->value === $value) selected @endif>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>

                                <!-- Actions Dropdown -->
                                <div class="relative">
                                    <button 
                                        onclick="event.stopPropagation(); toggleIndividualDropdown('individual-dropdown-{{ $package->id }}')"
                                        class="inline-flex items-center p-2 border border-gray-300 rounded-full shadow-sm text-gray-400 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                        </svg>
                                    </button>

                                    <div 
                                        id="individual-dropdown-{{ $package->id }}"
                                        class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10"
                                    >
                                        <div class="py-1">
                                            <button 
                                                onclick="event.stopPropagation(); document.getElementById('package-details-{{ $package->id }}').classList.toggle('hidden'); closeIndividualDropdown('individual-dropdown-{{ $package->id }}')"
                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            >
                                                Toggle Details
                                            </button>
                                            @if($canEdit)
                                                <button 
                                                    onclick="event.stopPropagation(); @this.showFeeEntryModal({{ $package->id }}); closeIndividualDropdown('individual-dropdown-{{ $package->id }}')"
                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                >
                                                    Update Fees
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Expandable Package Details -->
                        <div id="package-details-{{ $package->id }}" class="hidden mt-4 border-t pt-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <h6 class="font-medium text-gray-900 mb-2">Package Information:</h6>
                                    <div class="space-y-1">
                                        <p><span class="font-medium">Warehouse Receipt:</span> {{ $package->warehouse_receipt_no ?? 'N/A' }}</p>
                                        <p><span class="font-medium">Shipper:</span> {{ $package->shipper->name ?? 'N/A' }}</p>
                                        <p><span class="font-medium">Office:</span> {{ $package->office->name ?? 'N/A' }}</p>
                                        <p><span class="font-medium">Estimated Value:</span> ${{ number_format($package->estimated_value ?? 0, 2) }}</p>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="font-medium text-gray-900 mb-2">Customer Information:</h6>
                                    <div class="space-y-1">
                                        @if($package->user && $package->user->profile)
                                            <p><span class="font-medium">Account Number:</span> {{ $package->user->profile->account_number }}</p>
                                            <p><span class="font-medium">Email:</span> {{ $package->user->email }}</p>
                                            <p><span class="font-medium">Phone:</span> {{ $package->user->profile->phone ?? 'N/A' }}</p>
                                        @else
                                            <p class="text-gray-500">No customer information available</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 sm:px-6">
                {{ $packages->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No individual packages found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if($search || $statusFilter)
                        Try adjusting your search or filter criteria.
                    @else
                        This manifest has no individual packages.
                    @endif
                </p>
            </div>
        @endif
    </div>

    <!-- Bulk Status Update Confirmation Modal -->
    @if($showBulkConfirmModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-data="{}">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mt-2">Confirm Bulk Status Update</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">
                            Are you sure you want to update {{ count($selectedPackages) }} package(s) to "{{ $confirmingStatusLabel }}"?
                        </p>
                        
                        <div class="mt-4">
                            <label for="bulk-notes" class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                            <textarea 
                                id="bulk-notes"
                                wire:model="bulkNotes"
                                rows="3"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                                placeholder="Add notes about this status update..."
                            ></textarea>
                        </div>
                    </div>
                    <div class="items-center px-4 py-3">
                        <button 
                            wire:click="executeBulkStatusUpdate"
                            class="px-4 py-2 bg-wax-flower-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-wax-flower-500"
                        >
                            Confirm Update
                        </button>
                        <button 
                            wire:click="cancelBulkUpdate"
                            class="mt-3 px-4 py-2 bg-white text-gray-500 text-base font-medium rounded-md w-full shadow-sm border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Fee Entry Modal -->
    @if($showFeeModal && $feePackage)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-data="{}">
            <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        Update Fees for Package: {{ $feePackage->tracking_number }}
                    </h3>
                    
                    <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-medium text-gray-900">Package Details</h4>
                        <p class="text-sm text-gray-600 mt-1">{{ $feePackage->description }}</p>
                        <p class="text-sm text-gray-600">Customer: {{ $feePackage->user->full_name ?? 'N/A' }}</p>
                        <p class="text-sm text-gray-600">Weight: {{ $feePackage->weight }} lbs</p>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Customs Duty</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0"
                                wire:model="customsDuty"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                            >
                            @error('customsDuty')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Storage Fee</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0"
                                wire:model="storageFee"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                            >
                            @error('storageFee')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Fee</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0"
                                wire:model="deliveryFee"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                            >
                            @error('deliveryFee')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
                        <button 
                            wire:click="closeFeeModal"
                            class="px-4 py-2 bg-white text-gray-500 text-sm font-medium rounded-md shadow-sm border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300"
                        >
                            Cancel
                        </button>
                        <button 
                            wire:click="processFeeUpdate"
                            class="px-4 py-2 bg-wax-flower-600 text-white text-sm font-medium rounded-md shadow-sm hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-wax-flower-500"
                        >
                            Update Fees & Set to Ready
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Consolidation Modal -->
    @if($showConsolidationModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-data="{}">
            <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Consolidate Selected Packages</h3>
                    
                    <div class="mb-4">
                        <h4 class="font-medium text-gray-900 mb-2">Packages to Consolidate:</h4>
                        <div class="space-y-2 max-h-60 overflow-y-auto">
                            @foreach($packagesForConsolidation as $package)
                                <div class="flex items-center justify-between bg-gray-50 p-3 rounded">
                                    <div class="flex items-center space-x-4">
                                        <span class="font-medium">{{ $package['tracking_number'] }}</span>
                                        <span class="text-gray-600">{{ Str::limit($package['description'], 30) }}</span>
                                        <span class="text-gray-500">{{ $package['weight'] }} lbs</span>
                                        <span class="text-gray-500">{{ $package['user']['full_name'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="consolidation-notes" class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                        <textarea 
                            id="consolidation-notes"
                            wire:model="consolidationNotes"
                            rows="3"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                            placeholder="Add notes about this consolidation..."
                        ></textarea>
                    </div>
                    
                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                        <button 
                            wire:click="cancelConsolidation"
                            class="px-4 py-2 bg-white text-gray-500 text-sm font-medium rounded-md shadow-sm border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300"
                        >
                            Cancel
                        </button>
                        <button 
                            wire:click="confirmConsolidation"
                            class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                        >
                            Consolidate Packages
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
function toggleIndividualDropdown(dropdownId) {
    // Close all other dropdowns first
    document.querySelectorAll('[id^="individual-dropdown-"]').forEach(dropdown => {
        if (dropdown.id !== dropdownId) {
            dropdown.classList.add('hidden');
        }
    });
    
    // Toggle the clicked dropdown
    const dropdown = document.getElementById(dropdownId);
    if (dropdown) {
        dropdown.classList.toggle('hidden');
    }
}

function closeIndividualDropdown(dropdownId) {
    const dropdown = document.getElementById(dropdownId);
    if (dropdown) {
        dropdown.classList.add('hidden');
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.relative')) {
        document.querySelectorAll('[id^="individual-dropdown-"]').forEach(dropdown => {
            dropdown.classList.add('hidden');
        });
    }
});

// Close dropdowns on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('[id^="individual-dropdown-"]').forEach(dropdown => {
            dropdown.classList.add('hidden');
        });
    }
});
</script>