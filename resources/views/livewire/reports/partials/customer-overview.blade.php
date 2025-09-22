<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Package Statistics -->
    <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-blue-600">Total Packages</p>
                <p class="text-2xl font-bold text-blue-900">{{ $overview['package_stats']->total_packages ?? 0 }}</p>
            </div>
        </div>
    </div>

    <!-- Delivered Packages -->
    <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-lg p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-green-600">Delivered</p>
                <p class="text-2xl font-bold text-green-900">{{ $overview['package_stats']->delivered_packages ?? 0 }}</p>
            </div>
        </div>
    </div>

    <!-- Total Charges -->
    <div class="bg-gradient-to-r from-purple-50 to-purple-100 rounded-lg p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-purple-600">Total Charges</p>
                <p class="text-2xl font-bold text-purple-900">${{ number_format($overview['package_stats']->total_charges ?? 0, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Total Payments -->
    <div class="bg-gradient-to-r from-yellow-50 to-yellow-100 rounded-lg p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-yellow-600">Total Payments</p>
                <p class="text-2xl font-bold text-yellow-900">${{ number_format($overview['payment_stats']->total_payments ?? 0, 2) }}</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Package Status Breakdown -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Package Status Breakdown</h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                    <span class="text-sm text-gray-700">Delivered</span>
                </div>
                <span class="text-sm font-medium text-gray-900">{{ $overview['package_stats']->delivered_packages ?? 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                    <span class="text-sm text-gray-700">In Transit</span>
                </div>
                <span class="text-sm font-medium text-gray-900">{{ $overview['package_stats']->in_transit_packages ?? 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-yellow-500 rounded-full mr-3"></div>
                    <span class="text-sm text-gray-700">Pending</span>
                </div>
                <span class="text-sm font-medium text-gray-900">{{ $overview['package_stats']->pending_packages ?? 0 }}</span>
            </div>
        </div>
        
        @if(($overview['package_stats']->total_packages ?? 0) > 0)
            <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="text-sm text-gray-600">
                    Delivery Rate: 
                    <span class="font-medium text-gray-900">
                        {{ number_format((($overview['package_stats']->delivered_packages ?? 0) / $overview['package_stats']->total_packages) * 100, 1) }}%
                    </span>
                </div>
            </div>
        @endif
    </div>

    <!-- Financial Summary -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Financial Summary</h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-700">Total Charges</span>
                <span class="text-sm font-medium text-gray-900">${{ number_format($overview['package_stats']->total_charges ?? 0, 2) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-700">Total Payments</span>
                <span class="text-sm font-medium text-green-600">${{ number_format($overview['payment_stats']->total_payments ?? 0, 2) }}</span>
            </div>
            <div class="flex items-center justify-between border-t border-gray-200 pt-4">
                <span class="text-sm font-medium text-gray-700">Current Balance</span>
                <span class="text-sm font-bold {{ $overview['account_balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    ${{ number_format($overview['account_balance'], 2) }}
                </span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-700">Average Package Cost</span>
                <span class="text-sm font-medium text-gray-900">${{ number_format($overview['package_stats']->average_package_cost ?? 0, 2) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-700">Average Payment</span>
                <span class="text-sm font-medium text-gray-900">${{ number_format($overview['payment_stats']->average_payment ?? 0, 2) }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
@if(isset($overview['recent_packages']) && $overview['recent_packages']->count() > 0)
    <div class="bg-white border border-gray-200 rounded-lg p-6 mt-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Package Activity</h3>
        <div class="overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tracking Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($overview['recent_packages'] as $package)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $package->tracking_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($package->status === 'delivered') bg-green-100 text-green-800
                                    @elseif($package->status === 'shipped') bg-blue-100 text-blue-800
                                    @elseif($package->status === 'customs') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($package->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $package->created_at->format('M j, Y') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif