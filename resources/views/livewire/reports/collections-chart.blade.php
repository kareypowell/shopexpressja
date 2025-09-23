<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <!-- Chart Header -->
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">Collections Analytics</h3>
            
            <!-- Chart Type Selector -->
            <div class="flex space-x-2">
                <select wire:model="chartType" class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="collections_overview">Collections Overview</option>
                    <option value="collections_trend">Collections Trend</option>
                    <option value="outstanding_analysis">Outstanding Analysis</option>
                    <option value="payment_patterns">Payment Patterns</option>
                </select>
                
                @if($drillDownLevel !== 'overview')
                    <button wire:click="resetDrillDown" 
                            class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Overview
                    </button>
                @endif
            </div>
        </div>
        
        <!-- Drill-down Indicator -->
        @if($drillDownLevel !== 'overview')
            <div class="mt-2 text-sm text-gray-600">
                @if($drillDownLevel === 'manifest')
                    Viewing details for Manifest #{{ $selectedManifest }}
                @elseif($drillDownLevel === 'period')
                    Viewing details for {{ $selectedPeriod }}
                @endif
            </div>
        @endif
    </div>

    <!-- Chart Content -->
    <div class="p-6">
        @if(isset($error))
            <!-- Error State -->
            <div class="flex items-center justify-center h-64">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Chart Unavailable</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $error }}</p>
                    <button wire:click="$refresh" class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                        Try Again
                    </button>
                </div>
            </div>
        @elseif($chartData && isset($chartData['data']))
            <!-- Summary Cards -->
            @if(isset($chartData['summary']))
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    @if(isset($chartData['summary']['total_owed']))
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="text-sm font-medium text-blue-600">Total Owed</div>
                            <div class="text-2xl font-bold text-blue-900">
                                ${{ number_format($chartData['summary']['total_owed'], 2) }}
                            </div>
                        </div>
                    @endif
                    
                    @if(isset($chartData['summary']['total_collected']))
                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="text-sm font-medium text-green-600">Total Collected</div>
                            <div class="text-2xl font-bold text-green-900">
                                ${{ number_format($chartData['summary']['total_collected'], 2) }}
                            </div>
                        </div>
                    @endif
                    
                    @if(isset($chartData['summary']['total_outstanding']))
                        <div class="bg-red-50 rounded-lg p-4">
                            <div class="text-sm font-medium text-red-600">Outstanding</div>
                            <div class="text-2xl font-bold text-red-900">
                                ${{ number_format($chartData['summary']['total_outstanding'], 2) }}
                            </div>
                        </div>
                    @endif
                    
                    @if(isset($chartData['summary']['collection_rate']))
                        <div class="bg-purple-50 rounded-lg p-4">
                            <div class="text-sm font-medium text-purple-600">Collection Rate</div>
                            <div class="text-2xl font-bold text-purple-900">
                                {{ number_format($chartData['summary']['collection_rate'], 1) }}%
                            </div>
                            @if(isset($chartData['summary']['growth_rate']) && $chartData['summary']['growth_rate'] != 0)
                                <div class="text-xs {{ $chartData['summary']['growth_rate'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $chartData['summary']['growth_rate'] > 0 ? '+' : '' }}{{ number_format($chartData['summary']['growth_rate'], 1) }}% vs last period
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            <!-- Chart Container -->
            <div class="relative" style="height: 400px;">
                <canvas id="collectionsChart-{{ $this->id }}" 
                        wire:ignore 
                        class="w-full h-full"></canvas>
            </div>

            <!-- Chart Instructions -->
            <div class="mt-4 text-sm text-gray-500 text-center">
                @if($chartType === 'collections_overview')
                    Click on chart segments to drill down into specific data
                @elseif($chartType === 'collections_trend')
                    Click on data points to view details for that time period
                @else
                    Hover over chart elements for detailed information
                @endif
            </div>
        @elseif($chartData === null)
            <!-- No Data State -->
            <div class="flex items-center justify-center h-64">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No Data Available</h3>
                    <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or date range.</p>
                </div>
            </div>
        @else
            <!-- Loading State -->
            <div class="flex items-center justify-center h-64">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-2 text-gray-600">Loading chart data...</span>
            </div>
        @endif
    </div>
</div>

{{-- Chart initialization handled by global reports dashboard --}}