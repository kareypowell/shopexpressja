<div>
    <h3 class="mt-5 text-lg leading-6 font-medium text-gray-900">Welcome back, {{ auth()->user()->first_name }}!</h3>

    <div>
        @include('livewire.quick-insights', [
            'inComingAir' => $inComingAir,
            'inComingSea' => $inComingSea,
            'availableAir' => $availableAir,
            'availableSea' => $availableSea,
            'accountBalance' => $accountBalance,
            'creditBalance' => $creditBalance,
            'totalAvailableBalance' => $totalAvailableBalance,
            'pendingPackageCharges' => $pendingPackageCharges,
            'totalAmountNeeded' => $totalAmountNeeded,
            'delayedPackages' => $delayedPackages
        ])

        <hr class="my-10">

        @if(auth()->user()->isCustomer())
            <!-- Detailed Account Balance for Customers -->
            <div class="mt-10">
                <livewire:customers.customer-transaction-history />
            </div>
            
            <div class="mt-10">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden relative">
                    <!-- Dropdown backdrop for mobile -->
                    @if($showFilterDropdown)
                        <div class="fixed inset-0 z-40 sm:hidden" wire:click="toggleFilterDropdown"></div>
                    @endif
                    
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-100 relative">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1H7a1 1 0 00-1 1v1m8 0V4.5"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base sm:text-lg font-semibold text-gray-900">My Packages</h3>
                                    <p class="text-xs sm:text-sm text-gray-600">Track and manage your shipments</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 flex-wrap sm:flex-nowrap relative">
                                <!-- Filter Dropdown -->
                                <div class="relative w-full sm:w-auto">
                                    <button 
                                        wire:click="toggleFilterDropdown"
                                        class="inline-flex items-center justify-center w-full sm:w-auto px-2 py-1.5 sm:px-3 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z"></path>
                                        </svg>
                                        <span class="hidden sm:inline">
                                            @switch($packageFilter)
                                                @case('air') Air Packages @break
                                                @case('sea') Sea Packages @break
                                                @case('ready') Ready @break
                                                @case('in-transit') In Transit @break
                                                @case('delivered') Delivered @break
                                                @case('delayed') Delayed @break
                                                @default All Packages
                                            @endswitch
                                        </span>
                                        <span class="sm:hidden">
                                            @switch($packageFilter)
                                                @case('air') Air @break
                                                @case('sea') Sea @break
                                                @case('ready') Ready @break
                                                @case('in-transit') Transit @break
                                                @case('delivered') Delivered @break
                                                @case('delayed') Delayed @break
                                                @default All
                                            @endswitch
                                        </span>
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    
                                    @if($showFilterDropdown)
                                        <div class="absolute left-0 right-0 sm:right-0 sm:left-auto mt-2 w-full sm:w-48 bg-white rounded-md shadow-xl z-50 border border-gray-200 max-h-64 overflow-y-auto">
                                            <div class="py-1">
                                                <button wire:click="setPackageFilter('all')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ $packageFilter === 'all' ? 'bg-blue-50 text-blue-700' : '' }}">
                                                    All Packages
                                                </button>
                                                <button wire:click="setPackageFilter('air')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ $packageFilter === 'air' ? 'bg-blue-50 text-blue-700' : '' }}">
                                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                                    </svg>
                                                    Air Packages
                                                </button>
                                                <button wire:click="setPackageFilter('sea')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ $packageFilter === 'sea' ? 'bg-blue-50 text-blue-700' : '' }}">
                                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h8a2 2 0 002-2V8m-9 4h4"></path>
                                                    </svg>
                                                    Sea Packages
                                                </button>
                                                <div class="border-t border-gray-100"></div>
                                                <button wire:click="setPackageFilter('ready')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ $packageFilter === 'ready' ? 'bg-blue-50 text-blue-700' : '' }}">
                                                    Ready for Pickup
                                                </button>
                                                <button wire:click="setPackageFilter('in-transit')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ $packageFilter === 'in-transit' ? 'bg-blue-50 text-blue-700' : '' }}">
                                                    In Transit
                                                </button>
                                                <button wire:click="setPackageFilter('delivered')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ $packageFilter === 'delivered' ? 'bg-blue-50 text-blue-700' : '' }}">
                                                    Delivered
                                                </button>
                                                @if($delayedPackages > 0)
                                                    <div class="border-t border-gray-100"></div>
                                                    <button wire:click="setPackageFilter('delayed')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ $packageFilter === 'delayed' ? 'bg-red-50 text-red-700' : '' }}">
                                                        <svg class="w-4 h-4 inline mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                        </svg>
                                                        Delayed ({{ $delayedPackages }})
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                
                                <a href="{{ route('packages.index') }}" class="inline-flex items-center px-2 py-1.5 sm:px-3 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mt-2 sm:mt-0 w-full sm:w-auto justify-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                                    </svg>
                                    <span class="hidden xs:inline">View All</span>
                                    <span class="xs:hidden">All</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6">
                        @include('livewire.customers.customer-packages-compact', [
                            'packages' => $this->filteredPackages
                        ])
                    </div>
                </div>
            </div>
        @else
            <!-- Admin Dashboard with Report Widgets -->
            <div class="mt-10">
                <div class="bg-white shadow rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Admin Dashboard</h3>
                    <p class="text-gray-600">Welcome to the admin dashboard. Use the navigation menu to access admin features.</p>
                </div>
                
                <!-- Business Analytics Section -->
                @if(auth()->user()->canAccessAdminPanel())
                    @livewire('admin.dashboard-reports')
                @endif
            </div>
        @endif
    </div>

    @if(auth()->user()->isCustomer())
        {{-- Package Detail Modal --}}
        @livewire('customers.customer-packages-with-modal', ['customer' => auth()->user()])
    @endif
</div>