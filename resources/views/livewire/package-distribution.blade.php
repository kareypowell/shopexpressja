<div>
    <!-- Flash Messages -->
    @if($successMessage)
        <div class="fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded z-50" role="alert">
            <div class="flex">
                <div class="py-1">
                    <svg class="fill-current h-6 w-6 text-green-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/>
                    </svg>
                </div>
                <div>
                    <span class="block sm:inline">{{ $successMessage }}</span>
                </div>
            </div>
        </div>
    @endif

    @if($errorMessage)
        <div class="fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50" role="alert">
            <div class="flex">
                <div class="py-1">
                    <svg class="fill-current h-6 w-6 text-red-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm1.41-1.41A8 8 0 1 0 15.66 4.34 8 8 0 0 0 4.34 15.66zm9.9-8.49L11.41 10l2.83 2.83-1.41 1.41L10 11.41l-2.83 2.83-1.41-1.41L8.59 10 5.76 7.17l1.41-1.41L10 8.59l2.83-2.83 1.41 1.41z"/>
                    </svg>
                </div>
                <div>
                    <span class="block sm:inline">{{ $errorMessage }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Package Distribution
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Distribute ready packages to customers with receipt generation
            </p>
        </div>
    </div>

    <!-- Customer Selection -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h4 class="text-lg font-medium text-gray-900">Select Customer</h4>
        </div>
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="customerSearch" class="block text-sm font-medium text-gray-700 mb-2">
                        Customer with Ready Packages
                    </label>
                    <div class="relative mt-1">
                        <div class="flex">
                            <input 
                                type="text" 
                                wire:model.debounce.300ms="customerSearch"
                                wire:focus="showAllCustomers"
                                id="customerSearch" 
                                placeholder="Search by name or account number..." 
                                autocomplete="off"
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-l-md placeholder-gray-400 focus:outline-none focus:ring-wax-flower-500 focus:border-wax-flower-500 transition duration-150 ease-in-out sm:text-sm sm:leading-5 @error('selectedCustomerId') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror"
                            >
                            @if($selectedCustomerId)
                                <button 
                                    type="button" 
                                    wire:click="clearCustomerSelection"
                                    class="px-3 py-2 border border-l-0 border-gray-300 rounded-r-md bg-gray-50 hover:bg-gray-100 focus:outline-none focus:ring-wax-flower-500 focus:border-wax-flower-500 transition duration-150 ease-in-out"
                                    title="Clear selection"
                                >
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            @else
                                <div class="px-3 py-2 border border-l-0 border-gray-300 rounded-r-md bg-gray-50">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Customer Dropdown Results -->
                        @if($showCustomerDropdown)
                            <div class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
                                @if($filteredCustomers->count() > 0)
                                    @foreach($filteredCustomers as $customer)
                                        <div 
                                            wire:click="selectCustomer({{ $customer->id }})"
                                            class="px-3 py-2 cursor-pointer hover:bg-wax-flower-50 hover:text-wax-flower-900 border-b border-gray-100 last:border-b-0"
                                        >
                                            <div class="font-medium">{{ $customer->full_name ?? $customer->name }}</div>
                                            <div class="text-sm text-gray-500">Account: {{ $customer->profile->account_number ?? 'N/A' }}</div>
                                        </div>
                                    @endforeach
                                    @if($filteredCustomers->count() == 10)
                                        <div class="px-3 py-2 text-sm text-gray-500 bg-gray-50 text-center">
                                            Showing first 10 results. Type more to narrow search.
                                        </div>
                                    @endif
                                @else
                                    <div class="px-3 py-2 text-sm text-gray-500 text-center">
                                        No customers found matching "{{ $customerSearch }}"
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                    
                    @if($selectedCustomerId && $selectedCustomerDisplay)
                        <div class="mt-2 text-sm text-green-600 bg-green-50 px-2 py-1 rounded">
                            ✓ Selected: {{ $selectedCustomerDisplay }}
                        </div>
                    @endif
                    
                    @error('selectedCustomerId')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if($selectedCustomerId)
                    <div>
                        <button 
                            wire:click="clearSelection"
                            type="button"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500 mt-6"
                        >
                            Clear Selection
                        </button>
                    </div>
                @endif
            </div>

            <!-- Selected Customer Info -->
            @if($selectedCustomer)
                <div class="mt-4 bg-gray-50 p-4 rounded-lg">
                    <h5 class="text-sm font-medium text-gray-900 mb-2">Customer Information</h5>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Name:</span>
                            <span class="ml-2 font-medium">{{ $selectedCustomer->full_name ?? $selectedCustomer->name }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Email:</span>
                            <span class="ml-2 font-medium">{{ $selectedCustomer->email }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Phone:</span>
                            <span class="ml-2 font-medium">{{ $selectedCustomer->profile->telephone_number ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Account:</span>
                            <span class="ml-2 font-medium">{{ $selectedCustomer->profile->account_number ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Packages Section -->
    @if($selectedCustomerId)
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg">
            <!-- Search and Filters -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h4 class="text-lg font-medium text-gray-900">Ready Packages</h4>
                    <div class="flex items-center space-x-4">
                        <!-- View Toggle -->
                        <div class="flex items-center space-x-2">
                            <button 
                                wire:click="toggleConsolidatedView"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md {{ $showConsolidatedView ? 'text-white bg-wax-flower-600 border-wax-flower-600' : 'text-gray-700 bg-white hover:bg-gray-50' }} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500"
                            >
                                @if($showConsolidatedView)
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                    Consolidated View
                                @else
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                    Individual View
                                @endif
                            </button>
                        </div>
                        
                        <div class="relative">
                            <input 
                                type="text" 
                                wire:model.debounce.300ms="search"
                                placeholder="{{ $showConsolidatedView ? 'Search consolidated packages...' : 'Search packages...' }}"
                                class="block w-64 border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                            >
                        </div>
                    </div>
                </div>
            </div>

            @if($showConsolidatedView)
                @if($consolidatedPackages->count() > 0)
                    <!-- Consolidated Package Selection -->
                    <div class="px-6 py-4">
                        @if(count($selectedConsolidatedPackages) > 0)
                            <div class="mb-4 bg-wax-flower-50 border border-wax-flower-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-wax-flower-800">
                                        {{ count($selectedConsolidatedPackages) }} consolidated package(s) selected for distribution
                                    </span>
                                    <button 
                                        wire:click="resetForm"
                                        class="text-sm text-wax-flower-600 hover:text-wax-flower-800"
                                    >
                                        Clear Selection
                                    </button>
                                </div>
                            </div>
                        @endif

                        <!-- Consolidated Packages Table -->
                        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">Select</span>
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Consolidated Tracking
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Package Count
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Total Weight
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Individual Packages
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Total Cost
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($consolidatedPackages as $consolidatedPackage)
                                        <tr class="hover:bg-gray-50">
                                            <td class="relative px-6 py-4 whitespace-nowrap">
                                                <input 
                                                    type="checkbox" 
                                                    wire:model="selectedConsolidatedPackages"
                                                    value="{{ $consolidatedPackage->id }}"
                                                    class="h-4 w-4 text-wax-flower-600 focus:ring-wax-flower-500 border-gray-300 rounded"
                                                >
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <x-badges.primary>{{ $consolidatedPackage->consolidated_tracking_number }}</x-badges.primary>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $consolidatedPackage->total_quantity }} packages
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ number_format($consolidatedPackage->total_weight, 2) }} lbs
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-xs text-gray-500 max-w-xs">
                                                    @foreach($consolidatedPackage->packages->take(3) as $package)
                                                        <span class="inline-block bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs mr-1 mb-1">
                                                            {{ $package->tracking_number }}
                                                        </span>
                                                    @endforeach
                                                    @if($consolidatedPackage->packages->count() > 3)
                                                        <span class="text-gray-400">+{{ $consolidatedPackage->packages->count() - 3 }} more</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <x-badges.success>${{ number_format($consolidatedPackage->total_cost, 2) }}</x-badges.success>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-4">
                            {{ $consolidatedPackages->links() }}
                        </div>
                    </div>
                @else
                    <div class="px-6 py-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No consolidated packages found</h3>
                        <p class="mt-1 text-sm text-gray-500">This customer has no consolidated packages ready for distribution.</p>
                    </div>
                @endif
            @else
                @if($packages->count() > 0)
                    <!-- Package Selection -->
                    <div class="px-6 py-4">
                        @if(count($selectedPackages) > 0)
                            <div class="mb-4 bg-wax-flower-50 border border-wax-flower-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-wax-flower-800">
                                        {{ count($selectedPackages) }} package(s) selected for distribution
                                    </span>
                                    <button 
                                        wire:click="resetForm"
                                        class="text-sm text-wax-flower-600 hover:text-wax-flower-800"
                                    >
                                        Clear Selection
                                    </button>
                                </div>
                            </div>
                        @endif

                    <!-- Packages Table -->
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Select</span>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tracking Number
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Description
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Weight
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Manifest
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total Cost
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($packages as $package)
                                    <tr class="hover:bg-gray-50">
                                        <td class="relative px-6 py-4 whitespace-nowrap">
                                            <input 
                                                type="checkbox" 
                                                wire:model="selectedPackages"
                                                value="{{ $package->id }}"
                                                class="h-4 w-4 text-wax-flower-600 focus:ring-wax-flower-500 border-gray-300 rounded"
                                            >
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <x-badges.primary>{{ $package->tracking_number }}</x-badges.primary>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                {{ $package->description ?: 'No description' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($package->weight, 2) }} lbs
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            @if($package->manifest)
                                                <div class="flex flex-col">
                                                    <x-badges.default>{{ $package->manifest->manifest_number }}</x-badges.default>
                                                    @if($package->manifest->name)
                                                        <span class="text-xs text-gray-500 mt-1">{{ $package->manifest->name }}</span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-gray-500">No manifest</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <x-badges.success>${{ number_format($package->total_cost, 2) }}</x-badges.success>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $packages->links() }}
                    </div>
                </div>

                @else
                    <div class="px-6 py-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No packages found</h3>
                        <p class="mt-1 text-sm text-gray-500">This customer has no individual packages ready for distribution.</p>
                    </div>
                @endif
            @endif

            <!-- Distribution Summary and Actions -->
            @if(($showConsolidatedView && count($selectedConsolidatedPackages) > 0) || (!$showConsolidatedView && count($selectedPackages) > 0))
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Amount Collection -->
                            <div>
                                <label for="amount-collected" class="block text-sm font-medium text-gray-700 mb-2">
                                    Amount Collected from Customer
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <input 
                                        type="number" 
                                        id="amount-collected"
                                        wire:model.lazy="amountCollected"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        class="block w-full pl-7 pr-12 border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                                    >
                                </div>
                                @error('amountCollected')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                
                                @if($amountCollected == 0)
                                    <p class="mt-1 text-xs text-gray-500">
                                        💡 Enter the amount collected from the customer to proceed with distribution.
                                    </p>
                                @endif

                                <!-- Advanced Options Toggle -->
                                <div class="mt-4">
                                    <button 
                                        type="button"
                                        wire:click="toggleAdvancedOptions"
                                        class="inline-flex items-center text-sm text-wax-flower-600 hover:text-wax-flower-800 font-medium"
                                    >
                                        <svg class="w-4 h-4 mr-1 transform {{ $showAdvancedOptions ? 'rotate-90' : '' }} transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                        {{ $showAdvancedOptions ? 'Hide' : 'Show' }} Advanced Options
                                    </button>
                                </div>

                                <!-- Advanced Options Panel -->
                                @if($showAdvancedOptions)
                                    <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg space-y-4">
                                        <!-- Apply Available Balance -->
                                        @if($this->customerTotalAvailableBalance > 0)
                                            <div class="space-y-3">
                                                <h5 class="text-sm font-medium text-gray-900">Apply Customer Balance</h5>
                                                
                                                @if($this->customerCreditBalance > 0)
                                                    <div class="flex items-center">
                                                        <input 
                                                            type="checkbox" 
                                                            id="apply-credit"
                                                            wire:model="applyCreditBalance"
                                                            class="h-4 w-4 text-wax-flower-600 focus:ring-wax-flower-500 border-gray-300 rounded"
                                                        >
                                                        <label for="apply-credit" class="ml-2 block text-sm text-gray-900">
                                                            Apply credit balance (${{ number_format($this->customerCreditBalance, 2) }})
                                                        </label>
                                                    </div>
                                                @endif
                                                
                                                @if($this->customerAccountBalance > 0)
                                                    <div class="flex items-center">
                                                        <input 
                                                            type="checkbox" 
                                                            id="apply-account"
                                                            wire:model="applyAccountBalance"
                                                            class="h-4 w-4 text-wax-flower-600 focus:ring-wax-flower-500 border-gray-300 rounded"
                                                        >
                                                        <label for="apply-account" class="ml-2 block text-sm text-gray-900">
                                                            Apply account balance (${{ number_format($this->customerAccountBalance, 2) }})
                                                        </label>
                                                    </div>
                                                @endif
                                                
                                                @if($this->customerCreditBalance > 0 && $this->customerAccountBalance > 0)
                                                    <div class="text-xs text-gray-500 pl-6">
                                                        Total available: ${{ number_format($this->customerTotalAvailableBalance, 2) }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endif

                                        <!-- Write-off/Discount -->
                                        <div>
                                            <label for="write-off-amount" class="block text-sm font-medium text-gray-700 mb-1">
                                                Write-off/Discount Amount
                                            </label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 sm:text-sm">$</span>
                                                </div>
                                                <input 
                                                    type="number" 
                                                    id="write-off-amount"
                                                    wire:model="writeOffAmount"
                                                    step="0.01"
                                                    min="0"
                                                    max="{{ $totalCost }}"
                                                    placeholder="0.00"
                                                    class="block w-full pl-7 pr-12 border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                                                >
                                            </div>
                                            @error('writeOffAmount')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- Write-off Reason -->
                                        @if($writeOffAmount > 0)
                                            <div>
                                                <label for="write-off-reason" class="block text-sm font-medium text-gray-700 mb-1">
                                                    Write-off Reason <span class="text-red-500">*</span>
                                                </label>
                                                <input 
                                                    type="text" 
                                                    id="write-off-reason"
                                                    wire:model="writeOffReason"
                                                    placeholder="e.g., Customer loyalty discount, damaged package compensation"
                                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                                                >
                                                @error('writeOffReason')
                                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        @endif

                                        <!-- Distribution Notes -->
                                        <div>
                                            <label for="distribution-notes" class="block text-sm font-medium text-gray-700 mb-1">
                                                Distribution Notes (Optional)
                                            </label>
                                            <textarea 
                                                id="distribution-notes"
                                                wire:model="distributionNotes"
                                                rows="2"
                                                placeholder="Add any notes about this distribution..."
                                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                                            ></textarea>
                                            @error('distributionNotes')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Cost Summary -->
                            <div class="bg-white p-4 rounded-lg border border-gray-200">
                                <h5 class="text-sm font-medium text-gray-900 mb-3">Distribution Summary</h5>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Selected Packages:</span>
                                        <span class="font-medium">{{ count($selectedPackages) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Original Total:</span>
                                        <span class="font-medium">${{ number_format($totalCost, 2) }}</span>
                                    </div>
                                    
                                    @if($writeOffAmount > 0)
                                        <div class="flex justify-between text-yellow-600">
                                            <span>Write-off/Discount:</span>
                                            <span class="font-medium">-${{ number_format($writeOffAmount, 2) }}</span>
                                        </div>
                                    @endif
                                    
                                    @if($writeOffAmount > 0)
                                        <div class="flex justify-between border-t border-gray-200 pt-2">
                                            <span class="text-gray-600">Net Total:</span>
                                            <span class="font-medium">${{ number_format($totalCost - $writeOffAmount, 2) }}</span>
                                        </div>
                                    @endif
                                    
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Cash Collected:</span>
                                        <span class="font-medium">${{ number_format($amountCollected, 2) }}</span>
                                    </div>
                                    
                                    @php
                                        $netTotal = $totalCost - ($writeOffAmount ?? 0);
                                        $remainingAfterCash = max(0, $netTotal - ($amountCollected ?? 0));
                                        $creditApplied = 0;
                                        $accountApplied = 0;
                                        
                                        if ($applyCreditBalance && $this->customerCreditBalance > 0) {
                                            $creditApplied = min($this->customerCreditBalance, $remainingAfterCash);
                                            $remainingAfterCash -= $creditApplied;
                                        }
                                        
                                        if ($applyAccountBalance && $this->customerAccountBalance > 0 && $remainingAfterCash > 0) {
                                            $accountApplied = min($this->customerAccountBalance, $remainingAfterCash);
                                        }
                                    @endphp
                                    
                                    @if($creditApplied > 0)
                                        <div class="flex justify-between text-blue-600">
                                            <span>Credit Applied:</span>
                                            <span class="font-medium">${{ number_format($creditApplied, 2) }}</span>
                                        </div>
                                    @endif
                                    
                                    @if($accountApplied > 0)
                                        <div class="flex justify-between text-green-600">
                                            <span>Account Balance Applied:</span>
                                            <span class="font-medium">${{ number_format($accountApplied, 2) }}</span>
                                        </div>
                                    @endif
                                    <div class="flex justify-between border-t border-gray-200 pt-2">
                                        <span class="text-gray-600">Payment Status:</span>
                                        <span class="font-medium {{ $this->getPaymentStatusColor() }}">
                                            {{ $this->getPaymentStatusLabel() }}
                                        </span>
                                    </div>
                                    @if($paymentStatus !== 'paid')
                                        @php
                                            $amountCollectedNum = (float) ($amountCollected ?? 0);
                                            $writeOffAmountNum = (float) ($writeOffAmount ?? 0);
                                            $totalCostNum = (float) ($totalCost ?? 0);
                                            
                                            $netTotal = $totalCostNum - $writeOffAmountNum;
                                            $balanceApplied = 0;
                                            if ($applyCreditBalance && $this->customerTotalAvailableBalance > 0) {
                                                $balanceApplied = min($this->customerTotalAvailableBalance, max(0, $netTotal - $amountCollectedNum));
                                            }
                                            $totalReceived = $amountCollectedNum + $balanceApplied;
                                            $outstanding = max(0, $netTotal - $totalReceived);
                                        @endphp
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Outstanding Balance:</span>
                                            <span class="font-medium text-red-600">
                                                ${{ number_format($outstanding, 2) }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-6 flex justify-end space-x-3">
                            <button 
                                wire:click="resetForm"
                                type="button"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500"
                            >
                                Reset
                            </button>
                            
                            <button 
                                wire:click="showDistributionConfirmation"
                                type="button"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-wax-flower-600 hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500"
                                @if(($showConsolidatedView && count($selectedConsolidatedPackages) === 0) || (!$showConsolidatedView && count($selectedPackages) === 0) || $amountCollected < 0) disabled @endif
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Review Distribution
                            </button>
                        </div>
                    </div>
    @else
                <div class="px-6 py-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v1M7 6V4a1 1 0 011-1h4a1 1 0 011 1v2"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No packages ready for distribution</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if($search)
                            No packages match your search criteria.
                        @else
                            This customer has no packages with "Ready for Pickup" status.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    @else
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg">
            <div class="px-6 py-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Select a customer</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Choose a customer from the dropdown above to view their packages ready for distribution.
                </p>
            </div>
        </div>
    @endif

    <!-- Distribution Confirmation Modal -->
    @if($showConfirmation && !empty($distributionSummary))
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="distribution-modal">
            <div class="relative top-10 mx-auto p-5 border max-w-4xl shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Confirm Package Distribution</h3>
                        <button 
                            wire:click="cancelDistribution"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Customer Information -->
                    <div class="mt-4 bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Customer Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div class="space-y-2">
                                <div>
                                    <span class="text-gray-600">Name:</span>
                                    <span class="ml-2 font-medium">{{ $distributionSummary['customer']['name'] ?? 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Email:</span>
                                    <span class="ml-2 font-medium">{{ $distributionSummary['customer']['email'] ?? 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Phone:</span>
                                    <span class="ml-2 font-medium">{{ $distributionSummary['customer']['phone'] ?? 'N/A' }}</span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div>
                                    <span class="text-gray-600">Account Number:</span>
                                    <span class="ml-2 font-medium">{{ $distributionSummary['customer']['account_number'] ?? 'N/A' }}</span>
                                </div>
                                @if(!empty($distributionSummary['customer']['tax_number']))
                                    <div>
                                        <span class="text-gray-600">Tax Number:</span>
                                        <span class="ml-2 font-medium">{{ $distributionSummary['customer']['tax_number'] }}</span>
                                    </div>
                                @endif
                                <div>
                                    <span class="text-gray-600">Address:</span>
                                    <span class="ml-2 font-medium">{{ $distributionSummary['customer']['address'] ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Package Details -->
                    <div class="mt-4">
                        @if($distributionSummary['is_consolidated'] ?? false)
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Consolidated Packages to Distribute</h4>
                            
                            @foreach($distributionSummary['consolidated_packages'] as $consolidatedPackage)
                                <div class="mb-6 border border-blue-200 rounded-lg overflow-hidden">
                                    <!-- Consolidated Package Header -->
                                    <div class="bg-blue-50 px-4 py-3 border-b border-blue-200">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h5 class="text-sm font-medium text-blue-900">
                                                    Consolidated Package: {{ $consolidatedPackage['consolidated_tracking_number'] }}
                                                </h5>
                                                <p class="text-xs text-blue-700 mt-1">
                                                    {{ $consolidatedPackage['total_quantity'] }} packages • {{ number_format($consolidatedPackage['total_weight'], 2) }} lbs total
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-medium text-blue-900">
                                                    ${{ number_format($consolidatedPackage['total_cost'], 2) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Individual Packages in Consolidation -->
                                    <div class="overflow-hidden">
                                        <table class="min-w-full divide-y divide-gray-300">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Tracking Number
                                                    </th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Description
                                                    </th>
                                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Weight
                                                    </th>
                                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Total
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach($consolidatedPackage['individual_packages'] as $package)
                                                    <tr>
                                                        <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">
                                                            {{ $package['tracking_number'] }}
                                                        </td>
                                                        <td class="px-4 py-2 text-xs text-gray-500 max-w-xs truncate">
                                                            {{ $package['description'] ?: '-' }}
                                                        </td>
                                                        <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900 text-right">
                                                            {{ number_format($package['weight'], 2) }} lbs
                                                        </td>
                                                        <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900 text-right">
                                                            ${{ number_format($package['total_cost'], 2) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Packages to Distribute</h4>
                            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-300">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Tracking Number
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Description
                                            </th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Freight
                                            </th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Customs
                                            </th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Storage
                                            </th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Delivery
                                            </th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Total
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($distributionSummary['packages'] as $package)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {{ $package['tracking_number'] }}
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                                    {{ $package['description'] ?: '-' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                    ${{ number_format($package['freight_price'], 2) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                    ${{ number_format($package['customs_duty'], 2) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                    ${{ number_format($package['storage_fee'], 2) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                    ${{ number_format($package['delivery_fee'], 2) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">
                                                    ${{ number_format($package['total_cost'], 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <!-- Payment Summary -->
                    <div class="mt-4 bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Payment Summary</h4>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Payment Breakdown -->
                            <div class="space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Original Total:</span>
                                    <span class="font-medium">${{ number_format($distributionSummary['total_cost'], 2) }}</span>
                                </div>
                                
                                @if($distributionSummary['write_off_amount'] > 0)
                                    <div class="flex justify-between text-sm text-yellow-600">
                                        <span>Write-off/Discount:</span>
                                        <span class="font-medium">-${{ number_format($distributionSummary['write_off_amount'], 2) }}</span>
                                    </div>
                                    @if($distributionSummary['write_off_reason'])
                                        <div class="text-xs text-gray-500 italic pl-4">
                                            Reason: {{ $distributionSummary['write_off_reason'] }}
                                        </div>
                                    @endif
                                @endif
                                
                                @if($distributionSummary['write_off_amount'] > 0)
                                    <div class="flex justify-between text-sm border-t border-gray-300 pt-2">
                                        <span class="text-gray-900 font-medium">Net Total:</span>
                                        <span class="font-bold">${{ number_format($distributionSummary['net_total'], 2) }}</span>
                                    </div>
                                @endif
                                
                                <div class="border-t border-gray-300 pt-2 space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Cash Collected:</span>
                                        <span class="font-medium">${{ number_format($distributionSummary['amount_collected'], 2) }}</span>
                                    </div>
                                    
                                    @if($distributionSummary['balance_applied'] > 0)
                                        <div class="flex justify-between text-sm text-blue-600">
                                            <span>Balance Applied:</span>
                                            <span class="font-medium">${{ number_format($distributionSummary['balance_applied'], 2) }}</span>
                                        </div>
                                    @endif
                                    
                                    <div class="flex justify-between text-sm border-t border-gray-300 pt-2">
                                        <span class="text-gray-900 font-medium">Total Received:</span>
                                        <span class="font-bold">${{ number_format($distributionSummary['total_received'], 2) }}</span>
                                    </div>
                                </div>
                                
                                @if($distributionSummary['outstanding_balance'] > 0)
                                    <div class="flex justify-between text-sm bg-red-50 p-2 rounded">
                                        <span class="text-red-700 font-medium">Outstanding Balance:</span>
                                        <span class="font-bold text-red-700">${{ number_format($distributionSummary['outstanding_balance'], 2) }}</span>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Status and Notes -->
                            <div class="space-y-4">
                                <!-- Payment Status -->
                                <div class="text-center">
                                    <div class="text-sm text-gray-600 mb-2">Payment Status</div>
                                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium
                                        @if($distributionSummary['payment_status'] === 'paid') bg-green-100 text-green-800
                                        @elseif($distributionSummary['payment_status'] === 'partial') bg-yellow-100 text-yellow-800
                                        @else bg-red-100 text-red-800 @endif">
                                        @if($distributionSummary['payment_status'] === 'paid')
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                        @elseif($distributionSummary['payment_status'] === 'partial')
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            </svg>
                                        @endif
                                        {{ ucfirst($distributionSummary['payment_status']) }}
                                    </span>
                                </div>
                                
                                <!-- Customer Balance Info -->
                                @if($distributionSummary['customer']['total_available_balance'] > 0)
                                    <div class="bg-blue-50 p-3 rounded-lg">
                                        <div class="text-xs text-blue-600 font-medium mb-1">Customer Available Balance</div>
                                        <div class="text-sm text-blue-800">
                                            ${{ number_format($distributionSummary['customer']['total_available_balance'], 2) }}
                                            @if($distributionSummary['balance_applied'] > 0)
                                                <span class="text-xs text-blue-600">
                                                    (${{ number_format($distributionSummary['balance_applied'], 2) }} applied)
                                                </span>
                                            @endif
                                        </div>
                                        @if($distributionSummary['customer']['account_balance'] > 0 && $distributionSummary['customer']['credit_balance'] > 0)
                                            <div class="text-xs text-blue-600 mt-1">
                                                Account: ${{ number_format($distributionSummary['customer']['account_balance'], 2) }} + 
                                                Credit: ${{ number_format($distributionSummary['customer']['credit_balance'], 2) }}
                                            </div>
                                        @elseif($distributionSummary['customer']['account_balance'] > 0)
                                            <div class="text-xs text-blue-600 mt-1">
                                                Account Balance: ${{ number_format($distributionSummary['customer']['account_balance'], 2) }}
                                            </div>
                                        @elseif($distributionSummary['customer']['credit_balance'] > 0)
                                            <div class="text-xs text-blue-600 mt-1">
                                                Credit Balance: ${{ number_format($distributionSummary['customer']['credit_balance'], 2) }}
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                
                                <!-- Distribution Notes -->
                                @if($distributionSummary['distribution_notes'])
                                    <div class="bg-gray-100 p-3 rounded-lg">
                                        <div class="text-xs text-gray-600 font-medium mb-1">Distribution Notes</div>
                                        <div class="text-sm text-gray-800">{{ $distributionSummary['distribution_notes'] }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-6 flex justify-end space-x-3">
                        <button 
                            wire:click="cancelDistribution"
                            type="button"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500"
                            @if($isProcessing) disabled @endif
                        >
                            Cancel
                        </button>
                        
                        <button 
                            wire:click="processDistribution"
                            type="button"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                            @if($isProcessing) disabled @endif
                        >
                            @if($isProcessing)
                                <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing...
                            @else
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                Confirm Distribution
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide flash messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('[role="alert"]');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);

        // Customer search dropdown functionality
        function initializeCustomerSearch() {
            const searchInput = document.getElementById('customerSearch');
            
            if (searchInput) {
                // Handle keyboard navigation
                searchInput.addEventListener('keydown', function(e) {
                    const dropdown = document.querySelector('.absolute.z-50');
                    if (!dropdown) return;
                    
                    const items = dropdown.querySelectorAll('[wire\\:click*="selectCustomer"]');
                    let currentIndex = -1;
                    
                    // Find currently highlighted item
                    items.forEach((item, index) => {
                        if (item.classList.contains('bg-wax-flower-100')) {
                            currentIndex = index;
                        }
                    });
                    
                    switch(e.key) {
                        case 'ArrowDown':
                            e.preventDefault();
                            // Remove current highlight
                            if (currentIndex >= 0) {
                                items[currentIndex].classList.remove('bg-wax-flower-100');
                            }
                            // Add highlight to next item
                            currentIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0;
                            items[currentIndex].classList.add('bg-wax-flower-100');
                            items[currentIndex].scrollIntoView({ block: 'nearest' });
                            break;
                            
                        case 'ArrowUp':
                            e.preventDefault();
                            // Remove current highlight
                            if (currentIndex >= 0) {
                                items[currentIndex].classList.remove('bg-wax-flower-100');
                            }
                            // Add highlight to previous item
                            currentIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
                            items[currentIndex].classList.add('bg-wax-flower-100');
                            items[currentIndex].scrollIntoView({ block: 'nearest' });
                            break;
                            
                        case 'Enter':
                            e.preventDefault();
                            if (currentIndex >= 0) {
                                items[currentIndex].click();
                            }
                            break;
                            
                        case 'Escape':
                            e.preventDefault();
                            @this.call('hideCustomerDropdown');
                            break;
                    }
                });
            }
            
            // Click outside to close dropdown
            document.addEventListener('click', function(e) {
                const searchContainer = document.querySelector('#customerSearch')?.closest('.relative');
                if (searchContainer && !searchContainer.contains(e.target)) {
                    @this.call('hideCustomerDropdown');
                }
            });
        }
        
        // Initialize customer search
        initializeCustomerSearch();
        
        // Re-initialize when Livewire updates the DOM
        document.addEventListener('livewire:load', function() {
            initializeCustomerSearch();
        });
        
        document.addEventListener('livewire:update', function() {
            initializeCustomerSearch();
        });
    });
</script>
            </div>
        </div>
</div>