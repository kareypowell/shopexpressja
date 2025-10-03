<div>
    <!-- Loading Overlay -->
    @if($bulkActionInProgress)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <x-loading-spinner size="lg" :text="$loadingMessage" />
            </div>
        </div>
    @endif

    <!-- Advanced Search Interface -->
    @if($advancedFilters)
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg mb-4 p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Advanced Search</h3>
                <div class="flex items-center space-x-2">
                    <button 
                        wire:click="toggleSearchPerformanceMode"
                        class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-shiraz-500"
                        title="{{ $searchPerformanceMode ? 'Disable' : 'Enable' }} performance mode"
                    >
                        @if($searchPerformanceMode)
                            <svg class="w-3 h-3 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        @else
                            <svg class="w-3 h-3 mr-1 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        @endif
                        Performance Mode
                    </button>
                    <button 
                        wire:click="toggleAdvancedFilters"
                        class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-shiraz-500"
                    >
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Close
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Name Search -->
                <div>
                    <label for="search-name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input 
                        type="text" 
                        id="search-name"
                        wire:model.debounce.300ms="advancedSearchCriteria.name"
                        placeholder="First or last name..."
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-shiraz-500 focus:border-shiraz-500 sm:text-sm"
                    >
                </div>

                <!-- Email Search -->
                <div>
                    <label for="search-email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input 
                        type="email" 
                        id="search-email"
                        wire:model.debounce.300ms="advancedSearchCriteria.email"
                        placeholder="Email address..."
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-shiraz-500 focus:border-shiraz-500 sm:text-sm"
                    >
                </div>

                <!-- Account Number Search -->
                <div>
                    <label for="search-account" class="block text-sm font-medium text-gray-700 mb-1">Account Number</label>
                    <input 
                        type="text" 
                        id="search-account"
                        wire:model.debounce.300ms="advancedSearchCriteria.account_number"
                        placeholder="Account number..."
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-shiraz-500 focus:border-shiraz-500 sm:text-sm"
                    >
                </div>

                <!-- Tax Number Search -->
                <div>
                    <label for="search-tax" class="block text-sm font-medium text-gray-700 mb-1">TRN</label>
                    <input 
                        type="text" 
                        id="search-tax"
                        wire:model.debounce.300ms="advancedSearchCriteria.tax_number"
                        placeholder="Tax registration number..."
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-shiraz-500 focus:border-shiraz-500 sm:text-sm"
                    >
                </div>

                <!-- Phone Number Search -->
                <div>
                    <label for="search-phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input 
                        type="text" 
                        id="search-phone"
                        wire:model.debounce.300ms="advancedSearchCriteria.telephone_number"
                        placeholder="Phone number..."
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-shiraz-500 focus:border-shiraz-500 sm:text-sm"
                    >
                </div>

                <!-- Parish Filter -->
                <div>
                    <label for="search-parish" class="block text-sm font-medium text-gray-700 mb-1">Parish</label>
                    <select 
                        id="search-parish"
                        wire:model="advancedSearchCriteria.parish"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-shiraz-500 focus:border-shiraz-500 sm:text-sm"
                    >
                        <option value="">All Parishes</option>
                        <option value="Clarendon">Clarendon</option>
                        <option value="Hanover">Hanover</option>
                        <option value="Kingston">Kingston</option>
                        <option value="Manchester">Manchester</option>
                        <option value="Portland">Portland</option>
                        <option value="St. Andrew">St. Andrew</option>
                        <option value="St. Ann">St. Ann</option>
                        <option value="St. Catherine">St. Catherine</option>
                        <option value="St. Elizabeth">St. Elizabeth</option>
                        <option value="St. James">St. James</option>
                        <option value="St. Mary">St. Mary</option>
                        <option value="St. Thomas">St. Thomas</option>
                        <option value="Trelawny">Trelawny</option>
                        <option value="Westmoreland">Westmoreland</option>
                    </select>
                </div>

                <!-- Address Search -->
                <div>
                    <label for="search-address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <input 
                        type="text" 
                        id="search-address"
                        wire:model.debounce.300ms="advancedSearchCriteria.address"
                        placeholder="Street address or city..."
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-shiraz-500 focus:border-shiraz-500 sm:text-sm"
                    >
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="search-status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select 
                        id="search-status"
                        wire:model="advancedSearchCriteria.status"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-shiraz-500 focus:border-shiraz-500 sm:text-sm"
                    >
                        <option value="active">Active</option>
                        <option value="deleted">Deleted</option>
                        <option value="all">All</option>
                    </select>
                </div>

                <!-- Registration Date From -->
                <div>
                    <label for="search-date-from" class="block text-sm font-medium text-gray-700 mb-1">Registered From</label>
                    <input 
                        type="date" 
                        id="search-date-from"
                        wire:model="advancedSearchCriteria.registration_date_from"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-shiraz-500 focus:border-shiraz-500 sm:text-sm"
                    >
                </div>

                <!-- Registration Date To -->
                <div>
                    <label for="search-date-to" class="block text-sm font-medium text-gray-700 mb-1">Registered To</label>
                    <input 
                        type="date" 
                        id="search-date-to"
                        wire:model="advancedSearchCriteria.registration_date_to"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-shiraz-500 focus:border-shiraz-500 sm:text-sm"
                    >
                </div>
            </div>

            <!-- Search Actions -->
            <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200">
                <div class="flex items-center space-x-4">
                    <button 
                        wire:click="applyAdvancedSearch"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-shiraz-600 hover:bg-shiraz-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-shiraz-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Apply Search
                    </button>
                    <button 
                        wire:click="clearAdvancedSearch"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-shiraz-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Clear All
                    </button>
                    <button 
                        wire:click="clearSavedFilterState"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-shiraz-500"
                        title="Clear saved filter preferences"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Reset
                    </button>
                    <button 
                        wire:click="exportSearchResults"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-shiraz-500"
                        title="Export current search results"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export
                    </button>
                </div>

                <!-- Search Statistics -->
                @if($hasAdvancedSearchCriteria() || !empty($searchHighlight))
                    <div class="text-sm text-gray-500">
                        @php $stats = $this->getSearchStats(); @endphp
                        {{ $stats['total_results'] }} result{{ $stats['total_results'] !== 1 ? 's' : '' }} found
                        @if($stats['search_term'])
                            for "{{ $stats['search_term'] }}"
                        @endif
                    </div>
                @endif
            </div>

            <!-- Active Filters Summary -->
            @if($hasAdvancedSearchCriteria())
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-medium text-gray-500">Active Filters:</span>
                            @foreach($this->getFilterSummary() as $filter)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-shiraz-100 text-shiraz-800">
                                    {{ $filter }}
                                </span>
                            @endforeach
                        </div>
                        <button 
                            wire:click="clearAdvancedSearch"
                            class="text-xs text-gray-400 hover:text-gray-600"
                        >
                            Clear all filters
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @else
        <!-- Quick Advanced Search Toggle -->
        <div class="mb-4 flex justify-between items-center">
            <button 
                wire:click="toggleAdvancedFilters"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-shiraz-500"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                </svg>
                Advanced Search
            </button>

            @if(!empty($searchHighlight))
                <div class="text-sm text-gray-500">
                    @php $stats = $this->getSearchStats(); @endphp
                    {{ $stats['total_results'] }} result{{ $stats['total_results'] !== 1 ? 's' : '' }} found for "{{ $stats['search_term'] }}"
                    <button 
                        wire:click="clearAllFilters"
                        class="ml-2 text-shiraz-600 hover:text-shiraz-800"
                    >
                        Clear
                    </button>
                </div>
            @endif
        </div>
    @endif

    {!! $table !!}

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full" style="z-index: 9999;" id="delete-modal">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.962-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mt-2">Delete Customer</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">
                            Are you sure you want to delete <strong>{{ $customerToDelete?->full_name }}</strong>? 
                            This action will soft delete the customer and they will no longer be able to log in.
                        </p>
                    </div>
                    <div class="items-center px-4 py-3">
                        <button wire:click="deleteCustomer" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300">
                            Delete
                        </button>
                        <button wire:click="cancelDelete" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Restore Confirmation Modal -->
    @if($showRestoreModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="restore-modal">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mt-2">Restore Customer</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">
                            Are you sure you want to restore <strong>{{ $customerToRestore?->full_name }}</strong>? 
                            This will reactivate their account and allow them to log in again.
                        </p>
                    </div>
                    <div class="items-center px-4 py-3">
                        <button wire:click="restoreCustomer" class="px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-300">
                            Restore
                        </button>
                        <button wire:click="cancelRestore" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded z-50" role="alert">
            <span class="block sm:inline">{!! session('message') !!}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50" role="alert">
            <span class="block sm:inline">{!! session('error') !!}</span>
        </div>
    @endif
</div>