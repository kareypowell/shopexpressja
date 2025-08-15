<div class="space-y-3">
    @foreach($packages as $package)
        <div class="border border-gray-200 rounded-lg p-4 bg-white hover:bg-gray-50">
            <div class="flex justify-between items-start">
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
                
                <div class="text-right">
                    @if($package->freight_price || $package->customs_duty || $package->storage_fee || $package->delivery_fee)
                        <div class="text-sm text-gray-600">
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
                    @endif
                    
                    <div class="text-xs text-gray-500 mt-1">
                        {{ $package->created_at->format('M d, Y') }}
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>