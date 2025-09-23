<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <!-- Chart Header -->
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">Manifest Performance Analytics</h3>
            
            <!-- Chart Controls -->
            <div class="flex items-center space-x-3">
                <!-- Chart Type Selector -->
                <select wire:model="chartType" class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="processing_efficiency">Processing Efficiency</option>
                    <option value="volume_trends">Volume Trends</option>
                    <option value="weight_analysis">Weight Analysis</option>
                    <option value="type_comparison">Type Comparison</option>
                    <option value="processing_times">Processing Times</option>
                </select>
                
                <!-- Comparison Toggle -->
                @if(in_array($chartType, ['volume_trends', 'weight_analysis']))
                    <button wire:click="toggleComparison" 
                            class="px-3 py-1 text-sm {{ $comparisonMode ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }} rounded-md hover:bg-blue-200 transition-colors">
                        {{ $comparisonMode ? 'Comparison On' : 'Compare Types' }}
                    </button>
                @endif
                
                <!-- Reset Button -->
                @if($selectedManifest)
                    <button wire:click="resetDrillDown" 
                            class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Reset View
                    </button>
                @endif
            </div>
        </div>
        
        <!-- Drill-down Indicator -->
        @if($selectedManifest)
            <div class="mt-2 text-sm text-gray-600">
                Viewing details for Manifest #{{ $selectedManifest }}
            </div>
        @endif
    </div>

    <!-- Chart Content -->
    <div class="p-6">
        @if($chartData)
            <!-- Summary Cards -->
            @if(isset($chartData['summary']))
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    @if(isset($chartData['summary']['total_manifests']))
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="text-sm font-medium text-blue-600">Total Manifests</div>
                            <div class="text-2xl font-bold text-blue-900">
                                {{ number_format($chartData['summary']['total_manifests']) }}
                            </div>
                        </div>
                    @endif
                    
                    @if(isset($chartData['summary']['total_packages']))
                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="text-sm font-medium text-green-600">Total Packages</div>
                            <div class="text-2xl font-bold text-green-900">
                                {{ number_format($chartData['summary']['total_packages']) }}
                            </div>
                        </div>
                    @endif
                    
                    @if(isset($chartData['summary']['avg_processing_time']))
                        <div class="bg-purple-50 rounded-lg p-4">
                            <div class="text-sm font-medium text-purple-600">Avg Processing Time</div>
                            <div class="text-2xl font-bold text-purple-900">
                                {{ number_format($chartData['summary']['avg_processing_time'], 1) }}h
                            </div>
                        </div>
                    @endif
                    
                    @if(isset($chartData['summary']['efficiency_score']))
                        <div class="bg-yellow-50 rounded-lg p-4">
                            <div class="text-sm font-medium text-yellow-600">Efficiency Score</div>
                            <div class="text-2xl font-bold text-yellow-900">
                                {{ number_format($chartData['summary']['efficiency_score'], 1) }}%
                            </div>
                            @if(isset($chartData['summary']['efficiency_trend']) && $chartData['summary']['efficiency_trend'] != 0)
                                <div class="text-xs {{ $chartData['summary']['efficiency_trend'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $chartData['summary']['efficiency_trend'] > 0 ? '+' : '' }}{{ number_format($chartData['summary']['efficiency_trend'], 1) }}% vs last period
                                </div>
                            @endif
                        </div>
                    @endif
                    
                    @if(isset($chartData['summary']['total_weight']))
                        <div class="bg-indigo-50 rounded-lg p-4">
                            <div class="text-sm font-medium text-indigo-600">Total Weight</div>
                            <div class="text-2xl font-bold text-indigo-900">
                                {{ number_format($chartData['summary']['total_weight']) }} lbs
                            </div>
                        </div>
                    @endif
                    
                    @if(isset($chartData['summary']['total_volume']))
                        <div class="bg-pink-50 rounded-lg p-4">
                            <div class="text-sm font-medium text-pink-600">Total Volume</div>
                            <div class="text-2xl font-bold text-pink-900">
                                {{ number_format($chartData['summary']['total_volume'], 1) }} ft³
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Chart Container -->
            <div class="relative" style="height: 400px;">
                <canvas id="manifestPerformanceChart-{{ $this->id }}" 
                        wire:ignore 
                        class="w-full h-full"></canvas>
            </div>

            <!-- Chart Instructions -->
            <div class="mt-4 text-sm text-gray-500 text-center">
                @if($chartType === 'processing_efficiency')
                    Radar chart showing efficiency metrics across different performance areas
                @elseif($chartType === 'volume_trends')
                    Click on data points to view details for that time period
                @elseif($chartType === 'type_comparison')
                    Click on chart segments to filter by manifest type
                @else
                    Hover over chart elements for detailed information
                @endif
            </div>

            <!-- Performance Insights -->
            @if(isset($chartData['insights']) && count($chartData['insights']) > 0)
                <div class="mt-6 bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Performance Insights</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        @foreach($chartData['insights'] as $insight)
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-blue-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                {{ $insight }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @else
            <!-- Loading State -->
            <div class="flex items-center justify-center h-64">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-2 text-gray-600">Loading performance data...</span>
            </div>
        @endif
    </div>
</div>

{{-- Chart initialization handled by global reports dashboard --}}