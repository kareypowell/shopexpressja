<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Transaction Management</h1>
            <p class="mt-1 text-sm text-gray-600">View and manage all customer transactions</p>
        </div>
        <div class="flex items-center space-x-3">
            <button 
                wire:click="clearFilters"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500"
            >
                Clear Filters
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Search -->
                <div class="lg:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input 
                        type="text" 
                        wire:model.debounce.300ms="search"
                        id="search"
                        placeholder="Search by description, customer name, or reference..."
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                    >
                </div>

                <!-- Transaction Type -->
                <div>
                    <label for="filterType" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select 
                        wire:model="filterType"
                        id="filterType"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                    >
                        <option value="">All Types</option>
                        @foreach($transactionTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Customer -->
                <div>
                    <label for="filterCustomer" class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                    <select 
                        wire:model="filterCustomer"
                        id="filterCustomer"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                    >
                        <option value="">All Customers</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Review Status -->
                <div>
                    <label for="filterReviewStatus" class="block text-sm font-medium text-gray-700 mb-1">Review Status</label>
                    <select 
                        wire:model="filterReviewStatus"
                        id="filterReviewStatus"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                    >
                        <option value="">All Statuses</option>
                        @foreach($reviewStatusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label for="filterDateFrom" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input 
                        type="date" 
                        wire:model="filterDateFrom"
                        id="filterDateFrom"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                    >
                </div>
                <div>
                    <label for="filterDateTo" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input 
                        type="date" 
                        wire:model="filterDateTo"
                        id="filterDateTo"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                    >
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-4 py-5 sm:p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date & Time
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Customer
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Description
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Amount
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Balance After
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($transactions as $transaction)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div>
                                        <div class="font-medium">{{ $transaction->created_at->format('M j, Y') }}</div>
                                        <div class="text-gray-500">{{ $transaction->created_at->format('g:i A') }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($transaction->user)
                                        <div class="text-sm font-medium text-gray-900">{{ $transaction->user->full_name }}</div>
                                        <div class="text-sm text-gray-500">{{ $transaction->user->email }}</div>
                                    @else
                                        <span class="text-sm text-gray-400">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($transaction->type === 'payment')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Payment
                                        </span>
                                    @elseif($transaction->type === 'charge')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Charge
                                        </span>
                                    @elseif($transaction->type === 'credit')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Credit
                                        </span>
                                    @elseif($transaction->type === 'write_off')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Write-off
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="max-w-xs truncate" title="{{ $transaction->description }}">
                                        {{ $transaction->description }}
                                    </div>
                                    @if($transaction->reference_type && $transaction->reference_id)
                                        <div class="text-xs text-gray-500 mt-1">
                                            Ref: {{ $transaction->reference_type }}#{{ $transaction->reference_id }}
                                        </div>
                                    @endif
                                    @if($transaction->flagged_for_review)
                                        <div class="flex items-center mt-1">
                                            @if($transaction->review_resolved)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Review Resolved
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                                                    </svg>
                                                    Flagged for Review
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <span class="{{ $transaction->isCredit() ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $transaction->isCredit() ? '+' : '-' }}${{ $transaction->formatted_amount }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                    ${{ $transaction->formatted_balance_after }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button 
                                            wire:click="viewTransaction({{ $transaction->id }})"
                                            class="text-wax-flower-600 hover:text-wax-flower-900"
                                            title="View Details"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                        @if($transaction->flagged_for_review && !$transaction->review_resolved)
                                            <button 
                                                wire:click="openReviewModal({{ $transaction->id }})"
                                                class="text-green-600 hover:text-green-900"
                                                title="Resolve Review"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </button>
                                        @endif
                                        @if($transaction->reference_type === 'package_distribution')
                                            <button 
                                                wire:click="openDisputeModal({{ $transaction->id }})"
                                                class="text-yellow-600 hover:text-yellow-900"
                                                title="Mark as Disputed"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p class="text-lg font-medium text-gray-900 mb-1">No transactions found</p>
                                        <p class="text-gray-500">Try adjusting your search or filter criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>

    <!-- Transaction Details Modal -->
    @if($showTransactionModal && $selectedTransaction)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeTransactionModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Transaction Details</h3>
                                
                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Date & Time</label>
                                            <p class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->created_at->format('M j, Y g:i A') }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Type</label>
                                            <p class="mt-1 text-sm text-gray-900">{{ ucfirst($selectedTransaction->type) }}</p>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Customer</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            {{ $selectedTransaction->user ? $selectedTransaction->user->full_name : 'N/A' }}
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Description</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->description }}</p>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Amount</label>
                                            <p class="mt-1 text-sm font-semibold {{ $selectedTransaction->isCredit() ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $selectedTransaction->isCredit() ? '+' : '-' }}${{ $selectedTransaction->formatted_amount }}
                                            </p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Balance After</label>
                                            <p class="mt-1 text-sm text-gray-900">${{ $selectedTransaction->formatted_balance_after }}</p>
                                        </div>
                                    </div>
                                    
                                    @if($selectedTransaction->reference_type && $selectedTransaction->reference_id)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Reference</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->reference_type }}#{{ $selectedTransaction->reference_id }}</p>
                                    </div>
                                    @endif
                                    
                                    @if($selectedTransaction->createdBy)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Created By</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->createdBy->full_name }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            wire:click="closeTransactionModal"
                            type="button" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-wax-flower-600 text-base font-medium text-white hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Dispute Modal -->
    @if($showDisputeModal && $selectedTransaction)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeDisputeModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Mark Transaction as Disputed</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 mb-4">
                                        This will mark the related distribution as disputed. Please provide a reason for the dispute.
                                    </p>
                                    
                                    <div>
                                        <label for="disputeReason" class="block text-sm font-medium text-gray-700 mb-2">Dispute Reason</label>
                                        <textarea 
                                            wire:model="disputeReason"
                                            id="disputeReason"
                                            rows="4"
                                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                                            placeholder="Please explain the reason for disputing this transaction..."
                                        ></textarea>
                                        @error('disputeReason') 
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            wire:click="disputeTransaction"
                            type="button" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Mark as Disputed
                        </button>
                        <button 
                            wire:click="closeDisputeModal"
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

    <!-- Review Resolution Modal -->
    @if($showReviewModal && $selectedTransaction)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeReviewModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Resolve Transaction Review</h3>
                                <div class="mt-2">
                                    <!-- Customer Review Request -->
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                                        <h4 class="text-sm font-bold text-yellow-800 mb-2">Customer's Review Request</h4>
                                        <div class="text-sm text-yellow-700 space-y-2">
                                            <p><strong>Customer:</strong> {{ $selectedTransaction->user->full_name }}</p>
                                            <p><strong>Flagged On:</strong> {{ $selectedTransaction->flagged_at->format('M j, Y g:i A') }}</p>
                                            <p><strong>Reason:</strong></p>
                                            <div class="bg-white border border-yellow-300 rounded p-3 italic">
                                                {{ $selectedTransaction->review_reason }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Transaction Details -->
                                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                        <h4 class="text-sm font-bold text-gray-900 mb-2">Transaction Details</h4>
                                        <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                                            <div>
                                                <p><strong>Date:</strong> {{ $selectedTransaction->created_at->format('M j, Y g:i A') }}</p>
                                                <p><strong>Type:</strong> {{ ucfirst($selectedTransaction->type) }}</p>
                                            </div>
                                            <div>
                                                <p><strong>Amount:</strong> 
                                                    <span class="{{ $selectedTransaction->isCredit() ? 'text-green-600' : 'text-red-600' }}">
                                                        {{ $selectedTransaction->isCredit() ? '+' : '-' }}${{ $selectedTransaction->formatted_amount }}
                                                    </span>
                                                </p>
                                                <p><strong>Balance After:</strong> ${{ $selectedTransaction->formatted_balance_after }}</p>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <p class="text-sm text-gray-600"><strong>Description:</strong> {{ $selectedTransaction->description }}</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Admin Response -->
                                    <div>
                                        <label for="adminResponse" class="block text-sm font-medium text-gray-700 mb-2">Admin Response</label>
                                        <textarea 
                                            wire:model="adminResponse"
                                            id="adminResponse"
                                            rows="4"
                                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                                            placeholder="Provide your response to the customer's review request. Explain your findings and any actions taken..."
                                        ></textarea>
                                        @error('adminResponse') 
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            wire:click="resolveReview"
                            type="button" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Resolve Review
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