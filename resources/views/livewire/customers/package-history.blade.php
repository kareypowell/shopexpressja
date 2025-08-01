{{-- Package History Component --}}
<div class="space-y-6">
    {{-- Package Statistics Summary --}}
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1H7a1 1 0 00-1 1v1m8 0V4.5"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Package History</dt>
                        <dd class="flex items-baseline">
                            <div class="text-2xl font-semibold text-gray-900">
                                {{ $packageStats['total_packages'] }}
                            </div>
                            <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                                packages shipped
                            </div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
            <div class="text-sm">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-lg font-semibold text-gray-900">
                            ${{ number_format($packageStats['total_spent'], 2) }}
                        </div>
                        <div class="text-xs text-gray-500">Total Spent</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-semibold text-gray-900">
                            ${{ number_format($packageStats['average_cost'], 2) }}
                        </div>
                        <div class="text-xs text-gray-500">Average Cost</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-semibold text-gray-900">
                            {{ $packageStats['status_breakdown']->get('shipped', 0) }}
                        </div>
                        <div class="text-xs text-gray-500">Shipped</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-semibold text-gray-900">
                            {{ $packageStats['status_breakdown']->get('processing', 0) }}
                        </div>
                        <div class="text-xs text-gray-500">Processing</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Package Table Component --}}
    @livewire('customers.customer-packages-table', ['customer' => $customer])
</div>