<div class="bg-white shadow-sm rounded-lg">
    <!-- Header with controls -->
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
            <div class="flex items-center space-x-4">
                <h3 class="text-lg font-semibold text-gray-900">Report Data</h3>
                <div class="text-sm text-gray-600">
                    {{ $totalRecords }} records
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
                <!-- Search -->
                <div class="relative">
                    <input 
                        type="text" 
                        wire:model.debounce.300ms="search"
                        placeholder="Search records..."
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Per page selector -->
                <select wire:model="perPage" class="block w-full sm:w-auto pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="10">10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                </select>

                <!-- Column selector -->
                <div class="relative" x-data="{ open: @entangle('showColumnSelector') }">
                    <button 
                        @click="open = !open"
                        type="button" 
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                        </svg>
                        Columns
                    </button>
                    
                    <div 
                        x-show="open" 
                        @click.away="open = false"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 z-10 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5"
                    >
                        <div class="py-1">
                            @foreach($availableColumns as $key => $label)
                                <label class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        wire:model="selectedColumns" 
                                        value="{{ $key }}"
                                        class="mr-3 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    >
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Export dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button 
                        @click="open = !open"
                        type="button" 
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export
                        <svg class="ml-2 -mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    
                    <div 
                        x-show="open" 
                        @click.away="open = false"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 z-10 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5"
                    >
                        <div class="py-1">
                            <button 
                                wire:click="exportAll('csv')"
                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            >
                                Export All as CSV
                            </button>
                            <button 
                                wire:click="exportAll('pdf')"
                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            >
                                Export All as PDF
                            </button>
                            @if(count($selectedRows) > 0)
                                <hr class="my-1">
                                <button 
                                    wire:click="exportSelected('csv')"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                >
                                    Export Selected as CSV ({{ count($selectedRows) }})
                                </button>
                                <button 
                                    wire:click="exportSelected('pdf')"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                >
                                    Export Selected as PDF ({{ count($selectedRows) }})
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    @if($paginatedData->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <!-- Select all checkbox -->
                        <th class="px-6 py-3 text-left">
                            <input 
                                type="checkbox" 
                                wire:model="selectAll"
                                wire:click="selectAllRows"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            >
                        </th>
                        
                        @foreach($selectedColumns as $column)
                            @if(isset($availableColumns[$column]))
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <button 
                                        wire:click="sortBy('{{ $column }}')"
                                        class="group inline-flex items-center space-x-1 text-left font-medium text-gray-500 uppercase tracking-wider hover:text-gray-700"
                                    >
                                        <span>{{ $availableColumns[$column] }}</span>
                                        <span class="ml-2 flex-none rounded text-gray-400 group-hover:text-gray-500">
                                            @if($sortField === $column)
                                                @if($sortDirection === 'asc')
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                    </svg>
                                                @else
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                @endif
                                            @else
                                                <svg class="h-4 w-4 opacity-0 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                                </svg>
                                            @endif
                                        </span>
                                    </button>
                                </th>
                            @endif
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($paginatedData as $row)
                        <tr class="hover:bg-gray-50 {{ in_array($row['id'] ?? '', $selectedRows) ? 'bg-blue-50' : '' }}">
                            <!-- Row checkbox -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input 
                                    type="checkbox" 
                                    wire:click="toggleRowSelection('{{ $row['id'] ?? '' }}')"
                                    {{ in_array($row['id'] ?? '', $selectedRows) ? 'checked' : '' }}
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                >
                            </td>
                            
                            @foreach($selectedColumns as $column)
                                @if(isset($availableColumns[$column]))
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if($column === 'manifest_number')
                                            <div class="flex items-center space-x-2">
                                                <span class="font-medium">{{ $row[$column] ?? 'N/A' }}</span>
                                                @if($reportType === 'sales_collections' && isset($row['id']))
                                                    <button wire:click="showManifestDetails({{ $row['id'] }})"
                                                            class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                                        View Packages
                                                    </button>
                                                @endif
                                            </div>
                                        @elseif($column === 'total_packages' && $reportType === 'sales_collections')
                                            <div class="flex items-center space-x-2">
                                                <span>{{ $row[$column] ?? 0 }}</span>
                                                @if(isset($row['id']))
                                                    <button wire:click="showManifestDetails({{ $row['id'] }})"
                                                            class="text-sm text-blue-600 hover:text-blue-800">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                        @elseif($column === 'total_owed' || $column === 'total_collected' || $column === 'outstanding_balance' || $column === 'account_balance' || $column === 'total_spent')
                                            <div class="flex items-center justify-between">
                                                <span>${{ number_format((float)($row[$column] ?? 0), 2) }}</span>
                                                @if($reportType === 'sales_collections' && ($column === 'total_owed' || $column === 'outstanding_balance') && isset($row['id']))
                                                    <button wire:click="showManifestDetails({{ $row['id'] }})"
                                                            class="ml-2 text-xs text-gray-500 hover:text-blue-600">
                                                        Details
                                                    </button>
                                                @endif
                                            </div>
                                        @elseif($column === 'collection_rate')
                                            <div class="flex items-center space-x-2">
                                                @php $rate = (float)($row[$column] ?? 0); @endphp
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    @if($rate >= 90) bg-green-100 text-green-800
                                                    @elseif($rate >= 70) bg-yellow-100 text-yellow-800
                                                    @elseif($rate >= 50) bg-orange-100 text-orange-800
                                                    @else bg-red-100 text-red-800 @endif">
                                                    {{ number_format($rate, 1) }}%
                                                </span>
                                                @if($reportType === 'sales_collections' && isset($row['id']))
                                                    <button wire:click="showManifestDetails({{ $row['id'] }})"
                                                            class="text-xs text-gray-500 hover:text-blue-600">
                                                        Breakdown
                                                    </button>
                                                @endif
                                            </div>
                                        @elseif($column === 'total_weight')
                                            {{ number_format((float)($row[$column] ?? 0), 1) }} lbs
                                        @elseif($column === 'total_volume')
                                            {{ number_format((float)($row[$column] ?? 0), 2) }} ft³
                                        @elseif($column === 'processing_time')
                                            {{ $row[$column] ?? 'N/A' }}
                                        @elseif($column === 'efficiency_score')
                                            @php $score = (float)($row[$column] ?? 0); @endphp
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($score >= 90) bg-green-100 text-green-800
                                                @elseif($score >= 70) bg-yellow-100 text-yellow-800
                                                @else bg-red-100 text-red-800 @endif">
                                                {{ number_format($score, 1) }}%
                                            </span>
                                        @elseif($column === 'status')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($row[$column] === 'completed') bg-green-100 text-green-800
                                                @elseif($row[$column] === 'processing') bg-yellow-100 text-yellow-800
                                                @elseif($row[$column] === 'pending') bg-gray-100 text-gray-800
                                                @else bg-red-100 text-red-800 @endif">
                                                {{ ucfirst($row[$column] ?? 'Unknown') }}
                                            </span>
                                        @elseif($column === 'created_at' || $column === 'last_activity')
                                            {{ isset($row[$column]) ? \Carbon\Carbon::parse($row[$column])->format('M j, Y') : 'N/A' }}
                                        @elseif($column === 'customer_name' && $reportType === 'customer_analytics')
                                            <div class="flex items-center space-x-2">
                                                <span>{{ $row[$column] ?? 'N/A' }}</span>
                                                @if(isset($row['id']))
                                                    <button wire:click="showCustomerDetails({{ $row['id'] }})"
                                                            class="text-blue-600 hover:text-blue-800 text-xs">
                                                        View Profile
                                                    </button>
                                                @endif
                                            </div>
                                        @else
                                            {{ $row[$column] ?? 'N/A' }}
                                        @endif
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center text-sm text-gray-700">
                    <span>Showing {{ $paginatedData->firstItem() }} to {{ $paginatedData->lastItem() }} of {{ $paginatedData->total() }} results</span>
                </div>
                
                <div class="flex items-center space-x-2">
                    {{ $paginatedData->links() }}
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No data available</h3>
            <p class="mt-1 text-sm text-gray-500">
                @if($search)
                    No results found for "{{ $search }}". Try adjusting your search terms.
                @else
                    Try adjusting your filters or date range.
                @endif
            </p>
            @if($search)
                <button 
                    wire:click="$set('search', '')"
                    class="mt-2 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    Clear search
                </button>
            @endif
        </div>
    @endif

    <!-- Loading overlay -->
    <div wire:loading class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center">
        <div class="flex items-center space-x-2">
            <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span class="text-sm text-gray-600">Loading...</span>
        </div>
    </div>
</div>