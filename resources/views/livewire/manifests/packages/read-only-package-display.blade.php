<div class="read-only-package-display">
    <!-- Read-only Notice Header -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-gray-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            <div>
                <h4 class="text-sm font-medium text-gray-800">Manifest is Closed - Read Only View</h4>
                <p class="text-sm text-gray-600 mt-1">
                    This manifest is locked and cannot be edited. Package information is displayed for viewing only.
                    @if(auth()->user() && auth()->user()->can('unlock', $manifest))
                        Use the unlock button above to make changes if needed.
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Filters and Search (Read-only) -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg mb-4 p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search -->
            <div>
                <label for="readonly-search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input 
                    type="text" 
                    id="readonly-search"
                    wire:model.debounce.300ms="search"
                    placeholder="Search by tracking number, customer..."
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-gray-500 focus:border-gray-500 sm:text-sm bg-white"
                >
            </div>

            <!-- Status Filter -->
            <div>
                <label for="readonly-status-filter" class="block text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
                <select 
                    id="readonly-status-filter"
                    wire:model="statusFilter"
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-gray-500 focus:border-gray-500 sm:text-sm bg-white"
                >
                    <option value="">All Statuses</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Clear Filters -->
            <div class="flex items-end">
                <button 
                    wire:click="clearFilters"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Clear Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Read-only Package Display -->
    <div class="bg-gray-50 shadow overflow-hidden sm:rounded-md border border-gray-200">
        <div class="px-4 py-3 bg-gray-100 border-b border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Packages ({{ $packages->total() }})
                </h3>
                
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <span class="text-sm text-gray-600 font-medium">View Only</span>
                </div>
            </div>
        </div>

        @if($packages->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($packages as $package)
                    <div class="px-4 py-4 sm:px-6 bg-white">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <!-- Package Header -->
                                <div class="flex items-center space-x-3 mb-2">
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
                                    
                                    <!-- Read-only indicator -->
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                        Locked
                                    </span>
                                </div>
                                
                                <!-- Package Basic Info -->
                                <div class="mt-1 flex items-center space-x-4 text-sm text-gray-600">
                                    <span class="font-medium">Customer:</span>
                                    <span>{{ $package->user->full_name ?? 'N/A' }}</span>
                                    @if($package->user && $package->user->profile && $package->user->profile->account_number)
                                        <span class="text-gray-500">({{ $package->user->profile->account_number }})</span>
                                    @endif
                                </div>
                                
                                <div class="mt-1 flex items-center space-x-4 text-sm text-gray-600">
                                    <span class="font-medium">Weight:</span>
                                    <span>{{ number_format($package->weight, 2) }} lbs</span>
                                    @if($package->freight_price > 0)
                                        <span class="font-medium ml-4">Freight:</span>
                                        <span>${{ number_format($package->freight_price, 2) }}</span>
                                    @endif
                                </div>
                                
                                <!-- Package Description -->
                                <div class="mt-2 text-sm text-gray-700">
                                    <span class="font-medium">Description:</span>
                                    <p class="mt-1">{{ $package->description ?: 'No description provided' }}</p>
                                </div>
                                
                                <!-- Financial Summary -->
                                <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <div class="bg-gray-50 px-3 py-2 rounded">
                                        <span class="font-medium text-gray-700">Clearance:</span>
                                        <div class="text-gray-900">${{ number_format($package->clearance_fee ?? 0, 2) }}</div>
                                    </div>
                                    <div class="bg-gray-50 px-3 py-2 rounded">
                                        <span class="font-medium text-gray-700">Storage:</span>
                                        <div class="text-gray-900">${{ number_format($package->storage_fee ?? 0, 2) }}</div>
                                    </div>
                                    <div class="bg-gray-50 px-3 py-2 rounded">
                                        <span class="font-medium text-gray-700">Delivery:</span>
                                        <div class="text-gray-900">${{ number_format($package->delivery_fee ?? 0, 2) }}</div>
                                    </div>
                                    <div class="bg-blue-50 px-3 py-2 rounded border border-blue-200">
                                        <span class="font-medium text-blue-700">Total:</span>
                                        <div class="text-blue-900 font-semibold">
                                            ${{ number_format(($package->freight_price ?? 0) + ($package->clearance_fee ?? 0) + ($package->storage_fee ?? 0) + ($package->delivery_fee ?? 0), 2) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- View Details Button -->
                            <div class="flex items-center space-x-2 ml-4">
                                <button 
                                    onclick="togglePackageDetails('readonly-details-{{ $package->id }}')"
                                    class="inline-flex items-center px-3 py-1 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    Details
                                </button>
                            </div>
                        </div>

                        <!-- Expandable Package Details -->
                        <div id="readonly-details-{{ $package->id }}" class="hidden mt-4 border-t border-gray-200 pt-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 text-sm">
                                <!-- Package Information -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h6 class="font-medium text-gray-900 mb-3 flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                        Package Information
                                    </h6>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Warehouse Receipt:</span>
                                            <span class="text-gray-900">{{ $package->warehouse_receipt_no ?: 'N/A' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Shipper:</span>
                                            <span class="text-gray-900">{{ $package->shipper->name ?? 'N/A' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Office:</span>
                                            <span class="text-gray-900">{{ $package->office->name ?? 'N/A' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Estimated Value:</span>
                                            <span class="text-gray-900">${{ number_format($package->estimated_value ?? 0, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Created:</span>
                                            <span class="text-gray-900">{{ $package->created_at->format('M j, Y') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Customer Information -->
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <h6 class="font-medium text-gray-900 mb-3 flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        Customer Information
                                    </h6>
                                    @if($package->user && $package->user->profile)
                                        <div class="space-y-2">
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Name:</span>
                                                <span class="text-gray-900">{{ $package->user->full_name }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Account:</span>
                                                <span class="text-gray-900">{{ $package->user->profile->account_number }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Email:</span>
                                                <span class="text-gray-900 text-xs">{{ $package->user->email }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Phone:</span>
                                                <span class="text-gray-900">{{ $package->user->profile->phone ?: 'N/A' }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-gray-500 italic">No customer information available</p>
                                    @endif
                                </div>

                                <!-- Financial Breakdown -->
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <h6 class="font-medium text-gray-900 mb-3 flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                        Financial Details
                                    </h6>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Freight Price:</span>
                                            <span class="text-gray-900">${{ number_format($package->freight_price ?? 0, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Clearance Fee:</span>
                                            <span class="text-gray-900">${{ number_format($package->clearance_fee ?? 0, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Storage Fee:</span>
                                            <span class="text-gray-900">${{ number_format($package->storage_fee ?? 0, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Delivery Fee:</span>
                                            <span class="text-gray-900">${{ number_format($package->delivery_fee ?? 0, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between pt-2 border-t border-green-200">
                                            <span class="font-medium text-gray-900">Total Cost:</span>
                                            <span class="font-semibold text-green-700">
                                                ${{ number_format(($package->freight_price ?? 0) + ($package->clearance_fee ?? 0) + ($package->storage_fee ?? 0) + ($package->delivery_fee ?? 0), 2) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sea Package Items (if applicable) -->
                            @if($package->items && $package->items->count() > 0)
                                <div class="mt-4 bg-yellow-50 p-4 rounded-lg">
                                    <h6 class="font-medium text-gray-900 mb-3 flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        Package Items
                                    </h6>
                                    <div class="space-y-2">
                                        @foreach($package->items as $item)
                                            <div class="flex justify-between items-center bg-white p-2 rounded border">
                                                <div>
                                                    <span class="font-medium">{{ $item->description }}</span>
                                                    <span class="text-gray-500 ml-2">(Qty: {{ $item->quantity }})</span>
                                                </div>
                                                <span class="text-gray-700">{{ number_format($item->weight_per_item, 2) }} lbs each</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Container Information (for sea packages) -->
                            @if($package->container_type)
                                <div class="mt-4 bg-purple-50 p-4 rounded-lg">
                                    <h6 class="font-medium text-gray-900 mb-3 flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                        Container Information
                                    </h6>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Container Type:</span>
                                            <span class="text-gray-900">{{ ucfirst($package->container_type) }}</span>
                                        </div>
                                        @if($package->length_inches && $package->width_inches && $package->height_inches)
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Dimensions:</span>
                                                <span class="text-gray-900">{{ $package->length_inches }}" × {{ $package->width_inches }}" × {{ $package->height_inches }}"</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Cubic Feet:</span>
                                                <span class="text-gray-900">{{ number_format($package->cubic_feet ?? 0, 3) }} ft³</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 bg-gray-100 border-t border-gray-200 sm:px-6">
                {{ $packages->links() }}
            </div>
        @else
            <div class="text-center py-12 bg-white">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No packages found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if($search || $statusFilter)
                        Try adjusting your search or filter criteria.
                    @else
                        This manifest has no packages.
                    @endif
                </p>
            </div>
        @endif
    </div>
</div>

<script>
function togglePackageDetails(detailsId) {
    const detailsElement = document.getElementById(detailsId);
    if (detailsElement) {
        detailsElement.classList.toggle('hidden');
        
        // Update button text/icon if needed
        const button = document.querySelector(`[onclick="togglePackageDetails('${detailsId}')"]`);
        if (button) {
            const isHidden = detailsElement.classList.contains('hidden');
            const buttonText = button.querySelector('span') || button;
            if (buttonText.textContent) {
                buttonText.textContent = isHidden ? 'Details' : 'Hide Details';
            }
        }
    }
}

// Close all details when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.read-only-package-display')) {
        return;
    }
    
    // If click is not on a details button or inside details content
    if (!event.target.closest('[onclick*="togglePackageDetails"]') && 
        !event.target.closest('[id^="readonly-details-"]')) {
        // Optionally close all details - uncomment if desired
        // document.querySelectorAll('[id^="readonly-details-"]').forEach(details => {
        //     details.classList.add('hidden');
        // });
    }
});
</script>

<style>
/* Read-only specific styling */
.read-only-package-display {
    /* Subtle visual cues for read-only state */
}

.read-only-package-display .bg-gray-50 {
    /* Slightly more muted background for read-only sections */
    background-color: #f9fafb;
}

.read-only-package-display input[type="text"],
.read-only-package-display select {
    /* Ensure form elements look interactive but are clearly for filtering only */
    background-color: white;
}

.read-only-package-display button:not([onclick*="togglePackageDetails"]):not([wire:click*="clearFilters"]) {
    /* Disable appearance for non-functional buttons */
    opacity: 0.6;
    cursor: not-allowed;
}

/* Enhanced focus states for accessibility */
.read-only-package-display button:focus,
.read-only-package-display input:focus,
.read-only-package-display select:focus {
    outline: 2px solid #6b7280;
    outline-offset: 2px;
}

/* Print styles for read-only view */
@media print {
    .read-only-package-display [id^="readonly-details-"] {
        display: block !important;
    }
    
    .read-only-package-display button {
        display: none;
    }
    
    .read-only-package-display .bg-gray-50,
    .read-only-package-display .bg-blue-50,
    .read-only-package-display .bg-green-50,
    .read-only-package-display .bg-yellow-50,
    .read-only-package-display .bg-purple-50 {
        background-color: white !important;
        border: 1px solid #e5e7eb;
    }
}
</style>