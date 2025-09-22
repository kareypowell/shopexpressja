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
        @if($chartData)
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
        @else
            <!-- Loading State -->
            <div class="flex items-center justify-center h-64">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-2 text-gray-600">Loading chart data...</span>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let chart = null;
    const chartId = 'collectionsChart-{{ $this->id }}';
    
    function initChart() {
        const canvas = document.getElementById(chartId);
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const chartData = @json($chartData);
        
        if (chart) {
            chart.destroy();
        }
        
        if (chartData && chartData.data) {
            chart = new Chart(ctx, {
                type: chartData.type,
                data: chartData.data,
                options: {
                    ...chartData.options,
                    onClick: function(event, elements) {
                        if (elements.length > 0) {
                            const index = elements[0].index;
                            
                            if (chartData.type === 'doughnut') {
                                const label = this.data.labels[index];
                                @this.call('handleDrillDown', {type: label.toLowerCase()});
                            } else if (chartData.type === 'line' || chartData.type === 'bar') {
                                const period = this.data.labels[index];
                                @this.call('handleDrillDown', {period: period});
                            }
                        }
                    }
                }
            });
        }
    }
    
    // Initialize chart
    initChart();
    
    // Reinitialize when component updates
    Livewire.hook('message.processed', (message, component) => {
        if (component.fingerprint.name === 'reports.collections-chart') {
            setTimeout(initChart, 100);
        }
    });
});
</script>
@endpush