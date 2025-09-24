<div>
    <!-- Success/Error Messages -->
    @if($successMessage)
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ $successMessage }}
        </div>
    @endif

    @if($errorMessage)
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ $errorMessage }}
        </div>
    @endif

    <!-- Header -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Package Management</h2>
        <p class="mt-1 text-sm text-gray-600">Search and manage individual and consolidated packages</p>
    </div>

    <!-- Search and Filter Controls -->
    <div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <!-- Search Input -->
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Search Packages</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" 
                           wire:model.debounce.300ms="search" 
                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="Search by tracking number, description, customer name...">
                </div>
            </div>

            <!-- Package Type Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Package Type</label>
                <select wire:model="typeFilter" 
                        class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="all">All Packages</option>
                    <option value="individual">Individual Only</option>
                    <option value="consolidated">Consolidated Only</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select wire:model="statusFilter" 
                        class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="">All Statuses</option>
                    @foreach($this->availableStatuses as $status)
                        <option value="{{ $status['value'] }}">{{ $status['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Customer Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Customer</label>
                <select wire:model="customerFilter" 
                        class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="">All Customers</option>
                    @foreach($this->availableCustomers as $customer)
                        <option value="{{ $customer['value'] }}">{{ $customer['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Clear Filters -->
            <div class="flex items-end">
                @if(!empty($search) || !empty($statusFilter) || !empty($customerFilter) || $typeFilter !== 'all')
                    <button wire:click="clearSearch" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Clear All Filters
                    </button>
                @endif
            </div>
        </div>

        <!-- Search Results Summary -->
        @if($showSearchResults || !empty($statusFilter) || !empty($customerFilter) || $typeFilter !== 'all')
            <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        @php $summary = $this->searchSummary; @endphp
                        
                        <span class="font-medium">Results:</span>
                        @if($typeFilter !== 'consolidated' && $summary['individual_count'] > 0)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 ml-2">
                                {{ $summary['individual_count'] }} Individual Package{{ $summary['individual_count'] !== 1 ? 's' : '' }}
                            </span>
                        @endif
                        @if($typeFilter !== 'individual' && $summary['consolidated_count'] > 0)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-2">
                                {{ $summary['consolidated_count'] }} Consolidated Group{{ $summary['consolidated_count'] !== 1 ? 's' : '' }}
                                ({{ $summary['total_individual_in_consolidated'] }} packages)
                            </span>
                        @endif
                        @if($summary['individual_count'] === 0 && $summary['consolidated_count'] === 0)
                            <span class="text-gray-500 ml-2">No packages found matching your criteria</span>
                        @endif
                    </div>
                    
                    <!-- Search Match Indicators -->
                    @if(!empty($searchMatches))
                        <div class="text-xs text-gray-500">
                            <span class="inline-flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                                </svg>
                                Matches highlighted below
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- Package Results -->
    <div class="space-y-6">
        <!-- Consolidated Packages -->
        @if($typeFilter !== 'individual' && $this->consolidatedPackages->count() > 0)
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        Consolidated Packages
                    </h3>
                    <div class="text-sm text-gray-500">
                        {{ $this->consolidatedPackages->total() }} total
                    </div>
                </div>

                <div class="space-y-4">
                    @foreach($this->consolidatedPackages as $consolidatedPackage)
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-400 rounded-lg shadow-sm overflow-hidden">
                            <div class="p-5">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <h4 class="text-lg font-semibold text-gray-900">
                                                @if($this->hasSearchMatches($consolidatedPackage->id, 'consolidated'))
                                                    <x-search-highlight 
                                                        :text="$consolidatedPackage->consolidated_tracking_number" 
                                                        :search="$search ?? ''" 
                                                        :matches="$this->getPackageSearchMatches($consolidatedPackage->id, 'consolidated')" />
                                                @else
                                                    {{ $consolidatedPackage->consolidated_tracking_number }}
                                                @endif
                                            </h4>
                                            <x-package-status-badge :status="$consolidatedPackage->status" />
                                            
                                            <!-- Search Match Indicators -->
                                            @if($this->hasSearchMatches($consolidatedPackage->id, 'consolidated'))
                                                <div class="flex items-center space-x-1">
                                                    @foreach($this->getPackageSearchMatches($consolidatedPackage->id, 'consolidated') as $match)
                                                        @if($match['type'] === 'exact')
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                                <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                Consolidated #
                                                            </span>
                                                        @elseif($match['type'] === 'individual_package')
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                Individual Package
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <!-- Customer Info -->
                                        <div class="text-sm text-gray-600 mb-3">
                                            <span class="font-medium">Customer:</span>
                                            {{ $consolidatedPackage->customer->full_name }}
                                            @if($consolidatedPackage->customer->profile && $consolidatedPackage->customer->profile->account_number)
                                                ({{ $consolidatedPackage->customer->profile->account_number }})
                                            @endif
                                        </div>
                                        
                                        <!-- Summary Stats -->
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                            <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                                                <div class="text-lg font-bold text-blue-600">{{ $consolidatedPackage->packages->count() }}</div>
                                                <div class="text-xs text-gray-600">Packages</div>
                                            </div>
                                            <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                                                <div class="text-lg font-bold text-green-600">{{ number_format($consolidatedPackage->total_weight, 1) }}</div>
                                                <div class="text-xs text-gray-600">Total lbs</div>
                                            </div>
                                            <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                                                <div class="text-lg font-bold text-purple-600">${{ number_format($consolidatedPackage->total_cost, 2) }}</div>
                                                <div class="text-xs text-gray-600">Total Cost</div>
                                            </div>
                                            <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                                                <div class="text-lg font-bold text-orange-600">{{ $consolidatedPackage->packages->pluck('shipper.name')->unique()->count() }}</div>
                                                <div class="text-xs text-gray-600">Shippers</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Individual Packages in Consolidation -->
                                <div class="border-t border-green-200 pt-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <h5 class="text-sm font-medium text-gray-700">Individual Packages ({{ $consolidatedPackage->packages->count() }})</h5>
                                        <button type="button" 
                                                onclick="document.getElementById('admin-packages-{{ $consolidatedPackage->id }}').classList.toggle('hidden')"
                                                class="text-xs text-blue-600 hover:text-blue-800 flex items-center">
                                            <span class="toggle-text">Show Details</span>
                                            <svg class="w-3 h-3 ml-1 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    <div id="admin-packages-{{ $consolidatedPackage->id }}" class="hidden">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            @foreach($consolidatedPackage->packages as $package)
                                                <div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm">
                                                    <div class="flex justify-between items-start">
                                                        <div class="flex-1">
                                                            <div class="font-medium text-gray-900 mb-1">
                                                                @php
                                                                    $packageMatches = collect($this->getPackageSearchMatches($consolidatedPackage->id, 'consolidated'))
                                                                        ->where('package_id', $package->id);
                                                                @endphp
                                                                @if($packageMatches->isNotEmpty())
                                                                    <div class="flex items-center space-x-2">
                                                                        <x-search-highlight 
                                                                            :text="$package->tracking_number" 
                                                                            :search="$search ?? ''" 
                                                                            :matches="$packageMatches->toArray()" />
                                                                        <span class="inline-flex items-center px-1 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                            Match
                                                                        </span>
                                                                    </div>
                                                                @else
                                                                    {{ $package->tracking_number }}
                                                                @endif
                                                            </div>
                                                            <div class="text-sm text-gray-600 space-y-1">
                                                                @if($package->description)
                                                                    <div>{{ Str::limit($package->description, 40) }}</div>
                                                                @endif
                                                                <div>{{ $package->weight ?? 'N/A' }} lbs</div>
                                                                @if($package->shipper)
                                                                    <div>{{ $package->shipper->name }}</div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="text-right ml-3">
                                                            @php
                                                                $packageCost = ($package->freight_price ?? 0) + ($package->clearance_fee ?? 0) + ($package->storage_fee ?? 0) + ($package->delivery_fee ?? 0);
                                                            @endphp
                                                            @if($packageCost > 0)
                                                                <div class="text-sm font-medium text-gray-900">${{ number_format($packageCost, 2) }}</div>
                                                            @endif
                                                            <div class="text-xs text-gray-500">{{ $package->created_at->format('M d') }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination for Consolidated Packages -->
                <div class="mt-4">
                    {{ $this->consolidatedPackages->links() }}
                </div>
            </div>
        @endif

        <!-- Individual Packages -->
        @if($typeFilter !== 'consolidated' && $this->individualPackages->count() > 0)
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                        </svg>
                        Individual Packages
                    </h3>
                    <div class="text-sm text-gray-500">
                        {{ $this->individualPackages->total() }} total
                    </div>
                </div>

                <div class="space-y-3">
                    @foreach($this->individualPackages as $package)
                        <div class="border border-gray-200 rounded-lg p-4 bg-white hover:bg-gray-50 transition-colors duration-150 shadow-sm">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h4 class="font-semibold text-gray-900">
                                            @if($this->hasSearchMatches($package->id, 'individual'))
                                                <x-search-highlight 
                                                    :text="$package->tracking_number" 
                                                    :search="$search ?? ''" 
                                                    :matches="$this->getPackageSearchMatches($package->id, 'individual')" />
                                            @else
                                                {{ $package->tracking_number }}
                                            @endif
                                        </h4>
                                        <x-package-status-badge :status="$package->status" />
                                        
                                        <!-- Search Match Indicators -->
                                        @if($this->hasSearchMatches($package->id, 'individual'))
                                            <div class="flex items-center space-x-1">
                                                @foreach($this->getPackageSearchMatches($package->id, 'individual') as $match)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                                                        </svg>
                                                        {{ ucfirst(str_replace('_', ' ', $match['field'])) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <!-- Customer Info -->
                                    <div class="text-sm text-gray-600 mb-2">
                                        <span class="font-medium">Customer:</span>
                                        {{ $package->user->full_name }}
                                        @if($package->user->profile && $package->user->profile->account_number)
                                            ({{ $package->user->profile->account_number }})
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
                                                        <span>Customs:</span>
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
                </div>

                <!-- Pagination for Individual Packages -->
                <div class="mt-4">
                    {{ $this->individualPackages->links() }}
                </div>
            </div>
        @endif

        <!-- No Results -->
        @if($this->individualPackages->count() === 0 && $this->consolidatedPackages->count() === 0)
            <div class="text-center py-12 text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 009.586 13H7"></path>
                </svg>
                <p class="text-lg font-medium">No packages found</p>
                <p class="text-sm mt-1">Try adjusting your search criteria or filters.</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Enhanced toggle functionality for consolidated package details
        document.querySelectorAll('[onclick*="toggle"]').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('onclick').match(/getElementById\('([^']+)'\)/)[1];
                const target = document.getElementById(targetId);
                const toggleText = this.querySelector('.toggle-text');
                const toggleIcon = this.querySelector('svg');
                
                if (target) {
                    target.classList.toggle('hidden');
                    
                    if (target.classList.contains('hidden')) {
                        toggleText.textContent = 'Show Details';
                        toggleIcon.style.transform = 'rotate(0deg)';
                    } else {
                        toggleText.textContent = 'Hide Details';
                        toggleIcon.style.transform = 'rotate(180deg)';
                    }
                }
            });
        });
    });
</script>
@endpush