<div class="manifest-summary bg-gradient-to-br from-white to-gray-50 p-8 rounded-xl shadow-lg border border-gray-100 relative" wire:poll.30s="refreshSummary">
    <!-- Enhanced Error State with Graceful Degradation -->
    @if($hasError)
        <div class="bg-gradient-to-r from-red-50 to-orange-50 border-l-4 border-red-400 rounded-xl p-6 mb-6 shadow-sm" role="alert" aria-live="assertive">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <div class="p-2 bg-red-100 rounded-full shadow-sm">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-base font-semibold text-red-800">Summary Temporarily Unavailable</h4>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Error
                        </span>
                    </div>
                    <p class="text-sm text-red-700 mb-4 leading-relaxed">
                        {{ $errorMessage ?: 'We\'re having trouble calculating the complete summary. Basic information is shown below, and you can try refreshing the data.' }}
                    </p>
                    
                    <!-- Action Buttons with Enhanced UX -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-3">
                        <button wire:click="refreshSummary" 
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span>Retry Calculation</span>
                        </button>
                        
                        <div class="text-xs text-gray-500 flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Showing available data below</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 sm:mb-8 space-y-4 sm:space-y-0">
        <div class="flex items-center space-x-3">
            <div class="p-3 bg-blue-100 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-xl sm:text-2xl font-bold text-gray-900" id="manifest-summary-title">Manifest Summary</h3>
                <p class="text-sm text-gray-500">Real-time package overview</p>
            </div>
        </div>
        <div class="flex items-center space-x-2 text-sm text-gray-500">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Auto-refresh: 30s</span>
            <button wire:click="refreshSummary" 
                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 ml-2">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh
            </button>
        </div>
    </div>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6" role="region" aria-labelledby="manifest-summary-title">
        <!-- Package Count -->
        <div class="relative bg-white rounded-xl p-4 sm:p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200 {{ $hasError ? 'border-red-200 bg-red-50' : '' }}">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="p-2 {{ $hasError ? 'bg-red-100' : 'bg-emerald-100' }} rounded-lg">
                            @if($hasError)
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            @endif
                        </div>
                        <h4 class="text-sm font-medium {{ $hasError ? 'text-red-700' : 'text-gray-600' }}">Total Packages</h4>
                    </div>
                    <div class="text-2xl sm:text-3xl font-bold {{ $hasError ? 'text-red-800' : 'text-gray-900' }} mb-1" aria-label="Total packages: {{ $summary['package_count'] ?? 0 }}">
                        <span wire:loading.remove>{{ $summary['package_count'] ?? 0 }}</span>
                        <span wire:loading class="animate-pulse">--</span>
                    </div>
                    <div class="text-sm {{ $hasError ? 'text-red-600' : 'text-gray-500' }}">
                        {{ $hasError ? 'Estimated count' : 'Active shipments' }}
                    </div>
                </div>
                <div class="absolute top-4 right-4 w-2 h-2 {{ $hasError ? 'bg-red-400' : 'bg-emerald-400' }} rounded-full animate-pulse"></div>
            </div>
            

        </div>
        
        <!-- Conditional Metrics Based on Manifest Type -->
        @if($manifestType === 'air')
            <!-- Weight Display for Air Manifests -->
            <div class="relative bg-white rounded-xl p-4 sm:p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200 {{ $hasError ? 'border-red-200 bg-red-50' : '' }}">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="p-2 {{ $hasError ? 'bg-red-100' : 'bg-blue-100' }} rounded-lg">
                                @if($hasError)
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16l-3-3m3 3l3-3"/>
                                    </svg>
                                @endif
                            </div>
                            <h4 class="text-sm font-medium {{ $hasError ? 'text-red-700' : 'text-gray-600' }}">Total Weight</h4>
                        </div>
                        <div class="text-2xl sm:text-3xl font-bold {{ $hasError ? 'text-red-800' : 'text-gray-900' }} mb-1" 
                             aria-label="Total weight: {{ $summary['weight']['lbs'] ?? '0.0 lbs' }}">
                            <span wire:loading.remove>{{ $summary['weight']['lbs'] ?? '0.0 lbs' }}</span>
                            <span wire:loading class="animate-pulse">-- lbs</span>
                        </div>
                        <div class="text-sm {{ $hasError ? 'text-red-600' : 'text-gray-500' }}" 
                             aria-label="Weight in kilograms: {{ $summary['weight']['kg'] ?? '0.0 kg' }}">
                            <span wire:loading.remove>({{ $summary['weight']['kg'] ?? '0.0 kg' }})</span>
                            <span wire:loading class="animate-pulse">(-- kg)</span>
                        </div>
                    </div>
                    <div class="absolute top-4 right-4 w-2 h-2 {{ $hasError ? 'bg-red-400' : 'bg-blue-400' }} rounded-full animate-pulse"></div>
                </div>
                

            </div>
        @elseif($manifestType === 'sea')
            <!-- Volume Display for Sea Manifests -->
            <div class="relative bg-white rounded-xl p-4 sm:p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200 {{ $hasError ? 'border-red-200 bg-red-50' : '' }}">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="p-2 {{ $hasError ? 'bg-red-100' : 'bg-cyan-100' }} rounded-lg">
                                @if($hasError)
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                @endif
                            </div>
                            <h4 class="text-sm font-medium {{ $hasError ? 'text-red-700' : 'text-gray-600' }}">Total Volume</h4>
                        </div>
                        <div class="text-2xl sm:text-3xl font-bold {{ $hasError ? 'text-red-800' : 'text-gray-900' }} mb-1" 
                             aria-label="Total volume: {{ $summary['volume'] ?? '0.0 ft³' }}">
                            <span wire:loading.remove>{{ $summary['volume'] ?? '0.0 ft³' }}</span>
                            <span wire:loading class="animate-pulse">-- ft³</span>
                        </div>
                        <div class="text-sm {{ $hasError ? 'text-red-600' : 'text-gray-500' }}">
                            {{ $hasError ? 'Estimated capacity' : 'Cargo capacity' }}
                        </div>
                    </div>
                    <div class="absolute top-4 right-4 w-2 h-2 {{ $hasError ? 'bg-red-400' : 'bg-cyan-400' }} rounded-full animate-pulse"></div>
                </div>
                

            </div>
        @else
            <!-- Default metric for unknown manifest types -->
            <div class="relative bg-white rounded-xl p-4 sm:p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200 {{ $hasError ? 'border-red-200 bg-red-50' : ($manifestType === 'unknown' ? 'border-amber-200 bg-amber-50' : '') }}">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="p-2 {{ $hasError ? 'bg-red-100' : ($manifestType === 'unknown' ? 'bg-amber-100' : 'bg-gray-100') }} rounded-lg">
                                @if($hasError)
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                @elseif($manifestType === 'unknown')
                                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                @endif
                            </div>
                            <h4 class="text-sm font-medium {{ $hasError ? 'text-red-700' : ($manifestType === 'unknown' ? 'text-amber-700' : 'text-gray-600') }}">Manifest Type</h4>
                        </div>
                        <div class="text-2xl font-bold {{ $hasError ? 'text-red-800' : ($manifestType === 'unknown' ? 'text-amber-800' : 'text-gray-900') }} mb-1">
                            <span wire:loading.remove>{{ ucfirst($manifestType ?: 'Unknown') }}</span>
                            <span wire:loading class="animate-pulse">--</span>
                        </div>
                        <div class="text-sm {{ $hasError ? 'text-red-600' : ($manifestType === 'unknown' ? 'text-amber-600' : 'text-gray-500') }}">
                            @if($hasError)
                                Unable to determine
                            @elseif($manifestType === 'unknown')
                                Type not specified
                            @else
                                Transport method
                            @endif
                        </div>
                    </div>
                    <div class="absolute top-4 right-4 w-2 h-2 {{ $hasError ? 'bg-red-400' : ($manifestType === 'unknown' ? 'bg-amber-400' : 'bg-gray-400') }} rounded-full animate-pulse"></div>
                </div>
                

            </div>
        @endif
        
        <!-- Total Value -->
        <div class="relative bg-white rounded-xl p-4 sm:p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200 {{ $hasError ? 'border-red-200 bg-red-50' : '' }}">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="p-2 {{ $hasError ? 'bg-red-100' : 'bg-amber-100' }} rounded-lg">
                            @if($hasError)
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                        </div>
                        <h4 class="text-sm font-medium {{ $hasError ? 'text-red-700' : 'text-gray-600' }}">Total Cost</h4>
                    </div>
                    <div class="text-2xl sm:text-3xl font-bold {{ $hasError ? 'text-red-800' : 'text-gray-900' }} mb-1" 
                         aria-label="Total cost: ${{ number_format((float) str_replace([',', '$'], '', $summary['total_value'] ?? 0), 2) }}">
                        <span wire:loading.remove>${{ number_format((float) str_replace([',', '$'], '', $summary['total_value'] ?? 0), 2) }}</span>
                        <span wire:loading class="animate-pulse">$--</span>
                    </div>
                    <div class="text-sm {{ $hasError ? 'text-red-600' : 'text-gray-500' }}">
                        {{ $hasError ? 'Estimated charges' : 'Shipping charges' }}
                    </div>
                </div>
                <div class="absolute top-4 right-4 w-2 h-2 {{ $hasError ? 'bg-red-400' : 'bg-amber-400' }} rounded-full animate-pulse"></div>
            </div>
            

        </div>
    </div>
    
    <!-- Data Source and Reliability Indicator -->
    <div class="mt-6 flex items-center justify-between text-xs text-gray-500 bg-gray-50 rounded-lg px-4 py-2">
        <div class="flex items-center space-x-2">
            <div class="w-2 h-2 rounded-full {{ $hasError ? 'bg-red-400' : ($hasIncompleteData ? 'bg-amber-400' : 'bg-green-400') }}"></div>
            <span>
                Data Status: 
                @if($hasError)
                    <span class="text-red-600 font-medium">Error - Showing fallback data</span>
                @elseif($hasIncompleteData)
                    <span class="text-amber-600 font-medium">Partial - Some data missing</span>
                @else
                    <span class="text-green-600 font-medium">Complete - All data available</span>
                @endif
            </span>
        </div>
        <div class="flex items-center space-x-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Last updated: {{ now()->format('H:i:s') }}</span>
        </div>
    </div>

    <!-- Simplified Loading Indicator -->
    <div wire:loading wire:target="refreshSummary" class="absolute inset-0 bg-white bg-opacity-75 rounded-xl flex items-center justify-center z-10">
        <div class="flex items-center space-x-3">
            <div class="animate-spin rounded-full h-6 w-6 border-2 border-blue-600 border-t-transparent"></div>
            <span class="text-sm font-medium text-blue-800">Refreshing...</span>
        </div>
    </div>
</div>