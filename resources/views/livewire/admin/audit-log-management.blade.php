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

    <!-- Search and Filters -->
    <div class="bg-white shadow rounded-lg" x-data="{ showFilters: false }">
        <!-- Search Bar -->
        <div class="p-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row gap-4">
                <!-- Main Search -->
                <div class="flex-1">
                    <div class="relative">
                        <input type="text" 
                               wire:model.debounce.300ms="search" 
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Search audit logs...">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="flex items-center gap-2">
                    <!-- Quick Date Filters -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Quick Dates
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <div x-show="open" @click.away="open = false" x-transition
                             class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                            <div class="py-1">
                                @foreach($quickFilters as $key => $label)
                                    <button wire:click="applyQuickFilter('{{ $key }}')" @click="open = false"
                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ $quickFilter === $key ? 'bg-blue-50 text-blue-700' : '' }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Filter Presets -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h4a1 1 0 011 1v2m-6 0h8m-8 0a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V6a2 2 0 00-2-2"></path>
                            </svg>
                            Presets
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <div x-show="open" @click.away="open = false" x-transition
                             class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                            <div class="py-1">
                                @foreach($filterPresets as $key => $label)
                                    <button wire:click="$set('filterPreset', '{{ $key }}')" @click="open = false"
                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ $filterPreset === $key ? 'bg-green-50 text-green-700' : '' }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Filters Toggle -->
                    <button @click="showFilters = !showFilters" 
                            class="inline-flex items-center px-3 py-2 border rounded-lg text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500
                                   {{ ($eventType || $action || $userId || $auditableType || $ipAddress) ? 'border-blue-300 text-blue-700 bg-blue-50' : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50' }}">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        Filters
                        @if($eventType || $action || $userId || $auditableType || $ipAddress)
                            <span class="ml-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-blue-600 rounded-full">
                                {{ collect([$eventType, $action, $userId, $auditableType, $ipAddress])->filter()->count() }}
                            </span>
                        @endif
                    </button>
                </div>
            </div>
        </div>

        <!-- Advanced Filters Panel -->
        <div x-show="showFilters" x-transition class="border-b border-gray-200">
            <div class="p-4 bg-gray-50">
                <!-- Filter Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <!-- Event Type -->
                    <div>
                        <label for="eventType" class="block text-sm font-medium text-gray-700 mb-1">Event Type</label>
                        <select wire:model="eventType" 
                                id="eventType"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
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
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
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
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->full_name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Model Type -->
                    <div>
                        <label for="auditableType" class="block text-sm font-medium text-gray-700 mb-1">Model Type</label>
                        <select wire:model="auditableType" 
                                id="auditableType"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">All Models</option>
                            @foreach($auditableTypes as $type)
                                <option value="{{ $type }}">{{ class_basename($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Date Range and IP -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <!-- Date From -->
                    <div>
                        <label for="dateFrom" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" 
                               wire:model="dateFrom" 
                               id="dateFrom"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>

                    <!-- Date To -->
                    <div>
                        <label for="dateTo" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date" 
                               wire:model="dateTo" 
                               id="dateTo"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>

                    <!-- IP Address -->
                    <div>
                        <label for="ipAddress" class="block text-sm font-medium text-gray-700 mb-1">IP Address</label>
                        <input type="text" 
                               wire:model.debounce.300ms="ipAddress" 
                               id="ipAddress"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                               placeholder="Filter by IP address...">
                    </div>
                </div>

                <!-- Advanced Search Options -->
                <div x-data="{ showAdvanced: false }">
                    <button @click="showAdvanced = !showAdvanced" 
                            class="text-sm text-blue-600 hover:text-blue-800 flex items-center mb-3">
                        <span x-text="showAdvanced ? 'Hide Advanced Search' : 'Show Advanced Search'"></span>
                        <svg class="ml-1 h-4 w-4 transform transition-transform" :class="{ 'rotate-180': showAdvanced }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    
                    <div x-show="showAdvanced" x-transition class="p-3 bg-white rounded-md border border-gray-200">
                        <p class="text-sm text-gray-600 mb-3">Search in additional fields:</p>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="searchInOldValues" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Old Values</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="searchInNewValues" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">New Values</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="searchInAdditionalData" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Additional Data</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="searchInUrl" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">URL</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="searchInUserAgent" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">User Agent</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Actions -->
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <button wire:click="clearFilters" 
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Clear Filters
                </button>

                @if($search || $eventType || $action || $userId || $auditableType || $ipAddress)
                    <div class="text-sm text-gray-600">
                        <span class="font-medium">{{ $auditLogs->total() }}</span> filtered results
                    </div>
                @endif
            </div>

            <div class="flex items-center space-x-2">
                <label for="perPage" class="text-sm text-gray-700">Show:</label>
                <select wire:model="perPage" 
                        id="perPage"
                        class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="text-sm text-gray-700">per page</span>
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
                            <button wire:click="sortBy('created_at')" class="flex items-center space-x-1 hover:text-gray-700">
                                <span>Timestamp</span>
                                @if($sortField === 'created_at')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        @endif
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <button wire:click="sortBy('user_id')" class="flex items-center space-x-1 hover:text-gray-700">
                                <span>User</span>
                                @if($sortField === 'user_id')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        @endif
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <button wire:click="sortBy('event_type')" class="flex items-center space-x-1 hover:text-gray-700">
                                <span>Event Type</span>
                                @if($sortField === 'event_type')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        @endif
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <button wire:click="sortBy('action')" class="flex items-center space-x-1 hover:text-gray-700">
                                <span>Action</span>
                                @if($sortField === 'action')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        @endif
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <button wire:click="sortBy('auditable_type')" class="flex items-center space-x-1 hover:text-gray-700">
                                <span>Model</span>
                                @if($sortField === 'auditable_type')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        @endif
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <button wire:click="sortBy('ip_address')" class="flex items-center space-x-1 hover:text-gray-700">
                                <span>IP Address</span>
                                @if($sortField === 'ip_address')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        @endif
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                @endif
                            </button>
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