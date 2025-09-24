<div class="read-only-consolidated-display">
    <!-- Read-only Notice Header -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-gray-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            <div>
                <h4 class="text-sm font-medium text-gray-800">Manifest is Closed - Read Only View</h4>
                <p class="text-sm text-gray-600 mt-1">
                    This manifest is locked and cannot be edited. Consolidated package information is displayed for viewing only.
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
                <label for="readonly-consolidated-search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input 
                    type="text" 
                    id="readonly-consolidated-search"
                    wire:model.debounce.300ms="search"
                    placeholder="Search by tracking number, customer..."
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-gray-500 focus:border-gray-500 sm:text-sm bg-white"
                >
            </div>

            <!-- Status Filter -->
            <div>
                <label for="readonly-consolidated-status-filter" class="block text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
                <select 
                    id="readonly-consolidated-status-filter"
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

    <!-- Read-only Consolidated Packages Display -->
    <div class="bg-gray-50 shadow overflow-hidden sm:rounded-md border border-gray-200">
        <div class="px-4 py-3 bg-gray-100 border-b border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Consolidated Packages ({{ $consolidatedPackages->total() }})
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

        @if($consolidatedPackages->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($consolidatedPackages as $consolidatedPackage)
                    <div class="px-4 py-4 sm:px-6 bg-white">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <!-- Consolidated Package Header -->
                                <div class="flex items-center space-x-3 mb-2">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ $consolidatedPackage->consolidated_tracking_number }}
                                    </p>
                                    @php
                                        $badgeClass = \App\Enums\PackageStatus::from($consolidatedPackage->status)->getBadgeClass();
                                        $statusLabel = \App\Enums\PackageStatus::from($consolidatedPackage->status)->getLabel();
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
                                    
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $consolidatedPackage->total_quantity }} packages
                                    </span>
                                    
                                    <!-- Read-only indicator -->
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                        Locked
                                    </span>
                                </div>
                                
                                <!-- Consolidated Package Basic Info -->
                                <div class="mt-1 flex items-center space-x-4 text-sm text-gray-600">
                                    <span class="font-medium">Customer:</span>
                                    <span>{{ $consolidatedPackage->customer->full_name ?? 'N/A' }}</span>
                                    @if($consolidatedPackage->customer && $consolidatedPackage->customer->profile && $consolidatedPackage->customer->profile->account_number)
                                        <span class="text-gray-500">({{ $consolidatedPackage->customer->profile->account_number }})</span>
                                    @endif
                                </div>
                                
                                <div class="mt-1 flex items-center space-x-4 text-sm text-gray-600">
                                    <span class="font-medium">Total Weight:</span>
                                    <span>{{ number_format($consolidatedPackage->total_weight, 2) }} lbs</span>
                                    @if($consolidatedPackage->total_cost > 0)
                                        <span class="font-medium ml-4">Total Cost:</span>
                                        <span>${{ number_format($consolidatedPackage->total_cost, 2) }}</span>
                                    @endif
                                </div>
                                
                                <!-- Notes -->
                                @if($consolidatedPackage->notes)
                                    <div class="mt-2 text-sm text-gray-700">
                                        <span class="font-medium">Notes:</span>
                                        <p class="mt-1">{{ $consolidatedPackage->notes }}</p>
                                    </div>
                                @endif
                                
                                <!-- Financial Summary -->
                                <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <div class="bg-gray-50 px-3 py-2 rounded">
                                        <span class="font-medium text-gray-700">Freight:</span>
                                        <div class="text-gray-900">${{ number_format($consolidatedPackage->total_freight_price, 2) }}</div>
                                    </div>
                                    <div class="bg-gray-50 px-3 py-2 rounded">
                                        <span class="font-medium text-gray-700">Clearance:</span>
                                        <div class="text-gray-900">${{ number_format($consolidatedPackage->total_clearance_fee, 2) }}</div>
                                    </div>
                                    <div class="bg-gray-50 px-3 py-2 rounded">
                                        <span class="font-medium text-gray-700">Storage:</span>
                                        <div class="text-gray-900">${{ number_format($consolidatedPackage->total_storage_fee, 2) }}</div>
                                    </div>
                                    <div class="bg-gray-50 px-3 py-2 rounded">
                                        <span class="font-medium text-gray-700">Delivery:</span>
                                        <div class="text-gray-900">${{ number_format($consolidatedPackage->total_delivery_fee, 2) }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- View Details Button -->
                            <div class="flex items-center space-x-2 ml-4">
                                <button 
                                    onclick="toggleConsolidatedDetails('readonly-consolidated-details-{{ $consolidatedPackage->id }}')"
                                    class="inline-flex items-center px-3 py-1 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View Packages
                                </button>
                            </div>
                        </div>

                        <!-- Expandable Individual Packages -->
                        <div id="readonly-consolidated-details-{{ $consolidatedPackage->id }}" class="hidden mt-4 border-t border-gray-200 pt-4">
                            <div class="bg-blue-50 p-4 rounded-lg mb-4">
                                <h6 class="font-medium text-gray-900 mb-3 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                    Individual Packages ({{ $consolidatedPackage->packages->count() }})
                                </h6>
                                <div class="space-y-3">
                                    @foreach($consolidatedPackage->packages as $package)
                                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                                            <div class="flex items-center justify-between mb-2">
                                                <div class="flex items-center space-x-3">
                                                    <span class="font-medium text-gray-900">{{ $package->tracking_number }}</span>
                                                    @php
                                                        $packageBadgeClass = $package->status->getBadgeClass();
                                                        $packageStatusLabel = $package->status->getLabel();
                                                    @endphp
                                                    @if($packageBadgeClass === 'default')
                                                        <x-badges.default>{{ $packageStatusLabel }}</x-badges.default>
                                                    @elseif($packageBadgeClass === 'primary')
                                                        <x-badges.primary>{{ $packageStatusLabel }}</x-badges.primary>
                                                    @elseif($packageBadgeClass === 'success')
                                                        <x-badges.success>{{ $packageStatusLabel }}</x-badges.success>
                                                    @elseif($packageBadgeClass === 'warning')
                                                        <x-badges.warning>{{ $packageStatusLabel }}</x-badges.warning>
                                                    @elseif($packageBadgeClass === 'danger')
                                                        <x-badges.danger>{{ $packageStatusLabel }}</x-badges.danger>
                                                    @elseif($packageBadgeClass === 'shs')
                                                        <x-badges.shs>{{ $packageStatusLabel }}</x-badges.shs>
                                                    @else
                                                        <x-badges.default>{{ $packageStatusLabel }}</x-badges.default>
                                                    @endif
                                                </div>
                                                <span class="text-sm text-gray-600">{{ number_format($package->weight, 2) }} lbs</span>
                                            </div>
                                            
                                            <div class="text-sm text-gray-700 mb-2">
                                                <span class="font-medium">Description:</span>
                                                <p>{{ $package->description ?: 'No description provided' }}</p>
                                            </div>
                                            
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                                                <div class="bg-gray-50 px-2 py-1 rounded">
                                                    <span class="text-gray-600">Freight:</span>
                                                    <div class="font-medium">${{ number_format($package->freight_price ?? 0, 2) }}</div>
                                                </div>
                                                <div class="bg-gray-50 px-2 py-1 rounded">
                                                    <span class="text-gray-600">Customs:</span>
                                                    <div class="font-medium">${{ number_format($package->clearance_fee ?? 0, 2) }}</div>
                                                </div>
                                                <div class="bg-gray-50 px-2 py-1 rounded">
                                                    <span class="text-gray-600">Storage:</span>
                                                    <div class="font-medium">${{ number_format($package->storage_fee ?? 0, 2) }}</div>
                                                </div>
                                                <div class="bg-gray-50 px-2 py-1 rounded">
                                                    <span class="text-gray-600">Delivery:</span>
                                                    <div class="font-medium">${{ number_format($package->delivery_fee ?? 0, 2) }}</div>
                                                </div>
                                            </div>
                                            
                                            <!-- Package Items (if applicable) -->
                                            @if($package->items && $package->items->count() > 0)
                                                <div class="mt-3 bg-yellow-50 p-3 rounded border border-yellow-200">
                                                    <h6 class="text-xs font-medium text-gray-900 mb-2">Package Items:</h6>
                                                    <div class="space-y-1">
                                                        @foreach($package->items as $item)
                                                            <div class="flex justify-between items-center text-xs">
                                                                <span>{{ $item->description }} (Qty: {{ $item->quantity }})</span>
                                                                <span class="text-gray-600">{{ number_format($item->weight_per_item, 2) }} lbs each</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            
                            <!-- Consolidation Summary -->
                            <div class="bg-green-50 p-4 rounded-lg">
                                <h6 class="font-medium text-gray-900 mb-3 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4"></path>
                                    </svg>
                                    Consolidation Summary
                                </h6>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Total Packages:</span>
                                        <span class="font-medium">{{ $consolidatedPackage->total_quantity }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Total Weight:</span>
                                        <span class="font-medium">{{ number_format($consolidatedPackage->total_weight, 2) }} lbs</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Created:</span>
                                        <span class="font-medium">{{ $consolidatedPackage->created_at->format('M j, Y') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Total Cost:</span>
                                        <span class="font-semibold text-green-700">${{ number_format($consolidatedPackage->total_cost, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 bg-gray-100 border-t border-gray-200 sm:px-6">
                {{ $consolidatedPackages->links() }}
            </div>
        @else
            <div class="text-center py-12 bg-white">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No consolidated packages found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if($search || $statusFilter)
                        Try adjusting your search or filter criteria.
                    @else
                        This manifest has no consolidated packages.
                    @endif
                </p>
            </div>
        @endif
    </div>
@push('scripts')
<script>
function toggleConsolidatedDetails(detailsId) {
    const detailsElement = document.getElementById(detailsId);
    if (detailsElement) {
        detailsElement.classList.toggle('hidden');
        
        // Update button text/icon if needed
        const button = document.querySelector(`[onclick="toggleConsolidatedDetails('${detailsId}')"]`);
        if (button) {
            const isHidden = detailsElement.classList.contains('hidden');
            const buttonText = button.querySelector('span') || button;
            if (buttonText.textContent) {
                buttonText.textContent = isHidden ? 'View Packages' : 'Hide Packages';
            }
        }
    }
}

// Close all details when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.read-only-consolidated-display')) {
        return;
    }
    
    // If click is not on a details button or inside details content
    if (!event.target.closest('[onclick*="toggleConsolidatedDetails"]') && 
        !event.target.closest('[id^="readonly-consolidated-details-"]')) {
        // Optionally close all details - uncomment if desired
        // document.querySelectorAll('[id^="readonly-consolidated-details-"]').forEach(details => {
        //     details.classList.add('hidden');
        // });
    }
});
</script>
@endpush

@push('styles')
<style>
/* Read-only consolidated display specific styling */
.read-only-consolidated-display {
    /* Subtle visual cues for read-only state */
}

.read-only-consolidated-display .bg-gray-50 {
    /* Slightly more muted background for read-only sections */
    background-color: #f9fafb;
}

.read-only-consolidated-display input[type="text"],
.read-only-consolidated-display select {
    /* Ensure form elements look interactive but are clearly for filtering only */
    background-color: white;
}

.read-only-consolidated-display button:not([onclick*="toggleConsolidatedDetails"]):not([wire:click*="clearFilters"]) {
    /* Disable appearance for non-functional buttons */
    opacity: 0.6;
    cursor: not-allowed;
}

/* Enhanced focus states for accessibility */
.read-only-consolidated-display button:focus,
.read-only-consolidated-display input:focus,
.read-only-consolidated-display select:focus {
    outline: 2px solid #6b7280;
    outline-offset: 2px;
}

/* Print styles for read-only view */
@media print {
    .read-only-consolidated-display [id^="readonly-consolidated-details-"] {
        display: block !important;
    }
    
    .read-only-consolidated-display button {
        display: none;
    }
    
    .read-only-consolidated-display .bg-gray-50,
    .read-only-consolidated-display .bg-blue-50,
    .read-only-consolidated-display .bg-green-50,
    .read-only-consolidated-display .bg-yellow-50 {
        background-color: white !important;
        border: 1px solid #e5e7eb;
    }
}
</style>
@endpush