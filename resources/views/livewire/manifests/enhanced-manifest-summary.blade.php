<div class="manifest-summary bg-gradient-to-br from-white to-gray-50 p-8 rounded-xl shadow-lg border border-gray-100" wire:poll.30s="refreshSummary">
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
        </div>
    </div>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6" role="region" aria-labelledby="manifest-summary-title">
        <!-- Package Count -->
        <div class="relative bg-white rounded-xl p-4 sm:p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="p-2 bg-emerald-100 rounded-lg">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <h4 class="text-sm font-medium text-gray-600">Total Packages</h4>
                    </div>
                    <div class="text-2xl sm:text-3xl font-bold text-gray-900 mb-1" aria-label="Total packages: {{ $summary['package_count'] ?? 0 }}">
                        {{ $summary['package_count'] ?? 0 }}
                    </div>
                    <div class="text-sm text-gray-500">Active shipments</div>
                </div>
                <div class="absolute top-4 right-4 w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></div>
            </div>
        </div>
        
        <!-- Conditional Metrics Based on Manifest Type -->
        @if($manifestType === 'air')
            <!-- Weight Display for Air Manifests -->
            <div class="relative bg-white rounded-xl p-4 sm:p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16l-3-3m3 3l3-3"/>
                                </svg>
                            </div>
                            <h4 class="text-sm font-medium text-gray-600">Total Weight</h4>
                        </div>
                        <div class="text-2xl sm:text-3xl font-bold text-gray-900 mb-1" 
                             aria-label="Total weight: {{ $summary['weight']['lbs'] ?? '0.0 lbs' }}">
                            {{ $summary['weight']['lbs'] ?? '0.0 lbs' }}
                        </div>
                        <div class="text-sm text-gray-500" 
                             aria-label="Weight in kilograms: {{ $summary['weight']['kg'] ?? '0.0 kg' }}">
                            ({{ $summary['weight']['kg'] ?? '0.0 kg' }})
                        </div>
                    </div>
                    <div class="absolute top-4 right-4 w-2 h-2 bg-blue-400 rounded-full animate-pulse"></div>
                </div>
            </div>
        @elseif($manifestType === 'sea')
            <!-- Volume Display for Sea Manifests -->
            <div class="relative bg-white rounded-xl p-4 sm:p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="p-2 bg-cyan-100 rounded-lg">
                                <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <h4 class="text-sm font-medium text-gray-600">Total Volume</h4>
                        </div>
                        <div class="text-2xl sm:text-3xl font-bold text-gray-900 mb-1" 
                             aria-label="Total volume: {{ $summary['volume'] ?? '0.0 ft³' }}">
                            {{ $summary['volume'] ?? '0.0 ft³' }}
                        </div>
                        <div class="text-sm text-gray-500">Cargo capacity</div>
                    </div>
                    <div class="absolute top-4 right-4 w-2 h-2 bg-cyan-400 rounded-full animate-pulse"></div>
                </div>
            </div>
        @else
            <!-- Default metric for unknown manifest types -->
            <div class="relative bg-white rounded-xl p-4 sm:p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="p-2 bg-gray-100 rounded-lg">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                            </div>
                            <h4 class="text-sm font-medium text-gray-600">Manifest Type</h4>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 mb-1">
                            {{ ucfirst($manifestType ?: 'Unknown') }}
                        </div>
                        <div class="text-sm text-gray-500">Transport method</div>
                    </div>
                    <div class="absolute top-4 right-4 w-2 h-2 bg-gray-400 rounded-full animate-pulse"></div>
                </div>
            </div>
        @endif
        
        <!-- Total Value -->
        <div class="relative bg-white rounded-xl p-4 sm:p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="p-2 bg-amber-100 rounded-lg">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h4 class="text-sm font-medium text-gray-600">Total Cost</h4>
                    </div>
                    <div class="text-2xl sm:text-3xl font-bold text-gray-900 mb-1" 
                         aria-label="Total cost: ${{ number_format($summary['total_value'] ?? 0, 2) }}">
                        ${{ number_format($summary['total_value'] ?? 0, 2) }}
                    </div>
                    <div class="text-sm text-gray-500">Shipping charges</div>
                </div>
                <div class="absolute top-4 right-4 w-2 h-2 bg-amber-400 rounded-full animate-pulse"></div>
            </div>
        </div>
    </div>
    
    <!-- Data Completeness Indicators -->
    @if($hasIncompleteData)
        <div class="mt-6 bg-amber-50 border border-amber-200 rounded-xl p-4" role="alert" aria-live="polite">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <div class="p-1 bg-amber-100 rounded-lg">
                        <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <h4 class="text-sm font-medium text-amber-800 mb-1">Incomplete Data Notice</h4>
                    <p class="text-sm text-amber-700">
                        Some packages are missing {{ $manifestType === 'air' ? 'weight' : ($manifestType === 'sea' ? 'volume' : 'measurement') }} information. 
                        Totals shown reflect available data only.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Loading indicator for real-time updates -->
    <div wire:loading.delay class="flex items-center justify-center mt-6 p-4 bg-blue-50 rounded-xl border border-blue-100">
        <div class="flex items-center space-x-3">
            <div class="animate-spin rounded-full h-6 w-6 border-2 border-blue-600 border-t-transparent"></div>
            <div class="text-sm text-blue-700 font-medium">Updating summary...</div>
        </div>
    </div>
</div>