<div class="dashboard-metrics">
    {{-- Header Section --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Dashboard Overview</h2>
            <p class="text-sm text-gray-600 mt-1">{{ $this->getPeriodLabel() }}</p>
        </div>
        <button 
            wire:click="refreshMetrics" 
            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 disabled:opacity-50"
            wire:loading.attr="disabled"
        >
            <svg wire:loading.remove class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <svg wire:loading class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span wire:loading.remove>Refresh</span>
            <span wire:loading>Loading...</span>
        </button>
    </div>

    {{-- Error State --}}
    @if($error)
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Error Loading Metrics</h3>
                    <p class="text-sm text-red-700 mt-1">{{ $error }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Loading State --}}
    @if($isLoading)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            @for($i = 0; $i < 8; $i++)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 animate-pulse">
                    <div class="flex items-center justify-between mb-4">
                        <div class="h-4 bg-gray-200 rounded w-20"></div>
                        <div class="h-6 w-6 bg-gray-200 rounded"></div>
                    </div>
                    <div class="h-8 bg-gray-200 rounded w-16 mb-2"></div>
                    <div class="h-4 bg-gray-200 rounded w-24"></div>
                </div>
            @endfor
        </div>
    @else
        {{-- Metrics Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Customer Metrics --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-600">Total Customers</h3>
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-gray-900 mb-2">{{ $this->formatNumber($metrics['customers']['total']) }}</div>
                <div class="flex items-center text-sm">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $this->getTrendClasses($metrics['customers']['growth_percentage']) }}">
                        <span class="mr-1">{{ $this->getTrendIcon($metrics['customers']['growth_percentage']) }}</span>
                        {{ $this->getFormattedPercentage($metrics['customers']['growth_percentage']) }}
                    </span>
                    <span class="ml-2 text-gray-600">vs previous period</span>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-600">Active Customers</h3>
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-gray-900 mb-2">{{ $this->formatNumber($metrics['customers']['active']) }}</div>
                <div class="text-sm text-gray-600">
                    {{ $this->formatNumber($metrics['customers']['new_this_period']) }} new this period
                </div>
            </div>

            {{-- Package Metrics --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-600">Total Packages</h3>
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-gray-900 mb-2">{{ $this->formatNumber($metrics['packages']['total']) }}</div>
                <div class="text-sm text-gray-600">
                    {{ $this->formatNumber($metrics['packages']['in_transit']) }} in transit
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-600">Delivered Packages</h3>
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l6 6 10-10"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-gray-900 mb-2">{{ $this->formatNumber($metrics['packages']['delivered']) }}</div>
                <div class="text-sm text-gray-600">
                    {{ $metrics['packages']['processing_time_avg'] }} days avg processing
                </div>
            </div>

            {{-- Revenue Metrics --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-600">Revenue</h3>
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-gray-900 mb-2">{{ $this->formatCurrency($metrics['revenue']['current_period']) }}</div>
                <div class="flex items-center text-sm">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $this->getTrendClasses($metrics['revenue']['growth_percentage']) }}">
                        <span class="mr-1">{{ $this->getTrendIcon($metrics['revenue']['growth_percentage']) }}</span>
                        {{ $this->getFormattedPercentage($metrics['revenue']['growth_percentage']) }}
                    </span>
                    <span class="ml-2 text-gray-600">vs previous period</span>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-600">Average Order Value</h3>
                    <div class="p-2 bg-indigo-100 rounded-lg">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-gray-900 mb-2">{{ $this->formatCurrency($metrics['revenue']['average_order_value']) }}</div>
                <div class="text-sm text-gray-600">
                    {{ $this->formatNumber($metrics['revenue']['total_orders']) }} total orders
                </div>
            </div>

            {{-- Package Status Breakdown --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-600">Pending Packages</h3>
                    <div class="p-2 bg-orange-100 rounded-lg">
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-gray-900 mb-2">{{ $this->formatNumber($metrics['packages']['pending']) }}</div>
                <div class="text-sm text-gray-600">
                    {{ $this->formatNumber($metrics['packages']['delayed']) }} delayed
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-600">Inactive Customers</h3>
                    <div class="p-2 bg-gray-100 rounded-lg">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L5.636 5.636"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-gray-900 mb-2">{{ $this->formatNumber($metrics['customers']['inactive']) }}</div>
                <div class="text-sm text-gray-600">
                    {{ number_format(($metrics['customers']['inactive'] / max($metrics['customers']['total'], 1)) * 100, 1) }}% of total
                </div>
            </div>
        </div>
    @endif

    {{-- Quick Stats Summary --}}
    @if(!$isLoading && !$error)
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6 border border-blue-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Summary</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div class="flex items-center">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mr-3"></div>
                    <span class="text-gray-700">
                        <strong>{{ $this->formatNumber($metrics['customers']['active']) }}</strong> active customers out of <strong>{{ $this->formatNumber($metrics['customers']['total']) }}</strong> total
                    </span>
                </div>
                <div class="flex items-center">
                    <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                    <span class="text-gray-700">
                        <strong>{{ $this->formatNumber($metrics['packages']['delivered']) }}</strong> packages delivered successfully
                    </span>
                </div>
                <div class="flex items-center">
                    <div class="w-2 h-2 bg-yellow-500 rounded-full mr-3"></div>
                    <span class="text-gray-700">
                        <strong>{{ $this->formatCurrency($metrics['revenue']['current_period']) }}</strong> revenue generated this period
                    </span>
                </div>
            </div>
        </div>
    @endif
</div>