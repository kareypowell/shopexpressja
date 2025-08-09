<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Account Balance
            </h3>
            <button 
                wire:click="toggleTransactions"
                class="text-sm text-wax-flower-600 hover:text-wax-flower-800 font-medium"
            >
                {{ $showTransactions ? 'Hide' : 'View' }} Transactions
            </button>
        </div>

        <!-- Balance Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Account Balance -->
            <div class="bg-blue-50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500">Account Balance</dt>
                        <dd class="text-2xl font-semibold text-gray-900">
                            ${{ $accountSummary['formatted']['account_balance'] }}
                        </dd>
                    </div>
                </div>
            </div>

            <!-- Credit Balance -->
            <div class="bg-green-50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500">Credit Balance</dt>
                        <dd class="text-2xl font-semibold text-green-600">
                            ${{ $accountSummary['formatted']['credit_balance'] }}
                        </dd>
                    </div>
                </div>
            </div>

            <!-- Total Available -->
            <div class="bg-purple-50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500">Total Available</dt>
                        <dd class="text-2xl font-semibold text-purple-600">
                            ${{ $accountSummary['formatted']['total_available'] }}
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Balance Explanation -->
        <div class="bg-gray-50 rounded-lg p-4 mb-4">
            <h4 class="text-sm font-medium text-gray-900 mb-2">Balance Information</h4>
            <div class="text-sm text-gray-600 space-y-1">
                <p><strong>Account Balance:</strong> Your current account balance including charges and payments.</p>
                <p><strong>Credit Balance:</strong> Available credit from overpayments that can be applied to future charges.</p>
                <p><strong>Total Available:</strong> Combined balance available for package distributions and charges.</p>
            </div>
        </div>

        <!-- Recent Transactions -->
        @if($showTransactions)
            <div class="border-t border-gray-200 pt-4">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Recent Transactions</h4>
                
                @if($recentTransactions->count() > 0)
                    <div class="space-y-3">
                        @foreach($recentTransactions as $transaction)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2">
                                        @if($transaction->isCredit())
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                {{ ucfirst($transaction->type) }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                {{ ucfirst($transaction->type) }}
                                            </span>
                                        @endif
                                        <span class="text-sm text-gray-900">{{ $transaction->description }}</span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $transaction->created_at->format('M j, Y g:i A') }}
                                        @if($transaction->createdBy)
                                            • by {{ $transaction->createdBy->full_name }}
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium {{ $transaction->isCredit() ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $transaction->isCredit() ? '+' : '-' }}${{ $transaction->formatted_amount }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        Balance: ${{ $transaction->formatted_balance_after }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 italic">No recent transactions found.</p>
                @endif
            </div>
        @endif
    </div>
</div>