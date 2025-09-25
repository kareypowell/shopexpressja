<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4">



        <!-- Transaction Summary - Compact View -->
        <div class="mb-6">
            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900">Transaction History</h4>
                        <p class="text-sm text-gray-600">{{ $recentTransactions->count() }} recent transactions</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button 
                        wire:click="toggleTransactions"
                        class="inline-flex items-center px-3 py-2 sm:px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                    >
                        <svg class="w-4 h-4 mr-1 sm:mr-2 {{ $showTransactions ? 'rotate-180' : '' }} transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                        <span class="hidden xs:inline">{{ $showTransactions ? 'Hide' : 'View' }} Transactions</span>
                        <span class="xs:hidden">{{ $showTransactions ? 'Hide' : 'View' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Transaction List - Hidden by Default -->
        @if($showTransactions && $recentTransactions->count() > 0)
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <!-- Transaction Header -->
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <h4 class="text-lg font-medium text-gray-900">Recent Transactions</h4>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-500">{{ $recentTransactions->count() }} transactions</span>
                        </div>
                    </div>
                </div>
                
                <!-- Transaction List -->
                <div class="divide-y divide-gray-200">
                    @foreach($recentTransactions as $transaction)
                        <div class="px-4 sm:px-6 py-4 hover:bg-gray-50 transition-colors duration-150">
                            <!-- Mobile Layout -->
                            <div class="block sm:hidden">
                                <div class="flex items-start justify-between mb-3">
                                    <!-- Transaction Type Badge -->
                                    @if($transaction->type === 'payment')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                            </svg>
                                            Payment
                                        </span>
                                    @elseif($transaction->type === 'charge')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                            </svg>
                                            Charge
                                        </span>
                                    @elseif($transaction->type === 'credit')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                            </svg>
                                            Credit
                                        </span>
                                    @elseif($transaction->type === 'write_off')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Write-off
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                    @endif
                                    
                                    <!-- Amount -->
                                    <div class="text-right">
                                        <div class="text-lg font-bold {{ $transaction->isCredit() ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $transaction->isCredit() ? '+' : '-' }}${{ $transaction->formatted_amount }}
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                <div class="mb-2">
                                    <p class="text-sm font-medium text-gray-900 mb-1">{{ $transaction->description }}</p>
                                    <p class="text-xs text-gray-500">{{ $transaction->created_at->format('M j, Y g:i A') }}</p>
                                </div>
                                
                                <!-- Bottom Row -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xs text-gray-500">Balance: ${{ $transaction->formatted_balance_after }}</span>
                                        @if($transaction->flagged_for_review)
                                            @if($transaction->review_resolved)
                                                <span class="inline-flex items-center text-xs text-green-600">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Resolved
                                                </span>
                                            @else
                                                <span class="inline-flex items-center text-xs text-yellow-600">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Under Review
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="flex items-center space-x-2">
                                        @if($transaction->type === 'payment' && $transaction->reference_type === 'package_distribution')
                                            <a 
                                                href="{{ route('customer.transaction.receipt', $transaction->id) }}"
                                                target="_blank"
                                                class="p-1.5 text-blue-600 hover:text-blue-900 hover:bg-blue-50 rounded-full transition-colors duration-150"
                                                title="View Receipt PDF"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                            </a>
                                        @endif
                                        
                                        <!-- Review Flag Button -->
                                        @if(!$transaction->flagged_for_review)
                                            <button 
                                                wire:click="openReviewModal({{ $transaction->id }})"
                                                class="p-1.5 text-gray-400 hover:text-yellow-600 hover:bg-yellow-50 rounded-full transition-colors duration-150"
                                                title="Flag for review"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path>
                                                </svg>
                                            </button>
                                        @else
                                            <div class="p-1.5 text-yellow-600" title="Flagged for review">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Desktop Layout -->
                            <div class="hidden sm:flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-4">
                                        <!-- Transaction Type Badge -->
                                        @if($transaction->type === 'payment')
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                                </svg>
                                                Payment
                                            </span>
                                        @elseif($transaction->type === 'charge')
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                </svg>
                                                Charge
                                            </span>
                                        @elseif($transaction->type === 'credit')
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                </svg>
                                                Credit
                                            </span>
                                        @elseif($transaction->type === 'write_off')
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Write-off
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                                {{ ucfirst($transaction->type) }}
                                            </span>
                                        @endif
                                        
                                        <!-- Transaction Description -->
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900">{{ $transaction->description }}</p>
                                            <div class="flex items-center space-x-2 text-sm text-gray-500 mt-1">
                                                <span>{{ $transaction->created_at->format('M j, Y g:i A') }}</span>
                                                @if($transaction->createdBy)
                                                    <span>•</span>
                                                    <span>by {{ $transaction->createdBy->full_name }}</span>
                                                @endif
                                                @if($transaction->flagged_for_review)
                                                    <span>•</span>
                                                    @if($transaction->review_resolved)
                                                        <span class="inline-flex items-center text-green-600">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            Review Resolved
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center text-yellow-600">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            Under Review
                                                        </span>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Transaction Amount and Actions -->
                                <div class="text-right ml-6">
                                    <div class="flex items-center justify-end space-x-3">
                                        <div>
                                            <div class="text-lg font-bold {{ $transaction->isCredit() ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $transaction->isCredit() ? '+' : '-' }}${{ $transaction->formatted_amount }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                Balance: ${{ $transaction->formatted_balance_after }}
                                            </div>
                                        </div>
                                        
                                        <!-- Actions -->
                                        <div class="flex items-center space-x-2">
                                            @if($transaction->type === 'payment' && $transaction->reference_type === 'package_distribution')
                                                <a 
                                                    href="{{ route('customer.transaction.receipt', $transaction->id) }}"
                                                    target="_blank"
                                                    class="p-2 text-blue-600 hover:text-blue-900 hover:bg-blue-50 rounded-full transition-colors duration-150"
                                                    title="View Receipt PDF"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                </a>
                                            @endif
                                            
                                            <!-- Review Flag Button -->
                                            @if(!$transaction->flagged_for_review)
                                                <button 
                                                    wire:click="openReviewModal({{ $transaction->id }})"
                                                    class="p-2 text-gray-400 hover:text-yellow-600 hover:bg-yellow-50 rounded-full transition-colors duration-150"
                                                    title="Flag for review"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path>
                                                    </svg>
                                                </button>
                                            @else
                                                <div class="p-2 text-yellow-600" title="Flagged for review">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Transaction Footer -->
                @if($recentTransactions->count() >= 10)
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <p class="text-sm text-gray-500 text-center">
                        Showing recent transactions. Contact support for complete transaction history.
                    </p>
                </div>
                @endif
            </div>
        @elseif($showTransactions)
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <div class="px-6 py-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No transactions found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Your transaction history will appear here once you start using our services.
                    </p>
                </div>
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