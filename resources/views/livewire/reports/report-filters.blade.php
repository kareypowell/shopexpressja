<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Report Filters</h3>
            <p class="text-sm text-gray-600">
                Customize your report data with filters
                @if($activeFiltersCount > 0)
                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ $activeFiltersCount }} active
                    </span>
                @endif
            </p>
        </div>
        
        <div class="flex items-center space-x-3">
            {{-- Saved Filters Dropdown --}}
            @if(!empty($savedFilters))
                <div class="relative" x-data="{ open: false }">
                    <button 
                        @click="open = !open"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                        </svg>
                        Saved Filters
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    
                    <div 
                        x-show="open" 
                        @click.away="open = false"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-lg shadow-lg z-10"
                    >
                        <div class="py-1">
                            @foreach($savedFilters as $filterId => $filterName)
                                <div class="flex items-center justify-between px-4 py-2 hover:bg-gray-50">
                                    <button 
                                        wire:click="loadSavedFilter({{ $filterId }})"
                                        class="flex-1 text-left text-sm text-gray-700 hover:text-gray-900"
                                    >
                                        {{ $filterName }}
                                    </button>
                                    <button 
                                        wire:click="deleteSavedFilter({{ $filterId }})"
                                        class="ml-2 text-red-600 hover:text-red-800"
                                        onclick="return confirm('Delete this saved filter?')"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
            
            {{-- Save Current Filters --}}
            <button 
                wire:click="$set('showSaveDialog', true)"
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                </svg>
                Save Filters
            </button>
            
            {{-- Advanced Filters Toggle --}}
            <button 
                wire:click="toggleAdvancedFilters"
                class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ $showAdvancedFilters ? 'bg-blue-100 text-blue-800' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50' }}"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                </svg>
                Advanced
            </button>
            
            {{-- Reset Filters --}}
            <button 
                wire:click="resetAllFilters"
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Reset
            </button>
        </div>
    </div>

    {{-- Active Filters Summary --}}
    @if(!empty($activeFiltersSummary))
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <h4 class="text-sm font-medium text-blue-900">Active Filters</h4>
                <button 
                    wire:click="resetAllFilters"
                    class="text-xs text-blue-700 hover:text-blue-900"
                >
                    Clear All
                </button>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($activeFiltersSummary as $filter)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <span class="font-semibold mr-1">{{ $filter['label'] }}:</span>
                        {{ $filter['value'] }}
                        <button 
                            wire:click="removeFilter('{{ $filter['type'] }}')"
                            class="ml-2 text-blue-600 hover:text-blue-800"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Basic Filters --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        {{-- Date Range Filter --}}
        @if($this->supportsFilter('date_range'))
            <div>
                <label for="dateRange" class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                <select 
                    wire:model="dateRange" 
                    id="dateRange"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    @foreach($dateRangeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                
                {{-- Custom Date Range --}}
                @if($dateRange === 'custom')
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <div>
                            <label for="customStartDate" class="block text-xs font-medium text-gray-600 mb-1">Start Date</label>
                            <input 
                                wire:model="customStartDate" 
                                type="date" 
                                id="customStartDate"
                                class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                        </div>
                        <div>
                            <label for="customEndDate" class="block text-xs font-medium text-gray-600 mb-1">End Date</label>
                            <input 
                                wire:model="customEndDate" 
                                type="date" 
                                id="customEndDate"
                                class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Manifest Types Filter --}}
        @if($this->supportsFilter('manifest_types'))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Manifest Types</label>
                <div class="space-y-2">
                    @foreach($manifestTypes as $value => $label)
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                wire:model="selectedManifestTypes" 
                                value="{{ $value }}"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >
                            <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Offices Filter --}}
        @if($this->supportsFilter('offices') && !empty($offices))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Offices</label>
                <div class="max-h-32 overflow-y-auto space-y-2 border border-gray-200 rounded-lg p-2">
                    @foreach($offices as $officeId => $officeName)
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                wire:model="selectedOffices" 
                                value="{{ $officeId }}"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >
                            <span class="ml-2 text-sm text-gray-700">{{ $officeName }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Customer Search --}}
        @if($this->supportsFilter('customer_search'))
            <div>
                <label for="customerSearch" class="block text-sm font-medium text-gray-700 mb-2">Customer Search</label>
                <input 
                    wire:model.debounce.500ms="customerSearch" 
                    type="text" 
                    id="customerSearch"
                    placeholder="Search by name, email, or account..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>
        @endif
    </div>

    {{-- Advanced Filters --}}
    @if($showAdvancedFilters)
        <div class="border-t border-gray-200 pt-6">
            <h4 class="text-md font-medium text-gray-900 mb-4">Advanced Filters</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Customer Type Filter --}}
                @if($this->supportsFilter('customer_type'))
                    <div>
                        <label for="customerType" class="block text-sm font-medium text-gray-700 mb-2">Customer Type</label>
                        <select 
                            wire:model="customerType" 
                            id="customerType"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="all">All Customers</option>
                            <option value="new">New Customers</option>
                            <option value="returning">Returning Customers</option>
                            <option value="premium">Premium Customers</option>
                        </select>
                    </div>
                @endif

                {{-- Amount Range Filter --}}
                @if($this->supportsFilter('amount_range'))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Amount Range</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input 
                                wire:model.debounce.500ms="minAmount" 
                                type="number" 
                                placeholder="Min amount"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                            <input 
                                wire:model.debounce.500ms="maxAmount" 
                                type="number" 
                                placeholder="Max amount"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>
                    </div>
                @endif

                {{-- Payment Status Filter --}}
                @if($this->supportsFilter('payment_status'))
                    <div>
                        <label for="paymentStatus" class="block text-sm font-medium text-gray-700 mb-2">Payment Status</label>
                        <select 
                            wire:model="paymentStatus" 
                            id="paymentStatus"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="all">All Statuses</option>
                            <option value="paid">Paid</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="partial">Partially Paid</option>
                        </select>
                    </div>
                @endif

                {{-- Package Status Filter --}}
                @if($this->supportsFilter('package_statuses') && !empty($packageStatuses))
                    <div class="md:col-span-2 lg:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Package Status</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            @foreach($packageStatuses as $value => $label)
                                <label class="flex items-center">
                                    <input 
                                        type="checkbox" 
                                        wire:model="selectedPackageStatuses" 
                                        value="{{ $value }}"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    >
                                    <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Save Filter Dialog --}}
    @if($showSaveDialog)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 flex items-center justify-center">
            <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Save Filter Configuration</h3>
                
                <div class="mb-4">
                    <label for="newFilterName" class="block text-sm font-medium text-gray-700 mb-2">Filter Name</label>
                    <input 
                        wire:model="newFilterName" 
                        type="text" 
                        id="newFilterName"
                        placeholder="Enter a name for this filter..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button 
                        wire:click="$set('showSaveDialog', false)"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="saveCurrentFilters"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
                    >
                        Save Filter
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Loading Indicator --}}
    <div wire:loading.flex wire:target="applyFilters,loadSavedFilter" class="absolute inset-0 bg-white bg-opacity-75 items-center justify-center">
        <div class="flex items-center">
            <svg class="animate-spin h-5 w-5 text-blue-600 mr-3" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-900">Applying filters...</span>
        </div>
    </div>
</div>