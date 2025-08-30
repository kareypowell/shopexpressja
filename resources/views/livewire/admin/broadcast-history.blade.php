<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Broadcast History</h2>
                    <button 
                        wire:click="composeNewMessage"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Compose New Message
                    </button>
                </div>

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

                <!-- Filters -->
                <div class="mb-6 flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <input 
                            type="text" 
                            wire:model.debounce.300ms="searchTerm"
                            placeholder="Search by subject, content, or sender..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                        >
                    </div>
                    <div>
                        <select 
                            wire:model="filterStatus"
                            class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="all">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="sending">Sending</option>
                            <option value="sent">Sent</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                </div>

                <!-- Broadcasts Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Subject
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Recipients
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Sender
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($broadcasts as $broadcast)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ Str::limit($broadcast->subject, 50) }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ Str::limit(strip_tags($broadcast->content), 80) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $this->getStatusBadgeClass($broadcast->status) }}">
                                            {{ ucfirst($broadcast->status) }}
                                        </span>
                                        @if($broadcast->status === 'scheduled' && $broadcast->scheduled_at)
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ $broadcast->scheduled_at->format('M j, Y g:i A') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($broadcast->recipient_count) }}
                                        <div class="text-xs text-gray-500">
                                            {{ ucfirst($broadcast->recipient_type) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $broadcast->sender->full_name ?? 'Unknown' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div>{{ $broadcast->created_at->format('M j, Y') }}</div>
                                        <div class="text-xs">{{ $broadcast->created_at->format('g:i A') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button 
                                                wire:click="showBroadcastDetails({{ $broadcast->id }})"
                                                class="text-indigo-600 hover:text-indigo-900"
                                            >
                                                View
                                            </button>
                                            
                                            @if($broadcast->status === 'scheduled')
                                                <button 
                                                    wire:click="cancelScheduledBroadcast({{ $broadcast->id }})"
                                                    class="text-red-600 hover:text-red-900"
                                                    onclick="return confirm('Are you sure you want to cancel this scheduled message?')"
                                                >
                                                    Cancel
                                                </button>
                                            @endif
                                            
                                            @if($broadcast->status === 'failed')
                                                <button 
                                                    wire:click="resendBroadcast({{ $broadcast->id }})"
                                                    class="text-green-600 hover:text-green-900"
                                                >
                                                    Resend
                                                </button>
                                            @endif
                                            
                                            @if($broadcast->status === 'draft')
                                                <button 
                                                    wire:click="editDraft({{ $broadcast->id }})"
                                                    class="text-blue-600 hover:text-blue-900"
                                                >
                                                    Edit
                                                </button>
                                                <button 
                                                    wire:click="deleteDraft({{ $broadcast->id }})"
                                                    class="text-red-600 hover:text-red-900"
                                                    onclick="return confirm('Are you sure you want to delete this draft?')"
                                                >
                                                    Delete
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        No broadcast messages found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($broadcasts->hasPages())
                    <div class="mt-6">
                        {{ $broadcasts->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Broadcast Details Modal -->
    @if($showDetails && $selectedBroadcast)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="hideBroadcastDetails">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white" wire:click.stop>
                <div class="mt-3">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-4 border-b">
                        <h3 class="text-lg font-medium text-gray-900">Broadcast Details</h3>
                        <button 
                            wire:click="hideBroadcastDetails"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Broadcast Information -->
                    <div class="mt-4 space-y-6">
                        <!-- Basic Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Subject</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $selectedBroadcast->subject }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                <span class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $this->getStatusBadgeClass($selectedBroadcast->status) }}">
                                    {{ ucfirst($selectedBroadcast->status) }}
                                </span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sender</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $selectedBroadcast->sender->full_name ?? 'Unknown' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Recipients</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ number_format($selectedBroadcast->recipient_count) }} ({{ ucfirst($selectedBroadcast->recipient_type) }})
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Created</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $selectedBroadcast->created_at->format('M j, Y \a\t g:i A') }}</p>
                            </div>
                            @if($selectedBroadcast->scheduled_at)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Scheduled For</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $selectedBroadcast->scheduled_at->format('M j, Y \a\t g:i A') }}</p>
                                </div>
                            @endif
                            @if($selectedBroadcast->sent_at)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Sent At</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $selectedBroadcast->sent_at->format('M j, Y \a\t g:i A') }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Message Content -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Message Content</label>
                            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 max-h-64 overflow-y-auto">
                                <div class="prose max-w-none">
                                    {!! nl2br(e($selectedBroadcast->content)) !!}
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Statistics -->
                        @if($selectedBroadcast->deliveries->count() > 0)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Statistics</label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div class="bg-blue-50 p-3 rounded-lg">
                                        <div class="text-2xl font-bold text-blue-600">
                                            {{ $selectedBroadcast->deliveries->where('status', 'pending')->count() }}
                                        </div>
                                        <div class="text-sm text-blue-800">Pending</div>
                                    </div>
                                    <div class="bg-green-50 p-3 rounded-lg">
                                        <div class="text-2xl font-bold text-green-600">
                                            {{ $selectedBroadcast->deliveries->where('status', 'sent')->count() }}
                                        </div>
                                        <div class="text-sm text-green-800">Sent</div>
                                    </div>
                                    <div class="bg-red-50 p-3 rounded-lg">
                                        <div class="text-2xl font-bold text-red-600">
                                            {{ $selectedBroadcast->deliveries->where('status', 'failed')->count() }}
                                        </div>
                                        <div class="text-sm text-red-800">Failed</div>
                                    </div>
                                    <div class="bg-yellow-50 p-3 rounded-lg">
                                        <div class="text-2xl font-bold text-yellow-600">
                                            {{ $selectedBroadcast->deliveries->where('status', 'bounced')->count() }}
                                        </div>
                                        <div class="text-sm text-yellow-800">Bounced</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Selected Recipients (if applicable) -->
                        @if($selectedBroadcast->recipient_type === 'selected' && $selectedBroadcast->recipients->count() > 0)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Selected Recipients</label>
                                <div class="max-h-32 overflow-y-auto border border-gray-200 rounded">
                                    @foreach($selectedBroadcast->recipients as $recipient)
                                        <div class="px-3 py-2 border-b border-gray-100 last:border-b-0">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $recipient->customer->full_name ?? 'Unknown Customer' }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $recipient->customer->email ?? 'No email' }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                        <button 
                            wire:click="hideBroadcastDetails"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                        >
                            Close
                        </button>
                        
                        @if($selectedBroadcast->status === 'draft')
                            <button 
                                wire:click="editDraft({{ $selectedBroadcast->id }})"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            >
                                Edit Draft
                            </button>
                        @endif
                        
                        @if($selectedBroadcast->status === 'scheduled')
                            <button 
                                wire:click="cancelScheduledBroadcast({{ $selectedBroadcast->id }})"
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                onclick="return confirm('Are you sure you want to cancel this scheduled message?')"
                            >
                                Cancel Schedule
                            </button>
                        @endif
                        
                        @if($selectedBroadcast->status === 'failed')
                            <button 
                                wire:click="resendBroadcast({{ $selectedBroadcast->id }})"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                            >
                                Resend Message
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>