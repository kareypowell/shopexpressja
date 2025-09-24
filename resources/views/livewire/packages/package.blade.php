<div>
    @include('livewire.quick-insights', [
        'inComingAir' => $inComingAir,
        'inComingSea' => $inComingSea,
        'availableAir' => $availableAir,
        'availableSea' => $availableSea,
        'accountBalance' => $accountBalance
    ])

    <hr class="my-10">

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

    <!-- Consolidated Packages Notification -->
    @if($this->consolidatedPackages->count() > 0 && !$showConsolidatedView)
        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-blue-800">
                            You have {{ $this->consolidatedPackages->count() }} consolidated package group{{ $this->consolidatedPackages->count() !== 1 ? 's' : '' }}
                        </p>
                        <p class="text-xs text-blue-600">
                            Some of your packages have been grouped together for easier processing and potentially lower costs.
                        </p>
                    </div>
                </div>
                <button wire:click="forceConsolidatedView" 
                        class="inline-flex items-center px-3 py-1.5 border border-blue-300 shadow-sm text-xs font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    View Consolidated Packages
                </button>
            </div>
        </div>
    @endif

    <div class="mt-10">
        <!-- Search and Filter Controls -->
        <div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0 md:space-x-4">
                <!-- Search Input -->
                <div class="flex-1 max-w-md">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" 
                               wire:model.debounce.300ms="search" 
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="Search by tracking number, description, or consolidated tracking...">
                    </div>
                    @if(!empty($search))
                        <div class="mt-2 text-xs text-gray-600">
                            <span class="inline-flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Searching in both individual and consolidated packages
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Status Filter -->
                <div class="flex items-center space-x-4">
                    <div class="min-w-0 flex-1 md:flex-none md:w-48">
                        <select wire:model="statusFilter" 
                                class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="">All Statuses</option>
                            @foreach($this->availableStatuses as $status)
                                <option value="{{ $status['value'] }}">{{ $status['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Clear Filters -->
                    @if(!empty($search) || !empty($statusFilter))
                        <button wire:click="clearSearch" 
                                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Clear
                        </button>
                    @endif
                </div>
            </div>

            <!-- Search Results Summary -->
            @if($showSearchResults)
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            @php
                                $individualCount = $this->individualPackages->count();
                                $consolidatedCount = $this->consolidatedPackages->count();
                                $totalIndividualInConsolidated = $this->consolidatedPackages->sum(function($cp) { return $cp->packages->count(); });
                            @endphp
                            
                            <span class="font-medium">Search Results:</span>
                            @if($individualCount > 0)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 ml-2">
                                    {{ $individualCount }} Individual Package{{ $individualCount !== 1 ? 's' : '' }}
                                </span>
                            @endif
                            @if($consolidatedCount > 0)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-2">
                                    {{ $consolidatedCount }} Consolidated Group{{ $consolidatedCount !== 1 ? 's' : '' }}
                                    ({{ $totalIndividualInConsolidated }} packages)
                                </span>
                            @endif
                            @if($individualCount === 0 && $consolidatedCount === 0)
                                <span class="text-gray-500 ml-2">No packages found matching your search criteria</span>
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

        <!-- Package Management Header -->
        <div class="flex justify-between items-center mb-5">
            <h3 class="text-base font-semibold text-gray-900">Packages</h3>
            
            <!-- Consolidation Controls -->
            <div class="flex items-center space-x-4">
                <!-- Consolidation Mode Toggle -->
                <div class="flex items-center">
                    <label class="inline-flex items-center">
                        <input type="checkbox" 
                               wire:model="consolidationMode" 
                               wire:click="toggleConsolidationMode"
                               class="form-checkbox h-4 w-4 text-blue-600">
                        <span class="ml-2 text-sm text-gray-700">Consolidation Mode</span>
                    </label>
                </div>

                <!-- View Toggle -->
                <div class="flex items-center">
                    <label class="inline-flex items-center">
                        <input type="checkbox" 
                               wire:model="showConsolidatedView" 
                               wire:click="toggleConsolidatedView"
                               class="form-checkbox h-4 w-4 text-green-600">
                        <span class="ml-2 text-sm text-gray-700">Show Consolidated</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Consolidation Mode Indicator -->
        @if($consolidationMode)
            <div class="mb-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-blue-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div>
                            <span class="text-sm font-semibold text-blue-900">
                                Consolidation Mode Active
                            </span>
                            @if($this->selectedPackagesCount > 0)
                                <div class="text-xs text-blue-700 mt-1">
                                    {{ $this->selectedPackagesCount }} package(s) selected for consolidation
                                </div>
                            @else
                                <div class="text-xs text-blue-700 mt-1">
                                    Select packages to consolidate them into a single group
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    @if($this->selectedPackagesCount > 0)
                        <div class="flex items-center space-x-2">
                            <button wire:click="clearSelectedPackages" 
                                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Clear
                            </button>
                            
                            @if($this->selectedPackagesCount >= 2)
                                <button wire:click="consolidateSelectedPackages" 
                                        class="inline-flex items-center px-4 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                    Consolidate Selected
                                </button>
                            @else
                                <span class="text-xs text-gray-500 italic">
                                    Select at least 2 packages to consolidate
                                </span>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Selection Summary -->
                @if($this->selectedPackagesCount > 0)
                    <div class="mt-4 p-3 bg-white rounded-md border border-blue-100">
                        <div class="text-xs font-medium text-gray-700 mb-2">Selection Summary:</div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                            <div class="text-center">
                                <div class="font-semibold text-blue-600">{{ $this->selectedPackagesCount }}</div>
                                <div class="text-gray-600">Packages</div>
                            </div>
                            @if($this->selectedPackagesCount > 0)
                                @php
                                    $selectedPackages = $this->availablePackagesForConsolidation->whereIn('id', $this->selectedPackagesForConsolidation);
                                    $totalWeight = $selectedPackages->sum('weight');
                                    $totalCost = $selectedPackages->sum(function($pkg) {
                                        return ($pkg->freight_price ?? 0) + ($pkg->clearance_fee ?? 0) + ($pkg->storage_fee ?? 0) + ($pkg->delivery_fee ?? 0);
                                    });
                                @endphp
                                <div class="text-center">
                                    <div class="font-semibold text-green-600">{{ number_format($totalWeight, 1) }}</div>
                                    <div class="text-gray-600">Total lbs</div>
                                </div>
                                <div class="text-center">
                                    <div class="font-semibold text-purple-600">${{ number_format($totalCost, 2) }}</div>
                                    <div class="text-gray-600">Total Cost</div>
                                </div>
                                <div class="text-center">
                                    <div class="font-semibold text-orange-600">{{ $selectedPackages->pluck('shipper.name')->unique()->count() }}</div>
                                    <div class="text-gray-600">Shippers</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Consolidation Notes -->
                @if($this->selectedPackagesCount >= 2)
                    <div class="mt-4">
                        <label class="block text-xs font-medium text-gray-700 mb-2">
                            <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Consolidation Notes (Optional)
                        </label>
                        <textarea wire:model="consolidationNotes" 
                                  rows="2" 
                                  class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 placeholder-gray-400"
                                  placeholder="Add any notes about this consolidation (e.g., special handling instructions, customer requests)..."></textarea>
                    </div>
                @endif
            </div>
        @endif

        <!-- Package Display -->
        @if($showConsolidatedView)
            <!-- Consolidated Packages View -->
            <div class="space-y-6">
                @if($this->consolidatedPackages->count() > 0)
                    <div>
                        <!-- Consolidated Packages Header with Summary -->
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                Consolidated Packages
                            </h4>
                            
                            <!-- Quick Stats -->
                            <div class="flex items-center space-x-4 text-sm">
                                <div class="bg-green-100 text-green-800 px-3 py-1 rounded-full">
                                    {{ $this->consolidatedPackages->count() }} Groups
                                </div>
                                <div class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full">
                                    {{ $this->consolidatedPackages->sum(function($cp) { return $cp->packages->count(); }) }} Total Packages
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            @foreach($this->consolidatedPackages as $consolidatedPackage)
                                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-400 rounded-lg shadow-sm overflow-hidden">
                                    <!-- Consolidated Package Header -->
                                    <div class="p-5">
                                        <div class="flex justify-between items-start mb-4">
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-3 mb-2">
                                                    <h5 class="text-lg font-semibold text-gray-900">
                                                        @if($this->hasSearchMatches($consolidatedPackage->id, 'consolidated'))
                                                            <x-search-highlight 
                                                                :text="$consolidatedPackage->consolidated_tracking_number" 
                                                                :search="$search ?? ''" 
                                                                :matches="$this->getPackageSearchMatches($consolidatedPackage->id, 'consolidated')" />
                                                        @else
                                                            {{ $consolidatedPackage->consolidated_tracking_number }}
                                                        @endif
                                                    </h5>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                                        </svg>
                                                        Consolidated
                                                    </span>
                                                    
                                                    <!-- Search Match Indicators for Consolidated Package -->
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
                                                
                                                <!-- Summary Stats -->
                                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-3">
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
                                                
                                                <div class="flex items-center space-x-4 text-sm text-gray-600">
                                                    <span class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                        Consolidated {{ $consolidatedPackage->consolidated_at->format('M d, Y') }}
                                                    </span>
                                                    @if($consolidatedPackage->createdBy)
                                                        <span class="flex items-center">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                            </svg>
                                                            by {{ $consolidatedPackage->createdBy->name }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center space-x-3">
                                                <x-package-status-badge :status="$consolidatedPackage->status" />
                                                
                                                <!-- History Button -->
                                                <button wire:click="showConsolidationHistory({{ $consolidatedPackage->id }})" 
                                                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    History
                                                </button>
                                                
                                                @if($consolidatedPackage->canBeUnconsolidated())
                                                    <button wire:click="unconsolidatePackage({{ $consolidatedPackage->id }})" 
                                                            class="inline-flex items-center px-3 py-1.5 border border-red-300 shadow-sm text-xs font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                                            onclick="return confirm('Are you sure you want to unconsolidate these packages? This will separate them back into individual packages.')">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                                        </svg>
                                                        Unconsolidate
                                                    </button>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Individual Packages in Consolidation -->
                                        <div class="border-t border-green-200 pt-4">
                                            <div class="flex items-center justify-between mb-3">
                                                <h6 class="text-sm font-medium text-gray-700 flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                                    </svg>
                                                    Individual Packages ({{ $consolidatedPackage->packages->count() }})
                                                </h6>
                                                <button type="button" 
                                                        onclick="document.getElementById('packages-{{ $consolidatedPackage->id }}').classList.toggle('hidden')"
                                                        class="text-xs text-blue-600 hover:text-blue-800 flex items-center">
                                                    <span class="toggle-text">Show Details</span>
                                                    <svg class="w-3 h-3 ml-1 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                            
                                            <div id="packages-{{ $consolidatedPackage->id }}" class="hidden">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                    @foreach($consolidatedPackage->packages as $package)
                                                        <div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm hover:shadow-md transition-shadow">
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
                                                                                    <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                                                                                    </svg>
                                                                                    Match
                                                                                </span>
                                                                            </div>
                                                                        @else
                                                                            {{ $package->tracking_number }}
                                                                        @endif
                                                                    </div>
                                                                    <div class="text-sm text-gray-600 space-y-1">
                                                                        @if($package->description)
                                                                            <div class="flex items-start">
                                                                                <svg class="w-3 h-3 mt-0.5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                                                                </svg>
                                                                                <span>{{ Str::limit($package->description, 40) }}</span>
                                                                            </div>
                                                                        @endif
                                                                        <div class="flex items-center">
                                                                            <svg class="w-3 h-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                                                                            </svg>
                                                                            <span>{{ $package->weight ?? 'N/A' }} lbs</span>
                                                                        </div>
                                                                        @if($package->shipper)
                                                                            <div class="flex items-center">
                                                                                <svg class="w-3 h-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                                                </svg>
                                                                                <span>{{ $package->shipper->name }}</span>
                                                                            </div>
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
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <p>No consolidated packages found.</p>
                    </div>
                @endif

                <!-- Individual Packages (if any remain) -->
                @if($this->individualPackages->count() > 0)
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Individual Packages</h4>
                        @include('livewire.packages.individual-packages-list', ['packages' => $this->individualPackages])
                    </div>
                @endif
            </div>
        @else
            <!-- Individual Packages View -->
            @if($consolidationMode)
                <!-- Show packages available for consolidation -->
                @if($this->availablePackagesForConsolidation->count() > 0)
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Available for Consolidation</h4>
                        @include('livewire.packages.consolidation-packages-list', ['packages' => $this->availablePackagesForConsolidation])
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <p>No packages available for consolidation.</p>
                    </div>
                @endif
            @else
                <!-- Standard package view -->
                @if($showSearchResults || !empty($statusFilter))
                    <!-- Show filtered individual packages -->
                    @if($this->individualPackages->count() > 0)
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Individual Packages</h4>
                            @include('livewire.packages.individual-packages-list', ['packages' => $this->individualPackages])
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <p>No packages found matching your search criteria.</p>
                        </div>
                    @endif
                @else
                    <livewire:customers.customer-packages-with-modal :customer="auth()->user()" />
                @endif
            @endif
        @endif
    </div>

    <!-- Consolidation History Component -->
    @if($showHistoryModal && $selectedConsolidatedPackageForHistory)
        @livewire('consolidation-history', ['consolidatedPackage' => $selectedConsolidatedPackageForHistory], key('history-'.$selectedConsolidatedPackageForHistory->id))
    @endif
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

        // Add smooth transitions for package selection
        document.querySelectorAll('[wire\\:click*="togglePackageSelection"]').forEach(element => {
            element.addEventListener('click', function() {
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            });
        });

        // Auto-scroll to consolidation summary when packages are selected
        window.addEventListener('livewire:load', function() {
            Livewire.hook('message.processed', (message, component) => {
                if (component.fingerprint.name === 'package') {
                    const selectedCount = component.data.selectedPackagesForConsolidation.length;
                    if (selectedCount > 0) {
                        const summaryElement = document.querySelector('.bg-gradient-to-r.from-blue-50');
                        if (summaryElement) {
                            summaryElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    }
                }
            });
        });
    });
</script>
@endpush