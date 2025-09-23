<div>
    <!-- Modal Backdrop -->
    <div x-show="show" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50"
         @click="show = false">
    </div>

    <!-- Modal Panel -->
    <div x-show="show"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         class="fixed inset-0 z-50 overflow-y-auto">
        
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-6xl">
                
                <!-- Modal Header -->
                <div class="bg-white px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                Package Details - {{ $manifestData['manifest_name'] ?? 'Manifest' }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ count($packages) }} packages • 
                                Total Owed: ${{ number_format($manifestData['total_owed'] ?? 0, 2) }} • 
                                Collected: ${{ number_format($manifestData['total_collected'] ?? 0, 2) }}
                            </p>
                        </div>
                        <button @click="show = false" 
                                class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <span class="sr-only">Close</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="bg-white px-6 py-4">
                    @if($isLoading)
                        <!-- Loading State -->
                        <div class="flex items-center justify-center py-12">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            <span class="ml-2 text-gray-600">Loading package details...</span>
                        </div>
                    @elseif($error)
                        <!-- Error State -->
                        <div class="rounded-md bg-red-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Error Loading Data</h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <p>{{ $error }}</p>
                                    </div>
                                    <div class="mt-4">
                                        <button wire:click="loadPackageDetails" 
                                                class="bg-red-100 px-2 py-1 text-xs font-semibold text-red-800 hover:bg-red-200 rounded">
                                            Try Again
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif(empty($packages))
                        <!-- Empty State -->
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1H7a1 1 0 00-1 1v1m8 0V4.5"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No packages found</h3>
                            <p class="mt-1 text-sm text-gray-500">This manifest doesn't contain any packages.</p>
                        </div>
                    @else
                        <!-- Package Details Table -->
                        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Package Info
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Customer
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Charges
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($packages as $package)
                                        <tr class="hover:bg-gray-50">
                                            <!-- Package Info -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                            <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            {{ $package['tracking_number'] ?? 'N/A' }}
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            {{ $package['description'] ?? 'No description' }}
                                                        </div>
                                                        @if(isset($package['weight']) || isset($package['dimensions']))
                                                            <div class="text-xs text-gray-400 mt-1">
                                                                @if(isset($package['weight']))
                                                                    {{ $package['weight'] }} lbs
                                                                @endif
                                                                @if(isset($package['dimensions']))
                                                                    • {{ $package['dimensions'] }}
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Customer -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ $package['customer_name'] ?? 'Unknown Customer' }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ $package['customer_email'] ?? '' }}
                                                </div>
                                            </td>

                                            <!-- Charges -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <div class="space-y-1">
                                                        @if(isset($package['freight_price']) && $package['freight_price'] > 0)
                                                            <div class="flex justify-between">
                                                                <span class="text-gray-600">Freight:</span>
                                                                <span>${{ number_format($package['freight_price'], 2) }}</span>
                                                            </div>
                                                        @endif
                                                        @if(isset($package['clearance_fee']) && $package['clearance_fee'] > 0)
                                                            <div class="flex justify-between">
                                                                <span class="text-gray-600">Customs:</span>
                                                                <span>${{ number_format($package['clearance_fee'], 2) }}</span>
                                                            </div>
                                                        @endif
                                                        @if(isset($package['storage_fee']) && $package['storage_fee'] > 0)
                                                            <div class="flex justify-between">
                                                                <span class="text-gray-600">Storage:</span>
                                                                <span>${{ number_format($package['storage_fee'], 2) }}</span>
                                                            </div>
                                                        @endif
                                                        @if(isset($package['delivery_fee']) && $package['delivery_fee'] > 0)
                                                            <div class="flex justify-between">
                                                                <span class="text-gray-600">Delivery:</span>
                                                                <span>${{ number_format($package['delivery_fee'], 2) }}</span>
                                                            </div>
                                                        @endif
                                                        <div class="border-t pt-1 font-medium flex justify-between">
                                                            <span>Total:</span>
                                                            <span>${{ number_format($package['total_charges'] ?? 0, 2) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Status -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex flex-col space-y-2">
                                                    <!-- Package Status -->
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                        @if($package['status'] === 'delivered') bg-green-100 text-green-800
                                                        @elseif($package['status'] === 'in_transit') bg-blue-100 text-blue-800
                                                        @elseif($package['status'] === 'processing') bg-yellow-100 text-yellow-800
                                                        @else bg-gray-100 text-gray-800 @endif">
                                                        {{ ucfirst(str_replace('_', ' ', $package['status'] ?? 'unknown')) }}
                                                    </span>
                                                    
                                                    <!-- Payment Status -->
                                                    @if(isset($package['payment_status']))
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                            @if($package['payment_status'] === 'paid') bg-green-100 text-green-800
                                                            @elseif($package['payment_status'] === 'partial') bg-yellow-100 text-yellow-800
                                                            @else bg-red-100 text-red-800 @endif">
                                                            {{ ucfirst($package['payment_status']) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>

                                            <!-- Actions -->
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    @if(isset($package['id']))
                                                        <a href="{{ route('packages.show', $package['id']) }}" 
                                                           class="text-blue-600 hover:text-blue-900 text-xs">
                                                            View Details
                                                        </a>
                                                    @endif
                                                    
                                                    @if(isset($package['customer_id']))
                                                        <button wire:click="viewCustomerDetails({{ $package['customer_id'] }})"
                                                                class="text-purple-600 hover:text-purple-900 text-xs">
                                                            Customer
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Summary Footer -->
                        <div class="mt-6 bg-gray-50 px-6 py-4 rounded-lg">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-gray-900">{{ count($packages) }}</div>
                                    <div class="text-sm text-gray-600">Total Packages</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-blue-600">
                                        ${{ number_format($manifestData['total_owed'] ?? 0, 2) }}
                                    </div>
                                    <div class="text-sm text-gray-600">Total Owed</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600">
                                        ${{ number_format($manifestData['total_collected'] ?? 0, 2) }}
                                    </div>
                                    <div class="text-sm text-gray-600">Collected</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-red-600">
                                        ${{ number_format(($manifestData['total_owed'] ?? 0) - ($manifestData['total_collected'] ?? 0), 2) }}
                                    </div>
                                    <div class="text-sm text-gray-600">Outstanding</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 px-6 py-3 flex justify-between items-center">
                    <div class="text-sm text-gray-500">
                        @if(!empty($packages))
                            Last updated: {{ now()->format('M j, Y g:i A') }}
                        @endif
                    </div>
                    <div class="flex space-x-3">
                        @if(!empty($packages))
                            <button wire:click="exportPackageDetails" 
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Export
                            </button>
                        @endif
                        <button @click="show = false" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>