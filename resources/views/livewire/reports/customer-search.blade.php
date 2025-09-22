<div class="relative" x-data="{ open: @entangle('showResults') }">
    <div class="relative">
        <input 
            type="text" 
            wire:model.debounce.300ms="search"
            @focus="$wire.set('showResults', true)"
            @blur="setTimeout(() => $wire.hideResults(), 200)"
            placeholder="Search customers by name or email..."
            class="block w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
        >
        
        <!-- Search Icon -->
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>

        <!-- Clear Button -->
        @if($selectedCustomer)
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <button 
                    wire:click="clearSelection"
                    type="button"
                    class="text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        @endif
    </div>

    <!-- Search Results Dropdown -->
    @if($showResults && count($customers) > 0)
        <div class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
            @foreach($customers as $customer)
                <button 
                    wire:click="selectCustomer({{ $customer->id }})"
                    type="button"
                    class="w-full text-left cursor-pointer select-none relative py-3 px-4 hover:bg-blue-50 focus:bg-blue-50 focus:outline-none"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-sm font-medium text-blue-600">
                                        {{ substr($customer->name, 0, 1) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ $customer->name }}
                                </p>
                                <p class="text-sm text-gray-500 truncate">
                                    {{ $customer->email }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium {{ $customer->account_balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                ${{ number_format($customer->account_balance, 2) }}
                            </p>
                            <p class="text-xs text-gray-500">
                                Since {{ $customer->created_at->format('M Y') }}
                            </p>
                        </div>
                    </div>
                </button>
            @endforeach
        </div>
    @elseif($showResults && strlen($search) >= 2 && count($customers) === 0)
        <div class="absolute z-10 mt-1 w-full bg-white shadow-lg rounded-md py-3 px-4 text-base ring-1 ring-black ring-opacity-5">
            <div class="text-center">
                <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-500">No customers found</p>
                <p class="text-xs text-gray-400">Try adjusting your search terms</p>
            </div>
        </div>
    @endif

    <!-- Selected Customer Display -->
    @if($selectedCustomer)
        <div class="mt-3 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-lg font-medium text-blue-600">
                                {{ substr($selectedCustomer->name, 0, 1) }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-blue-900">{{ $selectedCustomer->name }}</p>
                        <p class="text-sm text-blue-700">{{ $selectedCustomer->email }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium {{ $selectedCustomer->account_balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        Balance: ${{ number_format($selectedCustomer->account_balance, 2) }}
                    </p>
                    <button 
                        wire:click="clearSelection"
                        class="text-xs text-blue-600 hover:text-blue-800 focus:outline-none"
                    >
                        Change customer
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Loading indicator -->
    <div wire:loading wire:target="search" class="absolute right-3 top-3">
        <svg class="animate-spin h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
    </div>
</div>