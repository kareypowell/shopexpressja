<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Audit Logs</h1>
                <p class="mt-1 text-sm text-gray-600">Monitor and review system activities and user actions</p>
            </div>
            <div class="text-sm text-gray-500">
                Total: {{ $auditLogs->total() }} entries
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <!-- Search -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" 
                       wire:model.debounce.300ms="search" 
                       id="search"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       placeholder="Search logs...">
            </div>

            <!-- Event Type -->
            <div>
                <label for="eventType" class="block text-sm font-medium text-gray-700 mb-1">Event Type</label>
                <select wire:model="eventType" 
                        id="eventType"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Event Types</option>
                    @foreach($eventTypes as $type)
                        <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Action -->
            <div>
                <label for="action" class="block text-sm font-medium text-gray-700 mb-1">Action</label>
                <select wire:model="action" 
                        id="action"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Actions</option>
                    @foreach($actions as $actionType)
                        <option value="{{ $actionType }}">{{ ucfirst($actionType) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- User -->
            <div>
                <label for="userId" class="block text-sm font-medium text-gray-700 mb-1">User</label>
                <select wire:model="userId" 
                        id="userId"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->full_name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Model Type -->
            <div>
                <label for="auditableType" class="block text-sm font-medium text-gray-700 mb-1">Model Type</label>
                <select wire:model="auditableType" 
                        id="auditableType"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Models</option>
                    @foreach($auditableTypes as $type)
                        <option value="{{ $type }}">{{ class_basename($type) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Date From -->
            <div>
                <label for="dateFrom" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                <input type="date" 
                       wire:model="dateFrom" 
                       id="dateFrom"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- Date To -->
            <div>
                <label for="dateTo" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                <input type="date" 
                       wire:model="dateTo" 
                       id="dateTo"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <div class="mt-4 flex justify-between items-center">
            <button wire:click="clearFilters" 
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Clear Filters
            </button>

            <div class="flex items-center space-x-2">
                <label for="perPage" class="text-sm text-gray-700">Per page:</label>
                <select wire:model="perPage" 
                        id="perPage"
                        class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Timestamp
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            User
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Event Type
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Action
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Model
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            IP Address
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($auditLogs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div>{{ $log->created_at->format('M j, Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $log->created_at->format('g:i A') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($log->user)
                                    <div class="text-sm text-gray-900">{{ $log->user->full_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $log->user->email }}</div>
                                @else
                                    <span class="text-sm text-gray-500">System</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($log->event_type === 'authentication') bg-blue-100 text-blue-800
                                    @elseif($log->event_type === 'authorization') bg-purple-100 text-purple-800
                                    @elseif($log->event_type === 'security_event') bg-red-100 text-red-800
                                    @elseif(str_contains($log->event_type, 'model_')) bg-green-100 text-green-800
                                    @elseif($log->event_type === 'business_action') bg-yellow-100 text-yellow-800
                                    @elseif($log->event_type === 'financial_transaction') bg-indigo-100 text-indigo-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $log->event_type)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ ucfirst($log->action) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($log->auditable_type)
                                    <div class="text-sm text-gray-900">{{ class_basename($log->auditable_type) }}</div>
                                    @if($log->auditable_id)
                                        <div class="text-xs text-gray-500">ID: {{ $log->auditable_id }}</div>
                                    @endif
                                @else
                                    <span class="text-sm text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $log->ip_address ?: '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button class="text-blue-600 hover:text-blue-900" 
                                        onclick="alert('Detailed view will be implemented in the next task')">
                                    View Details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-lg font-medium">No audit logs found</p>
                                    <p class="text-sm">Try adjusting your filters or date range</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($auditLogs->hasPages())
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $auditLogs->links() }}
            </div>
        @endif
    </div>
</div>