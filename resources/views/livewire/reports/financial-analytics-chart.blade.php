<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <!-- Chart Header -->
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">Financial Analytics</h3>
            
            <!-- Chart Controls -->
            <div class="flex items-center space-x-3">
                <!-- Chart Type Selector -->
                <select wire:model="chartType" class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="revenue_breakdown">Revenue Breakdown</option>
                    <option value="customer_patterns">Customer Patterns</option>
                    <option value="outstanding_aging">Outstanding Aging</option>
                    <option value="revenue_trends">Revenue Trends</option>
                    <option value="service_performance">Service Performance</option>
                </select>
                
                <!-- Customer Segment Filter -->
                @if(in_array($chartType, ['customer_patterns', 'outstanding_aging']))
                    <select wire:model="customerSegment" class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">All Customers</option>
                        <option value="high_value">High Value</option>
                        <option value="regular">Regular</option>
                        <option value="new">New Customers</option>
                    </select>
                @endif
                
                <!-- Reset Button -->
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
                @if($drillDownLevel === 'service')
                    Viewing details for {{ $selectedService }} service
                @elseif($drillDownLevel === 'customer')
                    Viewing details for Customer #{{ $selectedCustomer }}
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
                    @if(isset($chartData['summary']['total_revenue']))
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="text-sm font-medium text-blue-600">Total Revenue</div>
                            <div class="text-2xl font-bold text-blue-900">
                                ${{ number_format($chartData['summary']['total_revenue'], 2) }}
                            </div>
                            @if(isset($chartData['summary']['revenue_growth']) && $chartData['summary']['revenue_growth'] != 0)
                                <div class="text-xs {{ $chartData['summary']['revenue_growth'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $chartData['summary']['revenue_growth'] > 0 ? '+' : '' }}{{ number_format($chartData['summary']['revenue_growth'], 1) }}% vs last period
                                </div>
                            @endif
                        </div>
                    @endif
                    
                    @if(isset($chartData['summary']['total_collections']))
                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="text-sm font-medium text-green-600">Collections</div>
                            <div class="text-2xl font-bold text-green-900">
                                ${{ number_format($chartData['summary']['total_collections'], 2) }}
                            </div>
                        </div>
                    @endif
                    
                    @if(isset($chartData['summary']['outstanding_balance']))
                        <div class="bg-red-50 rounded-lg p-4">
                            <div class="text-sm font-medium text-red-600">Outstanding</div>
                            <div class="text-2xl font-bold text-red-900">
                                ${{ number_format($chartData['summary']['outstanding_balance'], 2) }}
                            </div>
                        </div>
                    @endif
                    
                    @if(isset($chartData['summary']['profit_margin']))
                        <div class="bg-purple-50 rounded-lg p-4">
                            <div class="text-sm font-medium text-purple-600">Profit Margin</div>
                            <div class="text-2xl font-bold text-purple-900">
                                {{ number_format($chartData['summary']['profit_margin'], 1) }}%
                            </div>
                        </div>
                    @endif
                    
                    @if(isset($chartData['summary']['avg_payment_days']))
                        <div class="bg-yellow-50 rounded-lg p-4">
                            <div class="text-sm font-medium text-yellow-600">Avg Payment Days</div>
                            <div class="text-2xl font-bold text-yellow-900">
                                {{ number_format($chartData['summary']['avg_payment_days'], 1) }}
                            </div>
                        </div>
                    @endif
                    
                    @if(isset($chartData['summary']['customer_count']))
                        <div class="bg-indigo-50 rounded-lg p-4">
                            <div class="text-sm font-medium text-indigo-600">Active Customers</div>
                            <div class="text-2xl font-bold text-indigo-900">
                                {{ number_format($chartData['summary']['customer_count']) }}
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Chart Container -->
            <div class="relative" style="height: 400px;">
                <canvas id="financialAnalyticsChart-{{ $this->id }}" 
                        wire:ignore 
                        class="w-full h-full"></canvas>
            </div>

            <!-- Chart Instructions -->
            <div class="mt-4 text-sm text-gray-500 text-center">
                @if($chartType === 'revenue_breakdown')
                    Click on chart segments to drill down into specific service types
                @elseif($chartType === 'customer_patterns')
                    Click on data points to view individual customer details
                @elseif($chartType === 'outstanding_aging')
                    Outstanding balances grouped by aging periods
                @else
                    Hover over chart elements for detailed information
                @endif
            </div>

            <!-- Financial Insights -->
            @if(isset($chartData['insights']) && count($chartData['insights']) > 0)
                <div class="mt-6 bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Financial Insights</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        @foreach($chartData['insights'] as $insight)
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                {{ $insight }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Risk Indicators -->
            @if(isset($chartData['risk_indicators']) && count($chartData['risk_indicators']) > 0)
                <div class="mt-4 bg-red-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-red-900 mb-2">Risk Indicators</h4>
                    <ul class="text-sm text-red-700 space-y-1">
                        @foreach($chartData['risk_indicators'] as $risk)
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-red-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                {{ $risk }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @else
            <!-- Loading State -->
            <div class="flex items-center justify-center h-64">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-2 text-gray-600">Loading financial data...</span>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let chart = null;
    const chartId = 'financialAnalyticsChart-{{ $this->id }}';
    
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
                                const service = this.data.labels[index];
                                @this.call('handleDrillDown', {service_type: service});
                            } else if (chartData.type === 'scatter') {
                                const dataIndex = elements[0].index;
                                const customerId = this.data.datasets[0].data[dataIndex].customer_id;
                                @this.call('handleDrillDown', {customer_id: customerId});
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
        if (component.fingerprint.name === 'reports.financial-analytics-chart') {
            setTimeout(initChart, 100);
        }
    });
});
</script>
@endpush