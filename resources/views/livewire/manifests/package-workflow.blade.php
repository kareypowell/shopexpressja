<div>
    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded z-50" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

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
                    
                    <select 
                        wire:model="bulkStatus"
                        class="border-wax-flower-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                    >
                        <option value="">Select new status...</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <button 
                        wire:click="confirmBulkStatusUpdate"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-wax-flower-600 hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500"
                        @if(!$bulkStatus) disabled @endif
                    >
                        Update Status
                    </button>

                    @if($this->canDistributeSelected())
                        <button 
                            wire:click="initiateDistribution"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            Distribute Packages
                        </button>
                    @endif
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
                                <!-- Quick Status Update -->
                                <select 
                                    wire:change="updateSinglePackageStatus({{ $package->id }}, $event.target.value)"
                                    class="text-sm border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500"
                                >
                                    <option value="">Change Status...</option>
                                    @foreach($statusOptions as $value => $label)
                                        @if($value !== $package->status)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endif
                                    @endforeach
                                </select>

                                <!-- Package Actions -->
                                <div class="flex items-center space-x-1">
                                    @if($package->canBeDistributed())
                                        <button 
                                            wire:click="initiateDistribution([{{ $package->id }}])"
                                            class="inline-flex items-center p-1 border border-transparent rounded-full shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                            title="Distribute Package"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                            </svg>
                                        </button>
                                    @endif
                                    
                                    <button 
                                        class="inline-flex items-center p-1 border border-transparent rounded-full shadow-sm text-white bg-wax-flower-600 hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500"
                                        title="View Package Details"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
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
</div>