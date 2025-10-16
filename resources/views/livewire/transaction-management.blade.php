<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Transaction Management</h2>
            <p class="text-gray-600">Manage customer transactions with manifest linking</p>
        </div>
        <button 
            wire:click="showCreateTransaction"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium"
        >
            Create Transaction
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4">
        <!-- First Row - Main Filters -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input 
                    type="text" 
                    wire:model="search"
                    placeholder="Search by description, customer name, or reference..."
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <!-- Transaction Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select 
                    wire:model="transactionType"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">All Types</option>
                    @foreach($transactionTypes as $type => $label)
                        <option value="{{ $type }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Customer Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                <select 
                    wire:model="selectedCustomerId"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Search customers...</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">
                            {{ $customer->full_name }} 
                            @if($customer->profile && $customer->profile->account_number)
                                ({{ $customer->profile->account_number }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Manifest Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Manifest</label>
                <select 
                    wire:model="selectedManifestId"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">All Manifests</option>
                    @foreach($manifests as $manifest)
                        <option value="{{ $manifest->id }}">
                            {{ $manifest->name }}
                            @if($manifest->type)
                                ({{ ucfirst($manifest->type) }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Review Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Review Status</label>
                <select 
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    disabled
                >
                    <option value="">All Statuses</option>
                </select>
            </div>
        </div>

        <!-- Second Row - Date Filters -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Date From -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                <input 
                    type="date" 
                    wire:model="dateFrom"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                <input 
                    type="date" 
                    wire:model="dateTo"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <!-- Clear Filters Button -->
            <div class="flex items-end">
                <button 
                    wire:click="clearFilters"
                    class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-md text-sm font-medium border border-gray-300"
                >
                    Clear Filters
                </button>
            </div>

            <!-- Empty spaces for alignment -->
            <div></div>
            <div></div>
        </div>

        <!-- Active Filters Display -->
        @if($selectedManifestId || $selectedCustomerId || $transactionType || $dateFrom || $dateTo || $search)
            <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="flex items-center space-x-2 flex-wrap">
                    <span class="text-sm text-gray-600">Active filters:</span>
                    @if($selectedManifestId)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Manifest: {{ $manifests->where('id', $selectedManifestId)->first()->name ?? 'Unknown' }}
                            <button wire:click="$set('selectedManifestId', '')" class="ml-1 text-blue-600 hover:text-blue-800">×</button>
                        </span>
                    @endif
                    @if($selectedCustomerId)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Customer: {{ $customers->where('id', $selectedCustomerId)->first()->full_name ?? 'Unknown' }}
                            <button wire:click="$set('selectedCustomerId', '')" class="ml-1 text-green-600 hover:text-green-800">×</button>
                        </span>
                    @endif
                    @if($transactionType)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                            Type: {{ $transactionTypes[$transactionType] ?? $transactionType }}
                            <button wire:click="$set('transactionType', '')" class="ml-1 text-purple-600 hover:text-purple-800">×</button>
                        </span>
                    @endif
                    @if($search)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            Search: "{{ $search }}"
                            <button wire:click="$set('search', '')" class="ml-1 text-gray-600 hover:text-gray-800">×</button>
                        </span>
                    @endif
                    @if($dateFrom || $dateTo)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            Date Range: {{ $dateFrom ?? 'Start' }} - {{ $dateTo ?? 'End' }}
                            <button wire:click="$set('dateFrom', ''); $set('dateTo', '')" class="ml-1 text-yellow-600 hover:text-yellow-800">×</button>
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>



    <!-- Transactions Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date & Time
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Customer
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Type
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Description
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Amount
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Balance After
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Manifest
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $transaction->created_at->format('M j, Y g:i A') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $transaction->user->full_name }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $transaction->user->email }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    @if($transaction->isCredit()) bg-green-100 text-green-800
                                    @else bg-red-100 text-red-800
                                    @endif
                                ">
                                    {{ ucfirst(str_replace('_', ' ', $transaction->type)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ $transaction->description }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium
                                @if($transaction->isCredit()) text-green-600
                                @else text-red-600
                                @endif
                            ">
                                @if($transaction->isCredit()) + @else - @endif
                                ${{ number_format($transaction->amount, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${{ number_format($transaction->balance_after, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($transaction->manifest)
                                    <span class="text-blue-600">
                                        {{ $transaction->manifest->name }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button class="text-indigo-600 hover:text-indigo-900">View</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                No transactions found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-3 border-t border-gray-200">
            {{ $transactions->links() }}
        </div>
    </div>

    <!-- Create Transaction Modal -->
    @if($showCreateModal)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Transaction</h3>
                
                <form wire:submit.prevent="createTransaction" class="space-y-4">
                    <!-- Customer -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Customer *</label>
                        <select 
                            wire:model="newTransactionCustomerId"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                            <option value="">Select Customer</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">
                                    {{ $customer->full_name }}
                                    @if($customer->profile && $customer->profile->account_number)
                                        ({{ $customer->profile->account_number }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('newTransactionCustomerId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Transaction Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Type *</label>
                        <select 
                            wire:model="newTransactionType"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                            @foreach($transactionTypes as $type => $label)
                                <option value="{{ $type }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('newTransactionType') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Amount -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
                        <input 
                            type="number" 
                            step="0.01"
                            wire:model="newTransactionAmount"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="0.00"
                            required
                        >
                        @error('newTransactionAmount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                        <textarea 
                            wire:model="newTransactionDescription"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            rows="3"
                            placeholder="Transaction description..."
                            required
                        ></textarea>
                        @error('newTransactionDescription') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Manifest (Optional) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Link to Manifest (Optional)</label>
                        <select 
                            wire:model="newTransactionManifestId"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">No Manifest</option>
                            @foreach($manifests as $manifest)
                                <option value="{{ $manifest->id }}">{{ $manifest->name }}</option>
                            @endforeach
                        </select>
                        @error('newTransactionManifestId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Buttons -->
                    <div class="flex justify-end space-x-3 pt-4">
                        <button 
                            type="button"
                            wire:click="hideCreateTransaction"
                            class="px-4 py-2 text-gray-600 hover:text-gray-800"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium"
                        >
                            Create Transaction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded z-50">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50">
            {{ session('error') }}
        </div>
    @endif
</div>