<div class="space-y-3">
    @foreach($packages as $package)
        <div class="border border-gray-200 rounded-lg p-4 bg-white hover:bg-gray-50 transition-all duration-200 cursor-pointer
                    {{ $this->isPackageSelected($package->id) ? 'ring-2 ring-blue-500 bg-blue-50 border-blue-300 shadow-md' : 'hover:shadow-md' }}"
             wire:click="togglePackageSelection({{ $package->id }})">
            <div class="flex items-start space-x-4">
                <!-- Selection Checkbox -->
                <div class="flex-shrink-0 pt-1">
                    <div class="relative">
                        <input type="checkbox" 
                               {{ $this->isPackageSelected($package->id) ? 'checked' : '' }}
                               class="form-checkbox h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-colors duration-200">
                        @if($this->isPackageSelected($package->id))
                            <div class="absolute -top-1 -right-1 w-3 h-3 bg-blue-600 rounded-full flex items-center justify-center">
                                <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Package Details -->
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="flex items-center space-x-2">
                            <!-- Available for Consolidation Indicator -->
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium 
                                         {{ $this->isPackageSelected($package->id) ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800' }}">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    @if($this->isPackageSelected($package->id))
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    @else
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    @endif
                                </svg>
                                {{ $this->isPackageSelected($package->id) ? 'Selected' : 'Available' }}
                            </span>
                            <h5 class="font-semibold text-gray-900">{{ $package->tracking_number }}</h5>
                        </div>
                        <x-package-status-badge :status="$package->status" />
                    </div>
                    
                    <div class="text-sm text-gray-600 space-y-2">
                        @if($package->description)
                            <div class="flex items-start">
                                <svg class="w-4 h-4 mt-0.5 mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                <p class="flex-1">{{ $package->description }}</p>
                            </div>
                        @endif
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                            @if($package->weight)
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                                    </svg>
                                    <span class="font-medium">{{ number_format($package->weight, 2) }} lbs</span>
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
                
                <!-- Package Costs -->
                <div class="text-right ml-4">
                    @if($package->freight_price || $package->clearance_fee || $package->storage_fee || $package->delivery_fee)
                        <div class="bg-gray-50 rounded-lg p-3 text-sm {{ $this->isPackageSelected($package->id) ? 'bg-blue-50' : '' }}">
                            @php
                                $totalCost = ($package->freight_price ?? 0) + 
                                           ($package->clearance_fee ?? 0) + 
                                           ($package->storage_fee ?? 0) + 
                                           ($package->delivery_fee ?? 0);
                            @endphp
                            
                            <div class="font-semibold text-gray-900 mb-2 text-lg">${{ number_format($totalCost, 2) }}</div>
                            
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
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 009.586 13H7"></path>
            </svg>
            <p class="text-lg font-medium mb-2">No packages available for consolidation</p>
            <div class="text-sm space-y-1">
                <p>Packages must meet the following criteria to be consolidated:</p>
                <ul class="list-disc list-inside text-left max-w-md mx-auto mt-2 space-y-1">
                    <li>Status: Pending, Processing, or Shipped</li>
                    <li>Not already consolidated</li>
                    <li>Belong to your account</li>
                </ul>
            </div>
        </div>
    @endif
</div>