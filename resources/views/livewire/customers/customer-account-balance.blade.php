<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Account Balance
            </h3>
            <div class="flex items-center space-x-3">
                <button 
                    wire:click="refreshData"
                    class="text-sm text-gray-600 hover:text-gray-800 font-medium flex items-center"
                    title="Refresh account data"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>
                <button 
                    wire:click="toggleTransactions"
                    class="text-sm text-wax-flower-600 hover:text-wax-flower-800 font-medium"
                >
                    {{ $showTransactions ? 'Hide' : 'View' }} Transactions
                </button>
            </div>
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
                <p><strong>Account Balance:</strong> Your running account balance from package charges and payments. Positive means you have funds available, negative means you owe money.</p>
                <p><strong>Credit Balance:</strong> Available credit from overpayments that can be applied to reduce future package charges.</p>
                <p><strong>Total Available:</strong> Combined funds available to cover package distribution costs (Account Balance + Credit Balance).</p>
            </div>
        </div>

        <!-- Recent Transactions -->
        @if($showTransactions)
            <div class="border-t border-gray-200 pt-4">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Recent Transactions</h4>
                
                @if($recentTransactions->count() > 0)
                    <div class="bg-gray-50 rounded-lg border">
                        <!-- Transaction Header -->
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-100 rounded-t-lg">
                            <div class="flex items-center justify-between">
                                <h5 class="text-sm font-medium text-gray-900">Transaction History</h5>
                                <span class="text-xs text-gray-500">{{ $recentTransactions->count() }} transactions</span>
                            </div>
                        </div>
                        
                        <!-- Scrollable Transaction List -->
                        <div class="max-h-96 overflow-y-auto">
                            <div class="divide-y divide-gray-200">
                                @foreach($recentTransactions as $transaction)
                                    <div class="px-4 py-3 hover:bg-gray-50 transition-colors duration-150">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center space-x-3">
                                                    <!-- Transaction Type Badge -->
                                                    @if($transaction->type === 'payment')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                                            </svg>
                                                            Payment
                                                        </span>
                                                    @elseif($transaction->type === 'charge')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                            </svg>
                                                            Charge
                                                        </span>
                                                    @elseif($transaction->type === 'credit')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                            </svg>
                                                            Credit
                                                        </span>
                                                    @elseif($transaction->type === 'write_off')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                            </svg>
                                                            Write-off
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                            {{ ucfirst($transaction->type) }}
                                                        </span>
                                                    @endif
                                                    
                                                    <!-- Transaction Description -->
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm text-gray-900 truncate">{{ $transaction->description }}</p>
                                                        <div class="flex items-center space-x-2 text-xs text-gray-500 mt-1">
                                                            <span>{{ $transaction->created_at->format('M j, Y g:i A') }}</span>
                                                            @if($transaction->createdBy)
                                                                <span>•</span>
                                                                <span>by {{ $transaction->createdBy->full_name }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Transaction Amount and Actions -->
                                            <div class="text-right ml-4">
                                                <div class="flex items-center justify-end space-x-2">
                                                    <div>
                                                        <div class="text-sm font-semibold {{ $transaction->isCredit() ? 'text-green-600' : 'text-red-600' }}">
                                                            {{ $transaction->isCredit() ? '+' : '-' }}${{ $transaction->formatted_amount }}
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            Balance: ${{ $transaction->formatted_balance_after }}
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Review Flag Button -->
                                                    @if(!$transaction->flagged_for_review)
                                                        <button 
                                                            wire:click="openReviewModal({{ $transaction->id }})"
                                                            class="text-gray-400 hover:text-yellow-600 transition-colors duration-150"
                                                            title="Flag for review"
                                                        >
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path>
                                                            </svg>
                                                        </button>
                                                    @else
                                                        <div class="flex items-center text-yellow-600" title="Flagged for review">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path>
                                                            </svg>
                                                        </div>
                                                    @endif
                                                </div>
                                                
                                                <!-- Review Status -->
                                                @if($transaction->flagged_for_review)
                                                    <div class="text-xs mt-1">
                                                        @if($transaction->review_resolved)
                                                            <span class="text-green-600">✓ Resolved</span>
                                                        @else
                                                            <span class="text-yellow-600">⏳ Under Review</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <!-- Transaction Footer -->
                        @if($recentTransactions->count() >= 10)
                        <div class="px-4 py-2 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                            <p class="text-xs text-gray-500 text-center">
                                Showing recent transactions. Contact support for complete transaction history.
                            </p>
                        </div>
                        @endif
                    </div>
                @else
                    <p class="text-sm text-gray-500 italic">No recent transactions found.</p>
                @endif
            </div>
        @endif
    </div>

    <!-- Transaction Review Modal -->
    @if($showReviewModal && $selectedTransaction)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeReviewModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Request Transaction Review</h3>
                                <div class="mt-2">
                                    <div class="bg-gray-50 rounded-lg p-3 mb-4">
                                        <h4 class="text-sm font-medium text-gray-900 mb-2">Transaction Details</h4>
                                        <div class="text-sm text-gray-600 space-y-1">
                                            <p><strong>Date:</strong> {{ $selectedTransaction->created_at->format('M j, Y g:i A') }}</p>
                                            <p><strong>Type:</strong> {{ ucfirst($selectedTransaction->type) }}</p>
                                            <p><strong>Amount:</strong> 
                                                <span class="{{ $selectedTransaction->isCredit() ? 'text-green-600' : 'text-red-600' }}">
                                                    {{ $selectedTransaction->isCredit() ? '+' : '-' }}${{ $selectedTransaction->formatted_amount }}
                                                </span>
                                            </p>
                                            <p><strong>Description:</strong> {{ $selectedTransaction->description }}</p>
                                        </div>
                                    </div>
                                    
                                    <p class="text-sm text-gray-500 mb-4">
                                        Please explain why you believe this transaction needs to be reviewed. Our admin team will investigate your concern and contact you if needed.
                                    </p>
                                    
                                    <div>
                                        <label for="reviewReason" class="block text-sm font-medium text-gray-700 mb-2">Reason for Review Request</label>
                                        <textarea 
                                            wire:model="reviewReason"
                                            id="reviewReason"
                                            rows="4"
                                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                                            placeholder="Please describe your concern about this transaction..."
                                        ></textarea>
                                        @error('reviewReason') 
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            wire:click="submitReviewRequest"
                            type="button" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Submit Review Request
                        </button>
                        <button 
                            wire:click="closeReviewModal"
                            type="button" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>