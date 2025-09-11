<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">My Packages</h1>
                    <p class="mt-1 text-sm text-gray-600">Track and manage all your shipments</p>
                </div>
                <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <div class="relative">
                            <input 
                                wire:model.debounce.300ms="search"
                                type="text" 
                                id="search"
                                placeholder="Search by tracking number or description..."
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                            >
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label for="status-filter" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select wire:model="statusFilter" id="status-filter" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="all">All Statuses</option>
                            <option value="in-transit">In Transit</option>
                            <option value="ready">Ready for Pickup</option>
                            <option value="delivered">Delivered</option>
                            <option value="delayed">Delayed</option>
                        </select>
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <label for="type-filter" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                        <select wire:model="typeFilter" id="type-filter" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="all">All Types</option>
                            <option value="air">Air Freight</option>
                            <option value="sea">Sea Freight</option>
                        </select>
                    </div>
                </div>

                <!-- Clear Filters -->
                @if($search || $statusFilter !== 'all' || $typeFilter !== 'all')
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <button wire:click="clearFilters" class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Clear Filters
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Packages Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            @if($packages->count() > 0)
                <!-- Desktop Table -->
                <div class="hidden md:block">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <button wire:click="sortBy('tracking_number')" class="flex items-center space-x-1 hover:text-gray-700">
                                        <span>Package</span>
                                        @if($sortBy === 'tracking_number')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                @if($sortDirection === 'asc')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                @endif
                                            </svg>
                                        @endif
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <button wire:click="sortBy('weight')" class="flex items-center space-x-1 hover:text-gray-700">
                                        <span>
                                            @php
                                                $hasSeaPackages = $packages->contains(function($package) { return $package->isSeaPackage(); });
                                                $hasAirPackages = $packages->contains(function($package) { return !$package->isSeaPackage(); });
                                            @endphp
                                            @if($hasSeaPackages && $hasAirPackages)
                                                Weight/Volume
                                            @elseif($hasSeaPackages)
                                                Cubic Feet
                                            @else
                                                Weight
                                            @endif
                                        </span>
                                        @if($sortBy === 'weight')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                @if($sortDirection === 'asc')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                @endif
                                            </svg>
                                        @endif
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <button wire:click="sortBy('created_at')" class="flex items-center space-x-1 hover:text-gray-700">
                                        <span>Date</span>
                                        @if($sortBy === 'created_at')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                @if($sortDirection === 'asc')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                @endif
                                            </svg>
                                        @endif
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($packages as $package)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $package->tracking_number }}</div>
                                            <div class="text-sm text-gray-500">{{ Str::limit($package->description, 40) }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $badgeClass = $package->status_badge_class ?? 'default';
                                            $statusLabel = $package->status_label ?? 'Unknown';
                                        @endphp
                                        @if($badgeClass === 'success')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $statusLabel }}</span>
                                        @elseif($badgeClass === 'warning')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">{{ $statusLabel }}</span>
                                        @elseif($badgeClass === 'danger')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ $statusLabel }}</span>
                                        @elseif($badgeClass === 'primary')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $statusLabel }}</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $statusLabel }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($package->manifest)
                                            @if($package->manifest->type === 'air')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-sky-100 text-sky-800">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                                    </svg>
                                                    Air
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h8a2 2 0 002-2V8m-9 4h4"></path>
                                                    </svg>
                                                    Sea
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-sm text-gray-500">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if($package->isSeaPackage())
                                            {{ number_format($package->cubic_feet, 2) }} ft³
                                        @else
                                            {{ number_format($package->weight, 1) }} lbs
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $package->created_at->format('M j, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @php
                                            $currentUser = auth()->user();
                                            $packageStatus = $package->status_value;
                                            $canSeeCosts = $currentUser->isSuperAdmin() || $currentUser->isAdmin() || 
                                                          ($currentUser->isCustomer() && in_array($packageStatus, ['ready', 'delivered']));
                                        @endphp
                                        @if($canSeeCosts && $package->total_cost > 0)
                                            ${{ number_format($package->total_cost, 2) }}
                                        @else
                                            <span class="text-gray-500">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button 
                                            wire:click="$emit('showPackageDetails', {{ $package->id }})"
                                            class="text-blue-600 hover:text-blue-900"
                                        >
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden">
                    <div class="space-y-4 p-4">
                        @foreach($packages as $package)
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $package->tracking_number }}
                                            </span>
                                            @if($package->manifest)
                                                @if($package->manifest->type === 'air')
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-sky-100 text-sky-800">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                                        </svg>
                                                        Air
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-teal-100 text-teal-800">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h8a2 2 0 002-2V8m-9 4h4"></path>
                                                        </svg>
                                                        Sea
                                                    </span>
                                                @endif
                                            @endif
                                        </div>
                                        <p class="text-sm font-medium text-gray-900 mb-1">{{ $package->description }}</p>
                                        <div class="flex items-center space-x-4 text-xs text-gray-500">
                                            <span>
                                                @if($package->isSeaPackage())
                                                    {{ number_format($package->cubic_feet, 2) }} ft³
                                                @else
                                                    {{ number_format($package->weight, 1) }} lbs
                                                @endif
                                            </span>
                                            <span>{{ $package->created_at->format('M j, Y') }}</span>
                                        </div>
                                    </div>
                                    <button 
                                        wire:click="$emit('showPackageDetails', {{ $package->id }})"
                                        class="ml-3 p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors duration-150"
                                        title="View details"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="flex items-center justify-between">
                                    @php
                                        $badgeClass = $package->status_badge_class ?? 'default';
                                        $statusLabel = $package->status_label ?? 'Unknown';
                                    @endphp
                                    @if($badgeClass === 'success')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $statusLabel }}</span>
                                    @elseif($badgeClass === 'warning')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">{{ $statusLabel }}</span>
                                    @elseif($badgeClass === 'danger')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ $statusLabel }}</span>
                                    @elseif($badgeClass === 'primary')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $statusLabel }}</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $statusLabel }}</span>
                                    @endif
                                    
                                    @php
                                        $currentUser = auth()->user();
                                        $packageStatus = $package->status_value;
                                        $canSeeCosts = $currentUser->isSuperAdmin() || $currentUser->isAdmin() || 
                                                      ($currentUser->isCustomer() && in_array($packageStatus, ['ready', 'delivered']));
                                    @endphp
                                    @if($canSeeCosts && $package->total_cost > 0)
                                        <span class="text-sm font-semibold text-gray-900">${{ number_format($package->total_cost, 2) }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $packages->links() }}
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1H7a1 1 0 00-1 1v1m8 0V4.5"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No packages found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if($search || $statusFilter !== 'all' || $typeFilter !== 'all')
                            Try adjusting your search criteria or filters.
                        @else
                            Your packages will appear here once they're processed.
                        @endif
                    </p>
                    @if($search || $statusFilter !== 'all' || $typeFilter !== 'all')
                        <div class="mt-6">
                            <button wire:click="clearFilters" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                Clear Filters
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Package Detail Modal --}}
    @livewire('customers.customer-packages-with-modal', ['customer' => auth()->user()])
</div>