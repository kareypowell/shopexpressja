<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Compose Broadcast Message</h2>

                <!-- Flash Messages -->
                @if (session()->has('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if (session()->has('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Composition Area -->
                    <div class="lg:col-span-2">
                        <form wire:submit.prevent="showPreview">
                            <!-- Subject -->
                            <div class="mb-6">
                                <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                                    Subject <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="subject"
                                    wire:model.defer="subject"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Enter message subject..."
                                >
                                @error('subject') 
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                                @enderror
                            </div>

                            <!-- Message Content -->
                            <div class="mb-6">
                                <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                                    Message Content <span class="text-red-500">*</span>
                                </label>
                                <div class="border border-gray-300 rounded-md">
                                    <textarea 
                                        id="content"
                                        wire:model.defer="content"
                                        rows="12"
                                        class="w-full px-3 py-2 border-0 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                                        placeholder="Enter your message content here..."
                                    ></textarea>
                                </div>
                                @error('content') 
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">
                                    You can use HTML formatting in your message content.
                                </p>
                            </div>

                            <!-- Scheduling Options -->
                            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center mb-4">
                                    <input 
                                        type="checkbox" 
                                        id="isScheduled"
                                        wire:model="isScheduled"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    >
                                    <label for="isScheduled" class="ml-2 block text-sm font-medium text-gray-700">
                                        Schedule for later
                                    </label>
                                </div>

                                @if($isScheduled)
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="scheduledDate" class="block text-sm font-medium text-gray-700 mb-1">
                                                Date <span class="text-red-500">*</span>
                                            </label>
                                            <input 
                                                type="date" 
                                                id="scheduledDate"
                                                wire:model.defer="scheduledDate"
                                                min="{{ \Carbon\Carbon::tomorrow()->format('Y-m-d') }}"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            >
                                            @error('scheduledDate') 
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="scheduledTime" class="block text-sm font-medium text-gray-700 mb-1">
                                                Time <span class="text-red-500">*</span>
                                            </label>
                                            <input 
                                                type="time" 
                                                id="scheduledTime"
                                                wire:model.defer="scheduledTime"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            >
                                            @error('scheduledTime') 
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                                            @enderror
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-wrap gap-3">
                                <button 
                                    type="button"
                                    wire:click="saveDraft"
                                    class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                                >
                                    Save Draft
                                </button>
                                
                                <button 
                                    type="submit"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                >
                                    Preview Message
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Recipient Selection Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Recipients</h3>
                            
                            <!-- Recipient Type Selection -->
                            <div class="mb-4">
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input 
                                            type="radio" 
                                            wire:model="recipientType" 
                                            value="all"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">All Customers</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input 
                                            type="radio" 
                                            wire:model="recipientType" 
                                            value="selected"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">Selected Customers</span>
                                    </label>
                                </div>
                                @error('recipientType') 
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                                @enderror
                            </div>

                            <!-- Recipient Count -->
                            <div class="mb-4 p-3 bg-white rounded border">
                                <div class="text-sm text-gray-600">Recipients:</div>
                                <div class="text-2xl font-bold text-indigo-600">{{ number_format($recipientCount) }}</div>
                            </div>

                            <!-- Customer Selection Interface -->
                            @if($recipientType === 'selected')
                                <div class="space-y-4">
                                    <!-- Search -->
                                    <div>
                                        <label for="customerSearch" class="block text-sm font-medium text-gray-700 mb-1">
                                            Search Customers
                                        </label>
                                        <input 
                                            type="text" 
                                            id="customerSearch"
                                            wire:model.debounce.300ms="customerSearch"
                                            placeholder="Search by name, email..."
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                        >
                                    </div>

                                    <!-- Selection Actions -->
                                    <div class="flex gap-2">
                                        <button 
                                            type="button"
                                            wire:click="selectAllCustomers"
                                            class="flex-1 px-3 py-1 text-xs bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200"
                                        >
                                            Select All
                                        </button>
                                        <button 
                                            type="button"
                                            wire:click="clearSelection"
                                            class="flex-1 px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200"
                                        >
                                            Clear All
                                        </button>
                                    </div>

                                    <!-- Customer List -->
                                    <div class="max-h-96 overflow-y-auto border border-gray-200 rounded">
                                        @forelse($availableCustomers as $customer)
                                            <div class="flex items-center p-2 hover:bg-gray-50 border-b border-gray-100 last:border-b-0">
                                                <input 
                                                    type="checkbox" 
                                                    wire:click="toggleCustomer({{ $customer->id }})"
                                                    @if(in_array($customer->id, $selectedCustomers)) checked @endif
                                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                                >
                                                <div class="ml-2 flex-1 min-w-0">
                                                    <div class="text-sm font-medium text-gray-900 truncate">
                                                        {{ $customer->full_name }}
                                                    </div>
                                                    <div class="text-xs text-gray-500 truncate">
                                                        {{ $customer->email }}
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="p-4 text-center text-gray-500 text-sm">
                                                No customers found
                                            </div>
                                        @endforelse
                                    </div>

                                    <!-- Pagination -->
                                    @if($availableCustomers->hasPages())
                                        <div class="mt-4">
                                            {{ $availableCustomers->links() }}
                                        </div>
                                    @endif

                                    @error('selectedCustomers') 
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                                    @enderror
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    @if($showPreview)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="hidePreview">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white" wire:click.stop>
                <div class="mt-3">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-4 border-b">
                        <h3 class="text-lg font-medium text-gray-900">Preview Message</h3>
                        <button 
                            wire:click="hidePreview"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Preview Content -->
                    <div class="mt-4">
                        <!-- Message Details -->
                        <div class="bg-gray-50 p-4 rounded-lg mb-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-gray-700">Recipients:</span>
                                    <span class="text-gray-900">{{ number_format($recipientCount) }} customers</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Type:</span>
                                    <span class="text-gray-900">{{ ucfirst($recipientType) }} customers</span>
                                </div>
                                @if($isScheduled)
                                    <div class="md:col-span-2">
                                        <span class="font-medium text-gray-700">Scheduled for:</span>
                                        <span class="text-gray-900">
                                            {{ \Carbon\Carbon::createFromFormat('Y-m-d H:i', $scheduledDate . ' ' . $scheduledTime)->format('M j, Y \a\t g:i A') }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Email Preview -->
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <div class="bg-gray-100 px-4 py-2 border-b">
                                <div class="text-sm">
                                    <span class="font-medium">Subject:</span> {{ $subject }}
                                </div>
                            </div>
                            <div class="p-4 bg-white">
                                <div class="prose max-w-none">
                                    {!! nl2br(e($content)) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                        <button 
                            wire:click="hidePreview"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                        >
                            Edit Message
                        </button>
                        
                        @if($isScheduled)
                            <button 
                                wire:click="scheduleMessage"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Schedule Message
                            </button>
                        @else
                            <button 
                                wire:click="sendNow"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                            >
                                Send Now
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>