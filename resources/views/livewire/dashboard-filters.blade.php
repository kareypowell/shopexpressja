<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    {{-- Loading State --}}
    <div wire:loading.delay class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10 rounded-lg">
        <div class="flex items-center space-x-2">
            <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-700">Applying filters...</span>
        </div>
    </div>

    {{-- Filter Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
            <h3 class="text-lg font-semibold text-gray-900">Dashboard Filters</h3>
            @if($activeFiltersCount > 0)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ $activeFiltersCount }} {{ $activeFiltersCount === 1 ? 'filter' : 'filters' }} active
                </span>
            @endif
        </div>
        
        <div class="flex items-center space-x-3">
            @if($activeFiltersCount > 0)
                <button wire:click="resetAllFilters" 
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Reset Filters
                </button>
            @endif
            
            <button wire:click="toggleAdvancedFilters" 
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                </svg>
                {{ $showAdvancedFilters ? 'Hide' : 'Show' }} Advanced
            </button>
        </div>
    </div>

    {{-- Basic Filters --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Date Range Filter --}}
        <div>
            <label for="dateRange" class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
            <select wire:model="dateRange" id="dateRange" 
                    class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                @foreach($dateRangeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Custom Date Range --}}
        @if($dateRange === 'custom')
            <div>
                <label for="customStartDate" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                <input wire:model="customStartDate" type="date" id="customStartDate"
                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            
            <div>
                <label for="customEndDate" class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                <input wire:model="customEndDate" type="date" id="customEndDate"
                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        @endif

        {{-- Service Type Filter --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Service Types</label>
            <div class="space-y-2">
                @foreach($serviceTypes as $key => $label)
                    <label class="inline-flex items-center">
                        <input wire:model="selectedServiceTypes" type="checkbox" value="{{ $key }}"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Customer Type Filter --}}
        <div>
            <label for="customerType" class="block text-sm font-medium text-gray-700 mb-2">Customer Type</label>
            <select wire:model="customerType" id="customerType"
                    class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                <option value="all">All Customers</option>
                <option value="new">New Customers</option>
                <option value="returning">Returning Customers</option>
            </select>
        </div>
    </div>

    {{-- Advanced Filters --}}
    @if($showAdvancedFilters)
        <div class="border-t border-gray-200 pt-6">
            <h4 class="text-md font-medium text-gray-900 mb-4">Advanced Filters</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {{-- Customer Segments --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Customer Segments</label>
                    <div class="space-y-2 max-h-32 overflow-y-auto">
                        @foreach($customerSegments as $key => $label)
                            <label class="flex items-center">
                                <input wire:model="selectedCustomerSegments" type="checkbox" value="{{ $key }}"
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Package Status --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Package Status</label>
                    <div class="space-y-2 max-h-32 overflow-y-auto">
                        @foreach($packageStatuses as $key => $label)
                            <label class="flex items-center">
                                <input wire:model="selectedPackageStatuses" type="checkbox" value="{{ $key }}"
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Offices --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Offices</label>
                    <div class="space-y-2 max-h-32 overflow-y-auto">
                        @foreach($offices as $id => $name)
                            <label class="flex items-center">
                                <input wire:model="selectedOffices" type="checkbox" value="{{ $id }}"
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">{{ $name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Order Value Range --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Order Value Range</label>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="minOrderValue" class="block text-xs text-gray-500 mb-1">Minimum ($)</label>
                            <input wire:model="minOrderValue" type="number" id="minOrderValue" min="0" step="0.01"
                                   placeholder="0.00"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="maxOrderValue" class="block text-xs text-gray-500 mb-1">Maximum ($)</label>
                            <input wire:model="maxOrderValue" type="number" id="maxOrderValue" min="0" step="0.01"
                                   placeholder="No limit"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Active Filters Summary --}}
    @if($activeFiltersCount > 0)
        <div class="border-t border-gray-200 pt-4 mt-6">
            <h4 class="text-sm font-medium text-gray-900 mb-3">Active Filters</h4>
            <div class="flex flex-wrap gap-2">
                @foreach($activeFiltersSummary as $filter)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <span class="font-semibold mr-1">{{ $filter['label'] }}:</span>
                        <span>{{ $filter['value'] }}</span>
                        <button wire:click="removeFilter('{{ $filter['type'] }}')" 
                                class="ml-2 inline-flex items-center justify-center w-4 h-4 rounded-full text-blue-400 hover:bg-blue-200 hover:text-blue-600 focus:outline-none focus:bg-blue-200 focus:text-blue-600">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Filter Actions --}}
    <div class="border-t border-gray-200 pt-4 mt-6">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">
                @if($filtersApplied)
                    <span class="inline-flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Filters applied successfully
                    </span>
                @else
                    <span>Configure filters above to refine dashboard data</span>
                @endif
            </div>
            
            <div class="flex items-center space-x-3">
                <button wire:click="applyFilters" 
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z"></path>
                    </svg>
                    Apply Filters
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-apply filters when custom date range is complete
    document.addEventListener('livewire:load', function () {
        Livewire.on('filtersUpdated', function (filters) {
            // Show success message briefly
            const successMessage = document.querySelector('[data-filter-success]');
            if (successMessage) {
                successMessage.classList.remove('hidden');
                setTimeout(() => {
                    successMessage.classList.add('hidden');
                }, 3000);
            }
        });
    });
    
    // Handle keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + R to reset filters
        if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
            e.preventDefault();
            Livewire.emit('resetFilters');
        }
        
        // Ctrl/Cmd + F to focus on first filter
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            const firstFilter = document.querySelector('#dateRange');
            if (firstFilter) {
                firstFilter.focus();
            }
        }
    });
});
</script>
@endpush