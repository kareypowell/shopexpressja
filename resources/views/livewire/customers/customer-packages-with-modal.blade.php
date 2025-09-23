<div>
    {{-- Package Detail Modal --}}
    @if($showModal && ($selectedPackage || $selectedConsolidatedPackage))
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="package-modal-{{ $isConsolidatedPackage ? 'consolidated-' . $selectedConsolidatedPackage->id : $selectedPackage->id }}">
            {{-- Backdrop --}}
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75" wire:click="closeModal"></div>
                </div>

                {{-- This element is to trick the browser into centering the modal contents. --}}
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                {{-- Modal panel --}}
                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
                    
                    <div class="sm:flex sm:items-start">
                        <div class="w-full">
                            {{-- Header --}}
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1H7a1 1 0 00-1 1v1m8 0V4.5"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                                            @if($isConsolidatedPackage)
                                                Consolidated Package Details
                                            @else
                                                Package Details
                                            @endif
                                        </h3>
                                        <p class="text-sm text-gray-500">
                                            @if($isConsolidatedPackage)
                                                {{ $selectedConsolidatedPackage->consolidated_tracking_number }}
                                            @else
                                                {{ $selectedPackage->tracking_number }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>

                            {{-- Content --}}
                            @if($isConsolidatedPackage)
                                {{-- Consolidated Package Content --}}
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    {{-- Basic Information --}}
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <h4 class="text-sm font-medium text-gray-900 mb-3">Consolidated Package Information</h4>
                                        <dl class="space-y-2">
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Consolidated Tracking:</dt>
                                                <dd class="text-sm font-medium text-gray-900">{{ $selectedConsolidatedPackage->consolidated_tracking_number }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Number of Packages:</dt>
                                                <dd class="text-sm font-medium text-gray-900">{{ $selectedConsolidatedPackage->packages->count() }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Status:</dt>
                                                <dd class="text-sm">
                                                    <x-package-status-badge :status="$selectedConsolidatedPackage->status" />
                                                </dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Consolidated Date:</dt>
                                                <dd class="text-sm font-medium text-gray-900">{{ $selectedConsolidatedPackage->consolidated_at->format('M d, Y g:i A') }}</dd>
                                            </div>
                                            @if($selectedConsolidatedPackage->createdBy)
                                                <div class="flex justify-between">
                                                    <dt class="text-sm text-gray-500">Consolidated By:</dt>
                                                    <dd class="text-sm font-medium text-gray-900">{{ $selectedConsolidatedPackage->createdBy->full_name ?? $selectedConsolidatedPackage->createdBy->name ?? 'N/A' }}</dd>
                                                </div>
                                            @endif
                                        </dl>
                                    </div>

                                    {{-- Shipping Information --}}
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <h4 class="text-sm font-medium text-gray-900 mb-3">Shipping Information</h4>
                                        <dl class="space-y-2">
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Weight:</dt>
                                                <dd class="text-sm font-medium text-gray-900">{{ number_format($selectedConsolidatedPackage->total_weight, 2) }} lbs</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Estimated Value:</dt>
                                                <dd class="text-sm font-medium text-gray-900">${{ number_format($selectedConsolidatedPackage->packages->sum('estimated_value'), 2) }} USD</dd>
                                            </div>
                                            @php
                                                $firstPackage = $selectedConsolidatedPackage->packages->first();
                                            @endphp
                                            @if($firstPackage && $firstPackage->shipper)
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Shipper:</dt>
                                                <dd class="text-sm font-medium text-gray-900">{{ $firstPackage->shipper->name }}</dd>
                                            </div>
                                            @endif
                                            @if($firstPackage && $firstPackage->office)
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Office:</dt>
                                                <dd class="text-sm font-medium text-gray-900">{{ $firstPackage->office->name }}</dd>
                                            </div>
                                            @endif


                                        </dl>
                                    </div>

                                    {{-- Cost Breakdown --}}
                                    @php
                                        $currentUser = auth()->user();
                                        $consolidatedStatus = $selectedConsolidatedPackage->status;
                                        $canSeeCosts = $currentUser->isSuperAdmin() || $currentUser->isAdmin() || 
                                                      ($currentUser->isCustomer() && in_array($consolidatedStatus, ['ready', 'delivered']));
                                    @endphp
                                    @if($canSeeCosts)
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <h4 class="text-sm font-medium text-gray-900 mb-3">Cost Breakdown</h4>
                                        <dl class="space-y-2">
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Freight:</dt>
                                                <dd class="text-sm font-medium text-gray-900">${{ number_format($selectedConsolidatedPackage->total_freight_price ?? 0, 2) }} JMD</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Clearance Fee:</dt>
                                                <dd class="text-sm font-medium text-gray-900">${{ number_format($selectedConsolidatedPackage->total_clearance_fee ?? 0, 2) }} JMD</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Storage Fee:</dt>
                                                <dd class="text-sm font-medium text-gray-900">${{ number_format($selectedConsolidatedPackage->total_storage_fee ?? 0, 2) }} JMD</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Delivery Fee:</dt>
                                                <dd class="text-sm font-medium text-gray-900">${{ number_format($selectedConsolidatedPackage->total_delivery_fee ?? 0, 2) }} JMD</dd>
                                            </div>
                                            <div class="flex justify-between pt-2 border-t border-gray-200">
                                                <dt class="text-sm font-medium text-gray-900">Total Cost:</dt>
                                                <dd class="text-sm font-bold text-gray-900">${{ number_format($selectedConsolidatedPackage->total_cost, 2) }} JMD</dd>
                                            </div>
                                        </dl>
                                    </div>
                                    @else
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <h4 class="text-sm font-medium text-gray-900 mb-3">Cost Information</h4>
                                        <p class="text-sm text-gray-600">Cost details will be available when your consolidated package is ready for pickup.</p>
                                    </div>
                                    @endif

                                    {{-- Individual Packages in Consolidation (Accordion) --}}
                                    @if($selectedConsolidatedPackage->packages && $selectedConsolidatedPackage->packages->count() > 0)
                                    <div class="bg-gray-50 rounded-lg lg:col-span-2">
                                        {{-- Accordion Header --}}
                                        <button 
                                            wire:click="toggleIndividualPackages"
                                            class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-100 rounded-lg transition-colors duration-150"
                                        >
                                            <div class="flex items-center space-x-3">
                                                <h4 class="text-sm font-medium text-gray-900">Individual Packages ({{ $selectedConsolidatedPackage->packages->count() }})</h4>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $showIndividualPackages ? 'Hide' : 'Show' }} Details
                                                </span>
                                            </div>
                                            <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200 {{ $showIndividualPackages ? 'rotate-180' : '' }}" 
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>

                                        {{-- Accordion Content --}}
                                        @if($showIndividualPackages)
                                            <div class="px-4 pb-4 border-t border-gray-200">
                                                <div class="space-y-3 max-h-60 overflow-y-auto mt-3">
                                                    @foreach($selectedConsolidatedPackage->packages as $package)
                                                        <div class="bg-white rounded p-3 border border-gray-200 shadow-sm">
                                                            <div class="flex justify-between items-start">
                                                                <div class="flex-1">
                                                                    <div class="flex items-center space-x-2 mb-2">
                                                                        <p class="text-sm font-medium text-gray-900">{{ $package->tracking_number }}</p>
                                                                        @if($package->manifest)
                                                                            @if($package->manifest->type === 'air')
                                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-sky-100 text-sky-800">
                                                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                                                                    </svg>
                                                                                    Air
                                                                                </span>
                                                                            @else
                                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800">
                                                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h8a2 2 0 002-2V8m-9 4h4"></path>
                                                                                    </svg>
                                                                                    Sea
                                                                                </span>
                                                                            @endif
                                                                        @endif
                                                                    </div>
                                                                    <p class="text-xs text-gray-600 mb-1">{{ $package->description }}</p>
                                                                    <div class="flex items-center space-x-4 text-xs text-gray-500">
                                                                        <span class="flex items-center">
                                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16l-3-3m3 3l3-3"></path>
                                                                            </svg>
                                                                            {{ number_format($package->weight, 1) }} lbs
                                                                        </span>
                                                                        @if($package->cubic_feet)
                                                                            <span class="flex items-center">
                                                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                                                </svg>
                                                                                {{ number_format($package->cubic_feet, 2) }} ft³
                                                                            </span>
                                                                        @endif
                                                                        @if($package->shipper)
                                                                            <span class="flex items-center">
                                                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                                                </svg>
                                                                                via {{ $package->shipper->name }}
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                @if($canSeeCosts)
                                                                    <div class="text-right">
                                                                        @if($package->total_cost > 0)
                                                                            <p class="text-sm font-medium text-gray-900">${{ number_format($package->total_cost, 2) }}</p>
                                                                            <p class="text-xs text-gray-500">Total Cost</p>
                                                                        @endif
                                                                        @if($package->freight_price > 0)
                                                                            <p class="text-xs text-gray-600 mt-1">Freight: ${{ number_format($package->freight_price, 2) }}</p>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            @else
                                {{-- Individual Package Content --}}
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                {{-- Basic Information --}}
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="text-sm font-medium text-gray-900 mb-3">Basic Information</h4>
                                    <dl class="space-y-2">
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Tracking Number:</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{ $selectedPackage->tracking_number }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Warehouse Receipt:</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{ $selectedPackage->warehouse_receipt_no ?: 'N/A' }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Description:</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{ $selectedPackage->description }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Status:</dt>
                                            <dd class="text-sm">
                                                @php
                                                  $badgeClass = $selectedPackage->status_badge_class ?? 'default';
                                                  $statusLabel = $selectedPackage->status_label ?? 'Unknown';
                                                @endphp
                                                @if($badgeClass === 'default')
                                                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $statusLabel }}</span>
                                                @elseif($badgeClass === 'primary')
                                                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $statusLabel }}</span>
                                                @elseif($badgeClass === 'success')
                                                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $statusLabel }}</span>
                                                @elseif($badgeClass === 'warning')
                                                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">{{ $statusLabel }}</span>
                                                @elseif($badgeClass === 'danger')
                                                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ $statusLabel }}</span>
                                                @elseif($badgeClass === 'shs')
                                                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">{{ $statusLabel }}</span>
                                                @else
                                                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $statusLabel }}</span>
                                                @endif
                                            </dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Date Created:</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{ $selectedPackage->created_at->format('M d, Y g:i A') }}</dd>
                                        </div>
                                    </dl>
                                </div>

                                {{-- Shipping Information --}}
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="text-sm font-medium text-gray-900 mb-3">Shipping Information</h4>
                                    <dl class="space-y-2">
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Weight:</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{ number_format($selectedPackage->weight, 2) }} lbs</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Estimated Value:</dt>
                                            <dd class="text-sm font-medium text-gray-900">${{ number_format($selectedPackage->estimated_value ?? 0, 2) }} USD</dd>
                                        </div>
                                        @if($selectedPackage->shipper)
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Shipper:</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{ $selectedPackage->shipper->name }}</dd>
                                        </div>
                                        @endif
                                        @if($selectedPackage->office)
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Office:</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{ $selectedPackage->office->name }}</dd>
                                        </div>
                                        @endif
                                        @if($selectedPackage->container_type)
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Container Type:</dt>
                                            <dd class="text-sm font-medium text-gray-900 capitalize">{{ $selectedPackage->container_type }}</dd>
                                        </div>
                                        @endif
                                        @if($selectedPackage->cubic_feet)
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Cubic Feet:</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{ number_format($selectedPackage->cubic_feet, 3) }} ft³</dd>
                                        </div>
                                        @endif
                                    </dl>
                                </div>

                                {{-- Cost Breakdown --}}
                                @php
                                    $currentUser = auth()->user();
                                    $packageStatus = $selectedPackage->status_value;
                                    $canSeeCosts = $currentUser->isSuperAdmin() || $currentUser->isAdmin() || 
                                                  ($currentUser->isCustomer() && in_array($packageStatus, ['ready', 'delivered']));
                                @endphp
                                @if($canSeeCosts)
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="text-sm font-medium text-gray-900 mb-3">Cost Breakdown</h4>
                                    <dl class="space-y-2">
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Freight:</dt>
                                            <dd class="text-sm font-medium text-gray-900">${{ number_format($selectedPackage->freight_price ?? 0, 2) }} JMD</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Clearance Fee:</dt>
                                            <dd class="text-sm font-medium text-gray-900">${{ number_format($selectedPackage->clearance_fee ?? 0, 2) }} JMD</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Storage Fee:</dt>
                                            <dd class="text-sm font-medium text-gray-900">${{ number_format($selectedPackage->storage_fee ?? 0, 2) }} JMD</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Delivery Fee:</dt>
                                            <dd class="text-sm font-medium text-gray-900">${{ number_format($selectedPackage->delivery_fee ?? 0, 2) }} JMD</dd>
                                        </div>
                                        <div class="flex justify-between pt-2 border-t border-gray-200">
                                            <dt class="text-sm font-medium text-gray-900">Total Cost:</dt>
                                            <dd class="text-sm font-bold text-gray-900">${{ number_format($selectedPackage->total_cost, 2) }} JMD</dd>
                                        </div>
                                    </dl>
                                </div>
                                @else
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="text-sm font-medium text-gray-900 mb-3">Cost Information</h4>
                                    <p class="text-sm text-gray-600">Cost details will be available when your package is ready for pickup.</p>
                                </div>
                                @endif

                                {{-- Package Items (if sea package) --}}
                                @if($selectedPackage->items && $selectedPackage->items->count() > 0)
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="text-sm font-medium text-gray-900 mb-3">Package Items</h4>
                                    <div class="space-y-2">
                                        @foreach($selectedPackage->items as $item)
                                            <div class="bg-white rounded p-3 border border-gray-200">
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900">{{ $item->description }}</p>
                                                        <p class="text-xs text-gray-500">Quantity: {{ $item->quantity }}</p>
                                                    </div>
                                                    @if($item->weight_per_item)
                                                    <div class="text-right">
                                                        <p class="text-xs text-gray-500">{{ number_format($item->weight_per_item, 2) }} lbs each</p>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                                </div>
                            @endif

                            {{-- Footer --}}
                            <div class="mt-6 flex justify-end">
                                <button wire:click="closeModal" 
                                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>