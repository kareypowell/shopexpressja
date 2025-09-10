<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-medium text-gray-900">Audit Trail</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Complete history of manifest locking and unlocking operations
                </p>
            </div>
            
            @can('viewAudit', $manifest)
                <button wire:click="exportAuditTrail" 
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export CSV
                </button>
            @endcan
        </div>

        <!-- Filters Section -->
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input wire:model="search" 
                           type="text" 
                           id="search"
                           placeholder="Search reason or user..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                </div>

                <!-- Action Filter -->
                <div>
                    <label for="actionFilter" class="block text-sm font-medium text-gray-700 mb-1">Action</label>
                    <select wire:model="actionFilter" 
                            id="actionFilter"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        <option value="">All Actions</option>
                        @foreach($actions as $action)
                            <option value="{{ $action['value'] }}">{{ $action['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- User Filter -->
                <div>
                    <label for="userFilter" class="block text-sm font-medium text-gray-700 mb-1">User</label>
                    <select wire:model="userFilter" 
                            id="userFilter"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user['value'] }}">{{ $user['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label for="dateFrom" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input wire:model="dateFrom" 
                           type="date" 
                           id="dateFrom"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                </div>

                <!-- Date To -->
                <div>
                    <label for="dateTo" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input wire:model="dateTo" 
                           type="date" 
                           id="dateTo"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                </div>
            </div>

            <!-- Filter Actions -->
            <div class="flex justify-between items-center mt-4">
                <button wire:click="clearFilters" 
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Clear Filters
                </button>

                <div class="text-sm text-gray-600">
                    Showing {{ $audits->count() }} of {{ $audits->total() }} records
                </div>
            </div>
        </div>

        @error('export')
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative">
                {{ $message }}
            </div>
        @enderror

        <!-- Audit Trail Table -->
        @if($audits->count() > 0)
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date & Time
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Action
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                User
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Reason
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($audits as $audit)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div>
                                        <div class="font-medium">{{ $audit->performed_at->format('M j, Y') }}</div>
                                        <div class="text-gray-500">{{ $audit->performed_at->format('g:i A') }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($audit->action === 'unlocked') bg-green-100 text-green-800
                                        @elseif($audit->action === 'closed') bg-red-100 text-red-800
                                        @elseif($audit->action === 'auto_complete') bg-blue-100 text-blue-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        @if($audit->action === 'unlocked')
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                                            </svg>
                                        @elseif($audit->action === 'closed')
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                            </svg>
                                        @elseif($audit->action === 'auto_complete')
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        @endif
                                        {{ $audit->action_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div>
                                        <div class="font-medium">{{ $audit->user->name ?? 'System' }}</div>
                                        @if($audit->user)
                                            <div class="text-gray-500">{{ $audit->user->email }}</div>
                                        @else
                                            <div class="text-gray-500">N/A</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="max-w-xs">
                                        <p class="truncate" title="{{ $audit->reason }}">{{ $audit->reason }}</p>
                                        @if(strlen($audit->reason) > 50)
                                            <button class="text-indigo-600 hover:text-indigo-900 text-xs mt-1" 
                                                    onclick="alert('{{ addslashes($audit->reason) }}')">
                                                Show full reason
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $audits->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No audit records found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if($search || $actionFilter || $userFilter)
                        Try adjusting your filters to see more results.
                    @else
                        No locking operations have been performed on this manifest yet.
                    @endif
                </p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:load', function () {
        Livewire.on('downloadFile', function (data) {
            const blob = new Blob([data.content], { type: data.mimeType });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = data.filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        });
    });
</script>
@endpush