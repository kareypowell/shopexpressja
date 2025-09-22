<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Customer Reports</h1>
                <p class="mt-1 text-sm text-gray-600">
                    View detailed customer analytics, transaction history, and account information.
                </p>
            </div>
        </div>
    </div>

    <!-- Customer Search -->
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Select Customer</h2>
        <livewire:reports.customer-search />
    </div>

    <!-- Customer Report Detail -->
    @if($selectedCustomerId)
        <livewire:reports.customer-report-detail :customer-id="$selectedCustomerId" :key="'customer-detail-'.$selectedCustomerId" />
    @else
        <!-- Instructions -->
        <div class="bg-white shadow-sm rounded-lg p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">Customer Analytics & Reports</h3>
            <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">
                Search and select a customer above to view their detailed report including package history, 
                transaction records, account balance tracking, and comprehensive analytics.
            </p>
            
            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6 max-w-3xl mx-auto">
                <div class="text-center">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-blue-100 text-blue-600 mx-auto">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <h4 class="mt-4 text-sm font-medium text-gray-900">Package History</h4>
                    <p class="mt-2 text-xs text-gray-500">Complete tracking and status information for all customer packages</p>
                </div>
                
                <div class="text-center">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-green-100 text-green-600 mx-auto">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <h4 class="mt-4 text-sm font-medium text-gray-900">Transaction Records</h4>
                    <p class="mt-2 text-xs text-gray-500">Detailed payment history and account activity</p>
                </div>
                
                <div class="text-center">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-purple-100 text-purple-600 mx-auto">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <h4 class="mt-4 text-sm font-medium text-gray-900">Balance Tracking</h4>
                    <p class="mt-2 text-xs text-gray-500">Account balance history and trend analysis</p>
                </div>
            </div>
        </div>
    @endif
</div>