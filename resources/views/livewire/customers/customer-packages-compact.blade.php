<div>
    @if($packages->count() > 0)
        <!-- Compact Package Cards -->
        <div class="space-y-4">
            @foreach($packages->take(5) as $package)
                <div class="bg-white border border-gray-200 rounded-lg hover:shadow-md transition-shadow duration-200 overflow-hidden">
                    <!-- Mobile Layout -->
                    <div class="block sm:hidden p-4">
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
                                    @if($package->shipper)
                                        <span>via {{ $package->shipper->name }}</span>
                                    @endif
                                </div>
                            </div>
                            <button 
                                wire:click="showPackageDetails({{ $package->id }})"
                                class="ml-3 p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors duration-150"
                                title="View details"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Desktop Layout -->
                    <div class="hidden sm:block p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4 flex-1 min-w-0">
                                <!-- Package Icon & Tracking -->
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1H7a1 1 0 00-1 1v1m8 0V4.5"></path>
                                        </svg>
                                    </div>
                                </div>
                                
                                <!-- Package Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-3 mb-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                            {{ $package->tracking_number }}
                                        </span>
                                        @if($package->manifest)
                                            @if($package->manifest->type === 'air')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-sky-100 text-sky-800">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                                    </svg>
                                                    Air
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-teal-100 text-teal-800">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h8a2 2 0 002-2V8m-9 4h4"></path>
                                                    </svg>
                                                    Sea
                                                </span>
                                            @endif
                                        @endif
                                        @php
                                            $badgeClass = $package->status_badge_class ?? 'default';
                                            $statusLabel = $package->status_label ?? 'Unknown';
                                        @endphp
                                        @if($badgeClass === 'success')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-green-100 text-green-800">{{ $statusLabel }}</span>
                                        @elseif($badgeClass === 'warning')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">{{ $statusLabel }}</span>
                                        @elseif($badgeClass === 'danger')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-red-100 text-red-800">{{ $statusLabel }}</span>
                                        @elseif($badgeClass === 'primary')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800">{{ $statusLabel }}</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-gray-100 text-gray-800">{{ $statusLabel }}</span>
                                        @endif
                                    </div>
                                    <p class="text-sm font-medium text-gray-900 mb-1">{{ $package->description }}</p>
                                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16l-3-3m3 3l3-3"></path>
                                            </svg>
                                            @if($package->isSeaPackage())
                                                {{ number_format($package->cubic_feet, 2) }} ft³
                                            @else
                                                {{ number_format($package->weight, 1) }} lbs
                                            @endif 
                                        </span>
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0h6m-6 0l-2 9a2 2 0 002 2h8a2 2 0 002-2l-2-9m-6 0V7"></path>
                                            </svg>
                                            {{ $package->created_at->format('M j, Y') }}
                                        </span>
                                        @if($package->shipper)
                                            <span>via {{ $package->shipper->name }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex items-center space-x-2">
                                @php
                                    $currentUser = auth()->user();
                                    $packageStatus = $package->status_value;
                                    $canSeeCosts = $currentUser->role_id == 1 || $currentUser->role_id == 2 || 
                                                  ($currentUser->role_id == 3 && in_array($packageStatus, ['ready', 'delivered']));
                                @endphp
                                @if($canSeeCosts && $package->total_cost > 0)
                                    <div class="text-right mr-4">
                                        <p class="text-lg font-bold text-gray-900">${{ number_format($package->total_cost, 2) }}</p>
                                        <p class="text-xs text-gray-500">Total Cost</p>
                                    </div>
                                @endif
                                <button 
                                    wire:click="showPackageDetails({{ $package->id }})"
                                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Show More Button -->
        @if($packages->count() > 5)
            <div class="mt-6 text-center">
                <a href="{{ route('packages.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                    Show {{ $packages->count() - 5 }} More {{ Str::plural('Package', $packages->count() - 5) }}
                </a>
            </div>
        @endif
    @else
        <!-- Empty State -->
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1H7a1 1 0 00-1 1v1m8 0V4.5"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No packages found</h3>
            <p class="mt-1 text-sm text-gray-500">
                Your packages will appear here once they're processed.
            </p>
        </div>
    @endif
</div>