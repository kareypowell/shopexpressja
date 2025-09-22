<div class="space-y-6">
    <!-- Customer Header -->
    @if($customer)
        <div class="bg-white shadow-sm rounded-lg p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-lg font-semibold text-blue-600">
                                {{ substr($customer->name, 0, 1) }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $customer->name }}</h1>
                        <p class="text-sm text-gray-600">{{ $customer->email }}</p>
                        <p class="text-xs text-gray-500">Customer since {{ $customer->created_at->format('M j, Y') }}</p>
                    </div>
                </div>
                
                <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
                    <!-- Account Balance -->
                    <div class="text-center sm:text-right">
                        <p class="text-sm text-gray-600">Account Balance</p>
                        <p class="text-lg font-semibold {{ $customer->account_balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            ${{ number_format($customer->account_balance, 2) }}
                        </p>
                    </div>
                    
                    <!-- Export Button -->
                    <div class="relative" x-data="{ open: false }">
                        <button 
                            @click="open = !open"
                            type="button" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Export Report
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
                            class="absolute right-0 z-10 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5"
                        >
                            <div class="py-1">
                                <button 
                                    wire:click="exportCustomerReport('pdf')"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                >
                                    Export as PDF
                                </button>
                                <button 
                                    wire:click="exportCustomerReport('csv')"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                >
                                    Export as CSV
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="bg-white shadow-sm rounded-lg p-4">
            <div class="flex flex-col sm:flex-row sm:items-center space-y-4 sm:space-y-0 sm:space-x-4">
                <div class="flex-1">
                    <label for="dateFrom" class="block text-sm font-medium text-gray-700">From Date</label>
                    <input 
                        type="date" 
                        id="dateFrom"
                        wire:model="dateFrom"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                </div>
                <div class="flex-1">
                    <label for="dateTo" class="block text-sm font-medium text-gray-700">To Date</label>
                    <input 
                        type="date" 
                        id="dateTo"
                        wire:model="dateTo"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                </div>
                <div class="flex-shrink-0 sm:mt-6">
                    <button 
                        wire:click="$refresh"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Update
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="bg-white shadow-sm rounded-lg">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                    <button 
                        wire:click="setActiveTab('overview')"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                    >
                        Overview
                    </button>
                    <button 
                        wire:click="setActiveTab('packages')"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'packages' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                    >
                        Package History
                    </button>
                    <button 
                        wire:click="setActiveTab('transactions')"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'transactions' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                    >
                        Transactions
                    </button>
                    <button 
                        wire:click="setActiveTab('balance')"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'balance' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                    >
                        Balance History
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                @if($activeTab === 'overview' && isset($overview))
                    @include('livewire.reports.partials.customer-overview', ['overview' => $overview])
                @elseif($activeTab === 'packages' && isset($packages))
                    @include('livewire.reports.partials.customer-packages', ['packages' => $packages])
                @elseif($activeTab === 'transactions' && isset($transactions))
                    @include('livewire.reports.partials.customer-transactions', ['transactions' => $transactions])
                @elseif($activeTab === 'balance' && isset($balanceHistory))
                    @include('livewire.reports.partials.customer-balance-history', ['balanceHistory' => $balanceHistory])
                @endif
            </div>
        </div>
    @else
        <!-- No Customer Selected -->
        <div class="bg-white shadow-sm rounded-lg p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No Customer Selected</h3>
            <p class="mt-1 text-sm text-gray-500">Select a customer to view their detailed report.</p>
        </div>
    @endif

    <!-- Loading overlay -->
    <div wire:loading class="fixed inset-0 bg-white bg-opacity-75 flex items-center justify-center z-50">
        <div class="flex items-center space-x-2">
            <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span class="text-sm text-gray-600">Loading customer data...</span>
        </div>
    </div>
</div>