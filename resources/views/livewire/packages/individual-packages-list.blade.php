<div class="space-y-3">
    @foreach($packages as $package)
        <div class="border border-gray-200 rounded-lg p-4 bg-white hover:bg-gray-50 transition-colors duration-150 shadow-sm hover:shadow-md">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="flex items-center space-x-2">
                            @if($package->isConsolidated())
                                <!-- Consolidated Package Indicator -->
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    Part of Group
                                </span>
                            @else
                                <!-- Individual Package Indicator -->
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                    </svg>
                                    Individual
                                </span>
                            @endif
                            <h5 class="font-semibold text-gray-900">
                                @if($this->hasSearchMatches($package->id, 'individual'))
                                    <x-search-highlight 
                                        :text="$package->tracking_number" 
                                        :search="$search ?? ''" 
                                        :matches="$this->getPackageSearchMatches($package->id, 'individual')" />
                                @else
                                    {{ $package->tracking_number }}
                                @endif
                            </h5>
                        </div>
                        <x-package-status-badge :status="$package->status" />
                        
                        <!-- Search Match Indicators -->
                        @if($this->hasSearchMatches($package->id, 'individual'))
                            <div class="flex items-center space-x-1">
                                @foreach($this->getPackageSearchMatches($package->id, 'individual') as $match)
                                    @if($match['type'] === 'exact')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ ucfirst(str_replace('_', ' ', $match['field'])) }}
                                        </span>
                                    @elseif($match['type'] === 'consolidated')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            Consolidated
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                    
                    <div class="text-sm text-gray-600 space-y-2">
                        @if($package->description)
                            <div class="flex items-start">
                                <svg class="w-4 h-4 mt-0.5 mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                <p class="flex-1">
                                    @if($this->hasSearchMatches($package->id, 'individual'))
                                        <x-search-highlight 
                                            :text="$package->description" 
                                            :search="$search ?? ''" 
                                            :matches="$this->getPackageSearchMatches($package->id, 'individual')" />
                                    @else
                                        {{ $package->description }}
                                    @endif
                                </p>
                            </div>
                        @endif
                        
                        <!-- Consolidated Package Info -->
                        @if($package->isConsolidated() && $package->consolidatedPackage)
                            <div class="mb-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-green-800">
                                            Consolidated Group: {{ $package->consolidatedPackage->consolidated_tracking_number }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-green-600">
                                        {{ $package->consolidatedPackage->total_quantity }} packages total
                                    </div>
                                </div>
                                <div class="mt-2 text-xs text-green-700">
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>Total Weight: {{ number_format($package->consolidatedPackage->total_weight, 2) }} lbs</div>
                                        <div>Total Cost: ${{ number_format($package->consolidatedPackage->total_cost, 2) }}</div>
                                        <div>Status: {{ $package->consolidatedPackage->status }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-sm">
                            @if($package->weight)
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                                    </svg>
                                    <span>{{ number_format($package->weight, 2) }} lbs</span>
                                </div>
                            @endif
                            
                            @if($package->shipper)
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <span>{{ $package->shipper->name }}</span>
                                </div>
                            @endif
                            
                            @if($package->manifest)
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span>{{ $package->manifest->manifest_number }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="text-right ml-4">
                    @if($package->freight_price || $package->clearance_fee || $package->storage_fee || $package->delivery_fee)
                        <div class="bg-gray-50 rounded-lg p-3 text-sm">
                            @php
                                $totalCost = ($package->freight_price ?? 0) + ($package->clearance_fee ?? 0) + ($package->storage_fee ?? 0) + ($package->delivery_fee ?? 0);
                            @endphp
                            <div class="font-semibold text-gray-900 mb-2">${{ number_format($totalCost, 2) }}</div>
                            
                            <div class="space-y-1 text-xs text-gray-600">
                                @if($package->freight_price)
                                    <div class="flex justify-between">
                                        <span>Freight:</span>
                                        <span>${{ number_format($package->freight_price, 2) }}</span>
                                    </div>
                                @endif
                                @if($package->clearance_fee)
                                    <div class="flex justify-between">
                                        <span>Clearance:</span>
                                        <span>${{ number_format($package->clearance_fee, 2) }}</span>
                                    </div>
                                @endif
                                @if($package->storage_fee)
                                    <div class="flex justify-between">
                                        <span>Storage:</span>
                                        <span>${{ number_format($package->storage_fee, 2) }}</span>
                                    </div>
                                @endif
                                @if($package->delivery_fee)
                                    <div class="flex justify-between">
                                        <span>Delivery:</span>
                                        <span>${{ number_format($package->delivery_fee, 2) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    <div class="text-xs text-gray-500 mt-2 flex items-center">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        {{ $package->created_at->format('M d, Y') }}
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    
    @if($packages->isEmpty())
        <div class="text-center py-12 text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 009.586 13H7"></path>
            </svg>
            <p class="text-lg font-medium">No individual packages found</p>
            <p class="text-sm mt-1">All your packages may be consolidated or there are no packages to display.</p>
        </div>
    @endif
</div>