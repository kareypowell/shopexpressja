<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header --}}
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
                <p class="mt-2 text-sm text-gray-600">Welcome back, {{ $user->first_name ?? 'Admin' }}</p>
            </div>
            
            <div class="flex items-center space-x-4">
                {{-- Dashboard Controls --}}
                <button 
                    wire:click="refreshDashboard" 
                    wire:loading.attr="disabled"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                >
                    <svg wire:loading.remove class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <svg wire:loading class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove>Refresh</span>
                    <span wire:loading>Refreshing...</span>
                </button>
                
                <button 
                    wire:click="resetDashboard"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Reset
                </button>
            </div>
        </div>

        {{-- Error Display --}}
        @if($error)
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Error</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>{{ $error }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Dashboard Filters --}}
        <div class="mb-8">
            @if($this->shouldLoadComponent('filters') || !$this->lazyLoadComponents)
                @livewire('dashboard-filters', ['filters' => $currentFilters], key('dashboard-filters'))
            @endif
        </div>

        {{-- Dashboard Grid Layout --}}
        <div class="space-y-6">
            {{-- Full Width Components --}}
            @foreach($sortedComponents as $componentName => $config)
                @if($config['enabled'] && $config['size'] === 'full')
                    <div class="dashboard-widget w-full" data-component="{{ $componentName }}">
                        @if($componentName === 'system_status')
                            {{-- System Status Section --}}
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h2 class="text-xl font-semibold text-gray-900">System Status</h2>
                                    <div class="text-xs text-gray-500">
                                        Last updated: {{ $systemStatus['last_updated'] ?? 'Unknown' }}
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    {{-- Database Status --}}
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0">
                                                    @if(($systemStatus['database']['status'] ?? 'error') === 'healthy')
                                                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                                    @elseif(($systemStatus['database']['status'] ?? 'error') === 'warning')
                                                        <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                                    @else
                                                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                                    @endif
                                                </div>
                                                <div class="ml-3">
                                                    <h3 class="text-sm font-medium text-gray-900">Database</h3>
                                                    <p class="text-xs text-gray-600">{{ $systemStatus['database']['message'] ?? 'Unknown' }}</p>
                                                </div>
                                            </div>
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                                            </svg>
                                        </div>
                                    </div>

                                    {{-- Cache Status --}}
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0">
                                                    @if(($systemStatus['cache']['status'] ?? 'error') === 'healthy')
                                                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                                    @elseif(($systemStatus['cache']['status'] ?? 'error') === 'warning')
                                                        <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                                    @else
                                                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                                    @endif
                                                </div>
                                                <div class="ml-3">
                                                    <h3 class="text-sm font-medium text-gray-900">Cache</h3>
                                                    <p class="text-xs text-gray-600">{{ $systemStatus['cache']['message'] ?? 'Unknown' }}</p>
                                                </div>
                                            </div>
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                            </svg>
                                        </div>
                                    </div>

                                    {{-- Memory Usage --}}
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0">
                                                    @if(($systemStatus['memory_usage']['status'] ?? 'error') === 'healthy')
                                                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                                    @elseif(($systemStatus['memory_usage']['status'] ?? 'error') === 'warning')
                                                        <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                                    @else
                                                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                                    @endif
                                                </div>
                                                <div class="ml-3">
                                                    <h3 class="text-sm font-medium text-gray-900">Memory</h3>
                                                    <p class="text-xs text-gray-600">{{ $systemStatus['memory_usage']['usage'] ?? 'N/A' }} ({{ $systemStatus['memory_usage']['percentage'] ?? 0 }}%)</p>
                                                </div>
                                            </div>
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                                            </svg>
                                        </div>
                                    </div>

                                    {{-- Disk Space --}}
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0">
                                                    @if(($systemStatus['disk_space']['status'] ?? 'error') === 'healthy')
                                                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                                    @elseif(($systemStatus['disk_space']['status'] ?? 'error') === 'warning')
                                                        <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                                    @else
                                                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                                    @endif
                                                </div>
                                                <div class="ml-3">
                                                    <h3 class="text-sm font-medium text-gray-900">Storage</h3>
                                                    <p class="text-xs text-gray-600">{{ $systemStatus['disk_space']['free'] ?? 'N/A' }} free</p>
                                                </div>
                                            </div>
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @elseif($componentName === 'metrics')
                            @livewire('dashboard-metrics', ['filters' => $currentFilters], key('dashboard-metrics'))
                        @elseif($componentName === 'financial_analytics')
                            @livewire('financial-analytics', ['filters' => $currentFilters], key('financial-analytics'))
                        @endif
                    </div>
                @endif
            @endforeach

            {{-- Half Width Components Grid --}}
            @php
                $halfWidthComponents = collect($sortedComponents)->filter(function($config, $name) {
                    return $config['enabled'] && $config['size'] === 'half';
                });
            @endphp

            @if($halfWidthComponents->count() > 0)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach($halfWidthComponents as $componentName => $config)
                        <div class="dashboard-widget" data-component="{{ $componentName }}">
                            @if($componentName === 'customer_analytics')
                                @livewire('customer-analytics', ['filters' => $currentFilters], key('customer-analytics'))
                            @elseif($componentName === 'shipment_analytics')
                                @livewire('shipment-analytics', ['filters' => $currentFilters], key('shipment-analytics'))
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Component Toggle Section --}}
        <div class="mt-8 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Dashboard Components</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($sortedComponents as $componentName => $config)
                    @if($componentName !== 'system_status') {{-- System status is always enabled --}}
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    @if($componentName === 'metrics')
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    @elseif($componentName === 'customer_analytics')
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                        </svg>
                                    @elseif($componentName === 'shipment_analytics')
                                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                    @elseif($componentName === 'financial_analytics')
                                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                    @endif
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-gray-900">{{ ucwords(str_replace('_', ' ', $componentName)) }}</h4>
                                </div>
                            </div>
                            <button 
                                wire:click="toggleComponent('{{ $componentName }}')"
                                class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 {{ $config['enabled'] ? 'bg-blue-600' : 'bg-gray-200' }}"
                            >
                                <span class="sr-only">Toggle {{ $componentName }}</span>
                                <span class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200 {{ $config['enabled'] ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="mt-4 text-sm text-gray-600">
                <p>Toggle components on/off to customize your dashboard. System status is always enabled for monitoring.</p>
            </div>
        </div>

        {{-- Loading Overlay --}}
        <div wire:loading.flex class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 items-center justify-center">
            <div class="bg-white rounded-lg p-6 shadow-lg">
                <div class="flex items-center">
                    <svg class="animate-spin h-5 w-5 text-blue-600 mr-3" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-gray-900">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>