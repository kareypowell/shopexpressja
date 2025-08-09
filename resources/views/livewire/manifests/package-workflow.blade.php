<div x-data="{}">
    <!-- Toastr notifications will be handled by JavaScript -->

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Package Workflow Management
                @if($manifestId)
                    - Manifest #{{ $manifestId }}
                @endif
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Manage package statuses and track workflow progress
            </p>
        </div>
        
        @if($manifestId)
            <a href="{{ route('admin.manifests.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Manifests
            </a>
        @endif
    </div>

    <!-- Status Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
        @foreach($statusStatistics as $status => $stats)
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            @php
                                $badgeClass = $stats['badge_class'] ?? 'default';
                            @endphp
                            @if($badgeClass === 'default')
                                <x-badges.default>{{ $stats['count'] }}</x-badges.default>
                            @elseif($badgeClass === 'primary')
                                <x-badges.primary>{{ $stats['count'] }}</x-badges.primary>
                            @elseif($badgeClass === 'success')
                                <x-badges.success>{{ $stats['count'] }}</x-badges.success>
                            @elseif($badgeClass === 'warning')
                                <x-badges.warning>{{ $stats['count'] }}</x-badges.warning>
                            @elseif($badgeClass === 'danger')
                                <x-badges.danger>{{ $stats['count'] }}</x-badges.danger>
                            @elseif($badgeClass === 'shs')
                                <x-badges.shs>{{ $stats['count'] }}</x-badges.shs>
                            @else
                                <x-badges.default>{{ $stats['count'] }}</x-badges.default>
                            @endif
                        </div>
                        <div class="ml-3 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    {{ $stats['label'] }}
                                </dt>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Filters and Search -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg mb-4 p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input 
                    type="text" 
                    id="search"
                    wire:model.debounce.300ms="search"
                    placeholder="Search by tracking number..."
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                >
            </div>

            <!-- Status Filter -->
            <div>
                <label for="status-filter" class="block text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
                <select 
                    id="status-filter"
                    wire:model="statusFilter"
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
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
                    
                    <!-- Quick Actions -->
                    <div class="flex items-center space-x-2">
                        @php
                            $commonNextStatus = $this->getCommonNextStatus();
                        @endphp
                        
                        @if($commonNextStatus)
                            <button 
                                wire:click="bulkAdvanceToNext"
                                class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                title="Advance all selected packages to {{ $commonNextStatus->getLabel() }}"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                                Advance to {{ $commonNextStatus->getLabel() }}
                            </button>
                        @endif

                        @if($this->canDistributeSelected())
                            <button 
                                wire:click="initiateDistribution"
                                class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                Distribute
                            </button>
                        @endif
                    </div>
                    
                    <!-- Custom Status Selection -->
                    <div class="flex items-center space-x-2">
                        <select 
                            wire:model="bulkStatus"
                            class="border-wax-flower-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                        >
                            <option value="">Custom status...</option>
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>

                        <button 
                            wire:click="confirmBulkStatusUpdate"
                            class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-wax-flower-600 hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500"
                            @if(!$bulkStatus) disabled @endif
                        >
                            Update
                        </button>
                    </div>
                </div>

                <button 
                    wire:click="$set('selectedPackages', [])"
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

    <!-- Packages Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Packages ({{ $packages->total() }})
                </h3>
                
                <div class="flex items-center space-x-2">
                    <input 
                        type="checkbox" 
                        wire:model="selectAll"
                        class="h-4 w-4 text-wax-flower-600 focus:ring-wax-flower-500 border-gray-300 rounded"
                    >
                    <label class="text-sm text-gray-700">Select All</label>
                </div>
            </div>
        </div>

        @if($packages->count() > 0)
            <ul class="divide-y divide-gray-200">
                @foreach($packages as $package)
                    <li class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <input 
                                    type="checkbox" 
                                    wire:model="selectedPackages"
                                    value="{{ $package->id }}"
                                    class="h-4 w-4 text-wax-flower-600 focus:ring-wax-flower-500 border-gray-300 rounded"
                                >
                                
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-3">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ $package->tracking_number }}
                                        </p>
                                        @php
                                            $badgeClass = $package->status_badge_class ?? 'default';
                                            $statusLabel = $package->status_label ?? 'Unknown';
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
                                        @if($package->total_cost > 0)
                                            <span>•</span>
                                            <span>Cost: ${{ number_format($package->total_cost, 2) }}</span>
                                        @endif
                                    </div>
                                    
                                    @if($package->description)
                                        <p class="mt-1 text-sm text-gray-500 truncate">
                                            {{ $package->description }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                <!-- Status Progression Buttons -->
                                <div class="flex items-center space-x-1">
                                    @php
                                        $currentStatus = \App\Enums\PackageStatus::from($package->status);
                                        $allValidTransitions = $currentStatus->getValidTransitions();
                                        // Filter out DELIVERED status from manual updates
                                        $validTransitions = collect($allValidTransitions)->filter(function($transition) {
                                            return $transition->value !== \App\Enums\PackageStatus::DELIVERED;
                                        })->toArray();
                                        $nextStatus = $this->getNextLogicalStatus($package->status);
                                    @endphp
                                    
                                    @if($nextStatus)
                                        <button 
                                            wire:click="updateSinglePackageStatus({{ $package->id }}, '{{ $nextStatus->value }}')"
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                            title="Advance to {{ $nextStatus->getLabel() }}"
                                        >
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                            {{ $nextStatus->getLabel() }}
                                        </button>
                                    @endif

                                    @if($package->canBeDistributed())
                                        <button 
                                            wire:click="initiateDistribution([{{ $package->id }}])"
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                            title="Distribute Package"
                                        >
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                            </svg>
                                            Distribute
                                        </button>
                                    @endif
                                </div>

                                <!-- More Options Dropdown -->
                                <div class="relative" x-data="{ open: false }">
                                    <button 
                                        @click="open = !open"
                                        class="inline-flex items-center p-1 border border-gray-300 rounded-full shadow-sm text-gray-400 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500"
                                        title="More Options"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                        </svg>
                                    </button>
                                    
                                    <div 
                                        x-show="open" 
                                        @click.away="open = false"
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="transform opacity-0 scale-95"
                                        x-transition:enter-end="transform opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="transform opacity-100 scale-100"
                                        x-transition:leave-end="transform opacity-0 scale-95"
                                        class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200"
                                    >
                                        <div class="py-1">
                                            @foreach($validTransitions as $transition)
                                                @if(!$nextStatus || $transition->value !== $nextStatus->value)
                                                    <button 
                                                        wire:click="updateSinglePackageStatus({{ $package->id }}, '{{ $transition->value }}')"
                                                        @click="open = false"
                                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                    >
                                                        {{ $transition->getLabel() }}
                                                    </button>
                                                @endif
                                            @endforeach
                                            
                                            <div class="border-t border-gray-100"></div>
                                            <button 
                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                title="View Package Details"
                                            >
                                                View Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <!-- Pagination -->
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 sm:px-6">
                {{ $packages->links() }}
            </div>
        @else
            <div class="px-4 py-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v1M7 6V4a1 1 0 011-1h4a1 1 0 011 1v2"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No packages found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if($search || $statusFilter)
                        Try adjusting your search criteria or filters.
                    @else
                        No packages are available for workflow management.
                    @endif
                </p>
            </div>
        @endif
    </div>

    <!-- Bulk Status Update Confirmation Modal -->
    @if($showConfirmModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="confirm-modal">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.962-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mt-2">Confirm Status Update</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">
                            Are you sure you want to update {{ count($confirmingPackages) }} package(s) to 
                            <strong>{{ $confirmingStatusLabel ?: 'Unknown' }}</strong>?
                        </p>
                        
                        <!-- Optional Notes -->
                        <div class="mt-4">
                            <label for="notes" class="block text-sm font-medium text-gray-700 text-left">
                                Notes (optional)
                            </label>
                            <textarea 
                                id="notes"
                                wire:model="notes"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                                placeholder="Add any notes about this status change..."
                            ></textarea>
                        </div>
                    </div>
                    <div class="items-center px-4 py-3">
                        <button 
                            wire:click="executeBulkStatusUpdate" 
                            class="px-4 py-2 bg-wax-flower-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-wax-flower-600 focus:outline-none focus:ring-2 focus:ring-wax-flower-300"
                        >
                            Confirm
                        </button>
                        <button 
                            wire:click="cancelBulkUpdate" 
                            class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Fee Entry Modal -->
    @if($showFeeModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="fee-modal">
            <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            Update Package Fees - {{ $feePackage->tracking_number ?? '' }}
                        </h3>
                        <button wire:click="closeFeeModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    @if($feePackage)
                        <!-- Package Info -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <h4 class="font-medium text-gray-900 mb-2">Package Information</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Customer:</span>
                                    <span class="ml-2 font-medium">{{ $feePackage->user->full_name ?? 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Description:</span>
                                    <span class="ml-2">{{ $feePackage->description }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Weight:</span>
                                    <span class="ml-2">{{ number_format($feePackage->weight, 2) }} lbs</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Freight Price:</span>
                                    <span class="ml-2">${{ number_format($feePackage->freight_price ?? 0, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Fee Entry Form -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div>
                                <label for="customs-duty" class="block text-sm font-medium text-gray-700 mb-1">
                                    Customs Duty
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                                    <input 
                                        type="number" 
                                        id="customs-duty"
                                        wire:model.lazy="customsDuty"
                                        step="0.01"
                                        min="0"
                                        class="pl-8 block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                                        placeholder="0.00"
                                    >
                                </div>
                                @error('customsDuty')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="storage-fee" class="block text-sm font-medium text-gray-700 mb-1">
                                    Storage Fee
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                                    <input 
                                        type="number" 
                                        id="storage-fee"
                                        wire:model.lazy="storageFee"
                                        step="0.01"
                                        min="0"
                                        class="pl-8 block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                                        placeholder="0.00"
                                    >
                                </div>
                                @error('storageFee')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="delivery-fee" class="block text-sm font-medium text-gray-700 mb-1">
                                    Delivery Fee
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                                    <input 
                                        type="number" 
                                        id="delivery-fee"
                                        wire:model.lazy="deliveryFee"
                                        step="0.01"
                                        min="0"
                                        class="pl-8 block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                                        placeholder="0.00"
                                    >
                                </div>
                                @error('deliveryFee')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Credit Balance Option -->
                        @if($feePackage->user->credit_balance > 0)
                            <div class="mb-6">
                                <div class="flex items-center">
                                    <input 
                                        type="checkbox" 
                                        id="apply-credit"
                                        wire:model="applyCreditBalance"
                                        class="h-4 w-4 text-wax-flower-600 focus:ring-wax-flower-500 border-gray-300 rounded"
                                    >
                                    <label for="apply-credit" class="ml-2 block text-sm text-gray-900">
                                        Apply available credit balance (${{ number_format($feePackage->user->credit_balance, 2) }})
                                    </label>
                                </div>
                            </div>
                        @endif

                        <!-- Fee Preview -->
                        @if($feePreview && $feePreview['valid'])
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                                <h4 class="font-medium text-blue-900 mb-3">Cost Summary</h4>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Freight Price:</span>
                                            <span>${{ number_format($feePreview['fees']['freight_price'], 2) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Customs Duty:</span>
                                            <span>${{ number_format($feePreview['fees']['customs_duty'], 2) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Storage Fee:</span>
                                            <span>${{ number_format($feePreview['fees']['storage_fee'], 2) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Delivery Fee:</span>
                                            <span>${{ number_format($feePreview['fees']['delivery_fee'], 2) }}</span>
                                        </div>
                                        <div class="flex justify-between font-medium border-t pt-2">
                                            <span>Total Cost:</span>
                                            <span>${{ $feePreview['formatted']['new_total_cost'] }}</span>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        @if($applyCreditBalance && $feePreview['cost_summary']['credit_to_apply'] > 0)
                                            <div class="flex justify-between text-green-600">
                                                <span>Credit Applied:</span>
                                                <span>-${{ $feePreview['formatted']['credit_to_apply'] }}</span>
                                            </div>
                                        @endif
                                        <div class="flex justify-between font-medium">
                                            <span>Net Charge:</span>
                                            <span>${{ $feePreview['formatted']['net_charge'] }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm text-gray-600">
                                            <span>Customer Balance After:</span>
                                            <span>${{ $feePreview['formatted']['customer_balance_after'] }}</span>
                                        </div>
                                        @if($applyCreditBalance)
                                            <div class="flex justify-between text-sm text-gray-600">
                                                <span>Credit Balance After:</span>
                                                <span>${{ $feePreview['formatted']['customer_credit_after'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end space-x-3">
                            <button 
                                wire:click="closeFeeModal" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300"
                            >
                                Cancel
                            </button>
                            <button 
                                wire:click="processFeeUpdate" 
                                class="px-4 py-2 bg-wax-flower-600 text-white text-sm font-medium rounded-md hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-wax-flower-500"
                                @if(!$feePreview || !$feePreview['valid']) disabled @endif
                            >
                                Update Fees & Set Ready
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>