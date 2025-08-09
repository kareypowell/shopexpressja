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
                @if($customer)
                    - {{ $customer->name }}
                @endif
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Distribute ready packages to customers with receipt generation
            </p>
        </div>
    </div>

    @if(!$customerId || !$customer)
        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">
                        Customer Required
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>Please select a customer to view their packages ready for distribution.</p>
                    </div>
                </div>
            </div>
        </div>
    @elseif($packages && $packages->count() === 0)
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v1M7 6V4a1 1 0 011-1h4a1 1 0 011 1v2"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No packages ready for distribution</h3>
            <p class="mt-1 text-sm text-gray-500">
                {{ $customer->name }} has no packages with "Ready for Pickup" status.
            </p>
        </div>
    @else
        <!-- Distribution Form -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg">
            <!-- Package Selection -->
            <div class="px-6 py-4 border-b border-gray-200">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Select Packages for Distribution</h4>
                
                @if($packages->count() > 0)
                    <div class="space-y-3">
                        @foreach($packages as $package)
                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                                <div class="flex items-center space-x-4">
                                    <input 
                                        type="checkbox" 
                                        wire:model="selectedPackages"
                                        value="{{ $package->id }}"
                                        class="h-4 w-4 text-wax-flower-600 focus:ring-wax-flower-500 border-gray-300 rounded"
                                    >
                                    
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-3">
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ $package->tracking_number }}
                                            </p>
                                            <x-badges.{{ $package->status_badge_class }}>
                                                {{ $package->status_label }}
                                            </x-badges.{{ $package->status_badge_class }}>
                                        </div>
                                        
                                        <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                                            <span>Weight: {{ $package->formatted_weight }} lbs</span>
                                            @if($package->manifest)
                                                <span>•</span>
                                                <span>Manifest: {{ $package->manifest->manifest_number ?? 'N/A' }}</span>
                                            @endif
                                            @if($package->office)
                                                <span>•</span>
                                                <span>Office: {{ $package->office->name }}</span>
                                            @endif
                                        </div>
                                        
                                        @if($package->description)
                                            <p class="mt-1 text-sm text-gray-500 truncate">
                                                {{ $package->description }}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <!-- Cost Breakdown -->
                                <div class="text-right">
                                    <div class="text-sm font-medium text-gray-900">
                                        ${{ number_format($package->total_cost, 2) }}
                                    </div>
                                    <div class="text-xs text-gray-500 space-y-1">
                                        @if($package->freight_price > 0)
                                            <div>Freight: ${{ number_format($package->freight_price, 2) }}</div>
                                        @endif
                                        @if($package->customs_duty > 0)
                                            <div>Customs: ${{ number_format($package->customs_duty, 2) }}</div>
                                        @endif
                                        @if($package->storage_fee > 0)
                                            <div>Storage: ${{ number_format($package->storage_fee, 2) }}</div>
                                        @endif
                                        @if($package->delivery_fee > 0)
                                            <div>Delivery: ${{ number_format($package->delivery_fee, 2) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @error('selectedPackages')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                @endif
            </div>

            <!-- Amount Collection and Summary -->
            @if(count($selectedPackages) > 0)
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
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
                        </div>

                        <!-- Credit Balance Option -->
                        @if($customer && $customer->credit_balance > 0)
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <input 
                                        type="checkbox" 
                                        id="apply-credit"
                                        wire:model="applyCreditBalance"
                                        class="h-4 w-4 text-wax-flower-600 focus:ring-wax-flower-500 border-gray-300 rounded"
                                    >
                                    <label for="apply-credit" class="ml-2 block text-sm text-gray-900">
                                        Apply available credit balance
                                    </label>
                                </div>
                                <div class="mt-2 text-sm text-gray-600">
                                    Available credit: <span class="font-medium text-blue-600">${{ number_format($customer->credit_balance, 2) }}</span>
                                    @if($applyCreditBalance)
                                        <br>Credit to apply: <span class="font-medium text-green-600">${{ number_format($creditApplied, 2) }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Cost Summary -->
                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <h5 class="text-sm font-medium text-gray-900 mb-3">Distribution Summary</h5>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Selected Packages:</span>
                                    <span class="font-medium">{{ count($selectedPackages) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Cost:</span>
                                    <span class="font-medium">${{ number_format($totalCost, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Amount Collected:</span>
                                    <span class="font-medium">${{ number_format($amountCollected, 2) }}</span>
                                </div>
                                @if($creditApplied > 0)
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Credit Applied:</span>
                                        <span class="font-medium text-green-600">${{ number_format($creditApplied, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Total Received:</span>
                                        <span class="font-medium">${{ number_format($amountCollected + $creditApplied, 2) }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between border-t border-gray-200 pt-2">
                                    <span class="text-gray-600">Payment Status:</span>
                                    <span class="font-medium {{ $this->getPaymentStatusColor() }}">
                                        {{ $this->getPaymentStatusLabel() }}
                                    </span>
                                </div>
                                @if($paymentStatus !== 'paid')
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Outstanding Balance:</span>
                                        <span class="font-medium text-red-600">
                                            ${{ number_format(max(0, $totalCost - $amountCollected - $creditApplied), 2) }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="px-6 py-4 flex justify-end space-x-3">
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
                        @if(count($selectedPackages) === 0 || $amountCollected < 0) disabled @endif
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Review Distribution
                    </button>
                </div>
            @endif
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
                    </div>

                    <!-- Payment Summary -->
                    <div class="mt-4 bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Payment Summary</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Total Cost:</span>
                                    <span class="font-medium">${{ number_format($distributionSummary['total_cost'], 2) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Amount Collected:</span>
                                    <span class="font-medium">${{ number_format($distributionSummary['amount_collected'], 2) }}</span>
                                </div>
                                @if($distributionSummary['outstanding_balance'] > 0)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Outstanding Balance:</span>
                                        <span class="font-medium text-red-600">${{ number_format($distributionSummary['outstanding_balance'], 2) }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex items-center justify-center">
                                <div class="text-center">
                                    <div class="text-sm text-gray-600 mb-1">Payment Status</div>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                        @if($distributionSummary['payment_status'] === 'paid') bg-green-100 text-green-800
                                        @elseif($distributionSummary['payment_status'] === 'partial') bg-yellow-100 text-yellow-800
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ ucfirst($distributionSummary['payment_status']) }}
                                    </span>
                                </div>
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
    // Auto-hide flash messages after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
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
    });
</script>