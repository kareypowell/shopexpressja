<div class="consolidated-packages-tab" x-data="{}">
    @if(isset($error))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
            <div class="flex">
                <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Error Loading Consolidated Packages</h3>
                    <p class="text-sm text-red-700 mt-1">{{ $error }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Filters and Search -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg mb-4 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label for="consolidated-search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input 
                    type="text" 
                    id="consolidated-search"
                    wire:model.debounce.300ms="search"
                    placeholder="Search by tracking number, customer..."
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-shiraz-500 focus:border-shiraz-500 sm:text-sm"
                >
            </div>

            <!-- Status Filter -->
            <div>
                <label for="consolidated-status-filter" class="block text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
                <select 
                    id="consolidated-status-filter"
                    wire:model="statusFilter"
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-shiraz-500 focus:border-shiraz-500 sm:text-sm"
                >
                    <option value="">All Statuses</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Sort Options -->
            <div>
                <label for="consolidated-sort" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                <select 
                    wire:model="sortBy"
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-shiraz-500 focus:border-shiraz-500 sm:text-sm"
                >
                    <option value="consolidated_tracking_number">Tracking Number</option>
                    <option value="status">Status</option>
                    <option value="total_weight">Weight</option>
                    <option value="total_cost">Total Cost</option>
                    <option value="created_at">Date Created</option>
                </select>
            </div>

            <!-- Clear Filters -->
            <div class="flex items-end">
                <button 
                    wire:click="clearFilters"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-shiraz-500"
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
    @if(count($selectedConsolidatedPackages) > 0)
        <div class="bg-shiraz-50 border border-shiraz-200 rounded-lg p-4 mb-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <span class="text-sm font-medium text-shiraz-800">
                        {{ count($selectedConsolidatedPackages) }} consolidated package(s) selected
                    </span>
                    
                    <!-- Custom Status Selection -->
                    <div class="flex items-center space-x-2">
                        <select 
                            wire:model="bulkStatus"
                            class="border-shiraz-300 rounded-md shadow-sm focus:ring-shiraz-500 focus:border-shiraz-500 sm:text-sm"
                        >
                            <option value="">Select status...</option>
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>

                        <button 
                            wire:click="confirmBulkStatusUpdate"
                            class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-shiraz-600 hover:bg-shiraz-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-shiraz-500"
                            @if(!$bulkStatus) disabled @endif
                        >
                            Update Status
                        </button>
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
            @error('selectedConsolidatedPackages')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <!-- Consolidated Packages Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Consolidated Packages ({{ $consolidatedPackages->total() }})
                </h3>
                
                <div class="flex items-center space-x-2">
                    <input 
                        type="checkbox" 
                        wire:model="selectAll"
                        class="h-4 w-4 text-shiraz-600 focus:ring-shiraz-500 border-gray-300 rounded"
                        onclick="event.stopPropagation()"
                    >
                    <label class="text-sm text-gray-700">Select All</label>
                </div>
            </div>
        </div>

        @if($consolidatedPackages->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($consolidatedPackages as $consolidatedPackage)
                    <div class="px-4 py-4 sm:px-6 hover:bg-gray-50" onclick="event.stopPropagation()">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <input 
                                    type="checkbox" 
                                    wire:model="selectedConsolidatedPackages"
                                    value="{{ $consolidatedPackage->id }}"
                                    class="h-4 w-4 text-shiraz-600 focus:ring-shiraz-500 border-gray-300 rounded"
                                    onclick="event.stopPropagation()"
                                >
                                
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-3">
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
                                    </div>
                                    
                                    <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                                        <span>Customer: {{ $consolidatedPackage->customer->full_name ?? 'N/A' }}</span>
                                        <span>•</span>
                                        <span>Weight: {{ number_format($consolidatedPackage->total_weight, 2) }} lbs</span>
                                        @if($consolidatedPackage->total_cost > 0)
                                            <span>•</span>
                                            <span>Cost: ${{ number_format($consolidatedPackage->total_cost, 2) }}</span>
                                        @endif
                                    </div>
                                    
                                    <div class="mt-1 grid grid-cols-4 gap-4 text-sm text-gray-600">
                                        <div>Freight: ${{ number_format($consolidatedPackage->total_freight_price, 2) }}</div>
                                        <div>Clearance: ${{ number_format($consolidatedPackage->total_clearance_fee, 2) }}</div>
                                        <div>Storage: ${{ number_format($consolidatedPackage->total_storage_fee, 2) }}</div>
                                        <div>Delivery: ${{ number_format($consolidatedPackage->total_delivery_fee, 2) }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                <!-- Status Update Dropdown -->
                                <select 
                                    wire:change="updateConsolidatedPackageStatus({{ $consolidatedPackage->id }}, $event.target.value)"
                                    class="text-sm border-gray-300 rounded-md shadow-sm focus:ring-shiraz-500 focus:border-shiraz-500"
                                    onclick="event.stopPropagation()"
                                >
                                    <option value="">Change Status</option>
                                    @foreach($statusOptions as $value => $label)
                                        <option value="{{ $value }}" 
                                                @if($consolidatedPackage->status === $value) selected @endif>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>

                                <!-- Actions Dropdown -->
                                <div class="relative">
                                    <button 
                                        onclick="event.stopPropagation(); toggleDropdown('dropdown-{{ $consolidatedPackage->id }}')"
                                        class="inline-flex items-center p-2 border border-gray-300 rounded-full shadow-sm text-gray-400 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-shiraz-500"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                        </svg>
                                    </button>

                                    <div 
                                        id="dropdown-{{ $consolidatedPackage->id }}"
                                        class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10"
                                    >
                                        <div class="py-1">
                                            <button 
                                                onclick="event.stopPropagation(); document.getElementById('consolidated-details-{{ $consolidatedPackage->id }}').classList.toggle('hidden'); closeDropdown('dropdown-{{ $consolidatedPackage->id }}')"
                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            >
                                                Toggle Details
                                            </button>
                                            @if($consolidatedPackage->canBeUnconsolidated())
                                                <button 
                                                    onclick="event.stopPropagation(); @this.showUnconsolidationModal({{ $consolidatedPackage->id }}); closeDropdown('dropdown-{{ $consolidatedPackage->id }}')"
                                                    class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
                                                >
                                                    Unconsolidate
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Expandable Individual Packages -->
                        <div id="consolidated-details-{{ $consolidatedPackage->id }}" class="hidden mt-4 border-t pt-4">
                            <h6 class="font-medium text-gray-900 mb-2">Individual Packages:</h6>
                            <div class="space-y-2">
                                @foreach($consolidatedPackage->packages as $package)
                                    <div class="flex items-center justify-between bg-gray-50 p-3 rounded">
                                        <div class="flex items-center space-x-4">
                                            <span class="font-medium">{{ $package->tracking_number }}</span>
                                            <span class="text-gray-600">{{ Str::limit($package->description, 30) }}</span>
                                            <span class="text-gray-500">{{ $package->weight }} lbs</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
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
                                            
                                            @if($canEdit)
                                                <a 
                                                    href="{{ route('admin.manifests.packages.edit', [$manifest->id, $package->id]) }}"
                                                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-shiraz-600 hover:text-shiraz-900 border border-shiraz-300 rounded hover:bg-shiraz-50 focus:outline-none focus:ring-2 focus:ring-shiraz-500"
                                                    title="Edit Package"
                                                >
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                    Edit
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 sm:px-6">
                {{ $consolidatedPackages->links() }}
            </div>
        @else
            <div class="text-center py-12">
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
                            Are you sure you want to update {{ count($selectedConsolidatedPackages) }} consolidated package(s) to "{{ $confirmingStatusLabel }}"?
                        </p>
                        
                        <div class="mt-4">
                            <label for="bulk-notes" class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                            <textarea 
                                id="bulk-notes"
                                wire:model="bulkNotes"
                                rows="3"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-shiraz-500 focus:border-shiraz-500 sm:text-sm"
                                placeholder="Add notes about this status update..."
                            ></textarea>
                        </div>
                    </div>
                    <div class="items-center px-4 py-3">
                        <button 
                            wire:click="executeBulkStatusUpdate"
                            class="px-4 py-2 bg-shiraz-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-shiraz-700 focus:outline-none focus:ring-2 focus:ring-shiraz-500"
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

    <!-- Consolidated Fee Entry Modal -->
    @if($showConsolidatedFeeModal && $feeConsolidatedPackage)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-data="{}">
            <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        Update Fees for Consolidated Package: {{ $feeConsolidatedPackage->consolidated_tracking_number }}
                    </h3>
                    
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        @foreach($consolidatedPackagesNeedingFees as $index => $packageData)
                            <div class="border border-gray-200 rounded-lg p-4 {{ $packageData['needs_fees'] ? 'bg-yellow-50' : 'bg-gray-50' }}">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ $packageData['tracking_number'] }}</h4>
                                        <p class="text-sm text-gray-600">{{ $packageData['description'] }}</p>
                                        @if($packageData['needs_fees'])
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Needs Fee Entry
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Clearance Fee</label>
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            min="0"
                                            wire:model="consolidatedPackagesNeedingFees.{{ $index }}.clearance_fee"
                                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-shiraz-500 focus:border-shiraz-500 sm:text-sm"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Storage Fee</label>
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            min="0"
                                            wire:model="consolidatedPackagesNeedingFees.{{ $index }}.storage_fee"
                                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-shiraz-500 focus:border-shiraz-500 sm:text-sm"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Fee</label>
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            min="0"
                                            wire:model="consolidatedPackagesNeedingFees.{{ $index }}.delivery_fee"
                                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-shiraz-500 focus:border-shiraz-500 sm:text-sm"
                                        >
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="flex items-center justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
                        <button 
                            wire:click="closeConsolidatedFeeModal"
                            class="px-4 py-2 bg-white text-gray-500 text-sm font-medium rounded-md shadow-sm border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300"
                        >
                            Cancel
                        </button>
                        <button 
                            wire:click="processConsolidatedFeeUpdate"
                            class="px-4 py-2 bg-shiraz-600 text-white text-sm font-medium rounded-md shadow-sm hover:bg-shiraz-700 focus:outline-none focus:ring-2 focus:ring-shiraz-500"
                        >
                            Update Fees & Set to Ready
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Unconsolidation Confirmation Modal -->
    @if($showUnconsolidationModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-data="{}">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mt-2">Confirm Unconsolidation</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">
                            Are you sure you want to unconsolidate this package? This will separate all individual packages and cannot be undone.
                        </p>
                        
                        <div class="mt-4">
                            <label for="unconsolidation-notes" class="block text-sm font-medium text-gray-700 mb-1">Reason (optional)</label>
                            <textarea 
                                id="unconsolidation-notes"
                                wire:model="unconsolidationNotes"
                                rows="3"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-shiraz-500 focus:border-shiraz-500 sm:text-sm"
                                placeholder="Reason for unconsolidation..."
                            ></textarea>
                        </div>
                    </div>
                    <div class="items-center px-4 py-3">
                        <button 
                            wire:click="confirmUnconsolidation"
                            class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                        >
                            Confirm Unconsolidation
                        </button>
                        <button 
                            wire:click="cancelUnconsolidation"
                            class="mt-3 px-4 py-2 bg-white text-gray-500 text-base font-medium rounded-md w-full shadow-sm border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>

@push('scripts')
<script>
function toggleDropdown(dropdownId) {
    // Close all other dropdowns first
    document.querySelectorAll('[id^="dropdown-"]').forEach(dropdown => {
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

function closeDropdown(dropdownId) {
    const dropdown = document.getElementById(dropdownId);
    if (dropdown) {
        dropdown.classList.add('hidden');
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.relative')) {
        document.querySelectorAll('[id^="dropdown-"]').forEach(dropdown => {
            dropdown.classList.add('hidden');
        });
    }
});

// Close dropdowns on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('[id^="dropdown-"]').forEach(dropdown => {
            dropdown.classList.add('hidden');
        });
    }
});
</script>
@endpush