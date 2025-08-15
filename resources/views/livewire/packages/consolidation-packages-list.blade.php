<div class="space-y-3">
    @foreach($packages as $package)
        <div class="border border-gray-200 rounded-lg p-4 bg-white hover:bg-gray-50 
                    {{ $this->isPackageSelected($package->id) ? 'ring-2 ring-blue-500 bg-blue-50' : '' }}">
            <div class="flex items-start space-x-3">
                <!-- Selection Checkbox -->
                <div class="flex-shrink-0 pt-1">
                    <input type="checkbox" 
                           wire:click="togglePackageSelection({{ $package->id }})"
                           {{ $this->isPackageSelected($package->id) ? 'checked' : '' }}
                           class="form-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                </div>
                
                <!-- Package Details -->
                <div class="flex-1">
                    <div class="flex items-center space-x-3">
                        <h5 class="font-medium text-gray-900">{{ $package->tracking_number }}</h5>
                        <x-package-status-badge :status="$package->status" />
                    </div>
                    
                    <div class="mt-2 text-sm text-gray-600">
                        @if($package->description)
                            <p>{{ $package->description }}</p>
                        @endif
                        
                        <div class="flex items-center space-x-4 mt-1">
                            @if($package->weight)
                                <span>Weight: {{ number_format($package->weight, 2) }} lbs</span>
                            @endif
                            
                            @if($package->shipper)
                                <span>From: {{ $package->shipper->name }}</span>
                            @endif
                            
                            @if($package->manifest)
                                <span>Manifest: {{ $package->manifest->manifest_number }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Package Costs -->
                <div class="text-right">
                    @if($package->freight_price || $package->customs_duty || $package->storage_fee || $package->delivery_fee)
                        <div class="text-sm text-gray-600">
                            @php
                                $totalCost = ($package->freight_price ?? 0) + 
                                           ($package->customs_duty ?? 0) + 
                                           ($package->storage_fee ?? 0) + 
                                           ($package->delivery_fee ?? 0);
                            @endphp
                            
                            <div class="font-medium">Total: ${{ number_format($totalCost, 2) }}</div>
                            
                            <div class="text-xs mt-1">
                                @if($package->freight_price)
                                    <div>Freight: ${{ number_format($package->freight_price, 2) }}</div>
                                @endif
                                @if($package->customs_duty)
                                    <div>Customs: ${{ number_format($package->customs_duty, 2) }}</div>
                                @endif
                                @if($package->storage_fee)
                                    <div>Storage: ${{ number_format($package->storage_fee, 2) }}</div>
                                @endif
                                @if($package->delivery_fee)
                                    <div>Delivery: ${{ number_format($package->delivery_fee, 2) }}</div>
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    <div class="text-xs text-gray-500 mt-1">
                        {{ $package->created_at->format('M d, Y') }}
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    
    @if($packages->isEmpty())
        <div class="text-center py-8 text-gray-500">
            <p>No packages available for consolidation.</p>
            <p class="text-sm mt-1">Packages must be in pending, processing, or shipped status to be consolidated.</p>
        </div>
    @endif
</div>