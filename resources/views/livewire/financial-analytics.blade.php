<div class="space-y-6">
    {{-- Loading State --}}
    <div wire:loading.delay class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-700">Loading financial analytics...</span>
        </div>
    </div>

    {{-- Financial KPIs Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Total Revenue --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($financialKPIs['total_revenue']['current'], 2) }}</p>
                    <div class="flex items-center mt-2">
                        @if($financialKPIs['total_revenue']['growth_rate'] >= 0)
                            <svg class="w-4 h-4 text-green-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-green-600 font-medium">+{{ $financialKPIs['total_revenue']['growth_rate'] }}%</span>
                        @else
                            <svg class="w-4 h-4 text-red-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-red-600 font-medium">{{ $financialKPIs['total_revenue']['growth_rate'] }}%</span>
                        @endif
                    </div>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Average Order Value --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Average Order Value</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($financialKPIs['average_order_value']['current'], 2) }}</p>
                    <div class="flex items-center mt-2">
                        @if($financialKPIs['average_order_value']['growth_rate'] >= 0)
                            <svg class="w-4 h-4 text-green-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-green-600 font-medium">+{{ $financialKPIs['average_order_value']['growth_rate'] }}%</span>
                        @else
                            <svg class="w-4 h-4 text-red-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-red-600 font-medium">{{ $financialKPIs['average_order_value']['growth_rate'] }}%</span>
                        @endif
                    </div>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Customer Lifetime Value --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Est. Customer LTV</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($financialKPIs['customer_lifetime_value']['estimated_clv'], 2) }}</p>
                    <p class="text-xs text-gray-500 mt-2">{{ $financialKPIs['customer_lifetime_value']['avg_lifespan_months'] }} month lifespan</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        {{-- ARPU --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">ARPU</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($financialKPIs['customer_metrics']['arpu'], 2) }}</p>
                    <div class="flex items-center mt-2">
                        @if($financialKPIs['customer_metrics']['customer_growth'] >= 0)
                            <svg class="w-4 h-4 text-green-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-green-600 font-medium">+{{ $financialKPIs['customer_metrics']['customer_growth'] }}% customers</span>
                        @else
                            <svg class="w-4 h-4 text-red-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-red-600 font-medium">{{ $financialKPIs['customer_metrics']['customer_growth'] }}% customers</span>
                        @endif
                    </div>
                </div>
                <div class="p-3 bg-orange-100 rounded-full">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Revenue Trends Chart --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Revenue Trends</h3>
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500">Multiple series view</span>
            </div>
        </div>
        <div class="h-80">
            @if(empty($revenueTrends))
                <div class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No revenue data</h3>
                        <p class="mt-1 text-sm text-gray-500">No revenue trends available for the selected period.</p>
                    </div>
                </div>
            @else
                <canvas id="revenueTrendsChart" width="400" height="200"></canvas>
            @endif
        </div>
    </div>

    {{-- Revenue by Service Type and Customer Segment --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Revenue by Service Type --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Revenue by Service Type</h3>
            </div>
            <div class="h-64">
                <canvas id="revenueByServiceChart" width="400" height="200"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                @foreach($revenueByService as $service)
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center">
                            <div class="w-3 h-3 rounded-full mr-2" style="background-color: {{ $service['service_type'] === 'Sea' ? '#3B82F6' : '#10B981' }}"></div>
                            <span class="text-gray-700">{{ $service['service_type'] }}</span>
                        </div>
                        <div class="text-right">
                            <span class="font-medium text-gray-900">${{ number_format($service['total_revenue'], 2) }}</span>
                            <span class="text-gray-500 ml-2">({{ $service['percentage'] ?? 0 }}%)</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Revenue by Customer Segment --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Revenue by Customer Segment</h3>
            </div>
            <div class="h-64">
                <canvas id="revenueBySegmentChart" width="400" height="200"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                @foreach($revenueBySegment as $index => $segment)
                    @php
                        $colors = ['#8B5CF6', '#F59E0B', '#EF4444', '#6B7280'];
                        $color = $colors[$index % count($colors)];
                    @endphp
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center">
                            <div class="w-3 h-3 rounded-full mr-2" style="background-color: {{ $color }}"></div>
                            <span class="text-gray-700">{{ $segment['segment'] }}</span>
                        </div>
                        <div class="text-right">
                            <span class="font-medium text-gray-900">${{ number_format($segment['total_revenue'], 2) }}</span>
                            <span class="text-gray-500 ml-2">({{ $segment['percentage'] ?? 0 }}%)</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Profit Margin Analysis --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Profit Margin Analysis</h3>
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500">Combination chart</span>
            </div>
        </div>
        <div class="h-80">
            <canvas id="profitMarginChart" width="400" height="200"></canvas>
        </div>
    </div>

    {{-- Customer Lifetime Value Scatter Plot --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Customer Lifetime Value Distribution</h3>
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500">Scatter plot</span>
            </div>
        </div>
        <div class="h-80">
            <canvas id="customerCLVChart" width="400" height="200"></canvas>
        </div>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div class="text-center">
                <p class="text-gray-500">Average CLV</p>
                <p class="text-lg font-semibold text-gray-900">
                    ${{ number_format(collect($customerCLV)->avg('estimated_clv'), 2) }}
                </p>
            </div>
            <div class="text-center">
                <p class="text-gray-500">Highest CLV</p>
                <p class="text-lg font-semibold text-gray-900">
                    ${{ number_format(collect($customerCLV)->max('estimated_clv'), 2) }}
                </p>
            </div>
            <div class="text-center">
                <p class="text-gray-500">Total Customers</p>
                <p class="text-lg font-semibold text-gray-900">
                    {{ count($customerCLV) }}
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function initializeFinancialCharts() {
    // Wait for Chart.js to be available
    if (typeof Chart === 'undefined') {
        setTimeout(initializeFinancialCharts, 100);
        return;
    }
    
    console.log('Initializing financial charts...');
    // Revenue Trends Line Chart with Multiple Series
    const revenueTrendsCtx = document.getElementById('revenueTrendsChart');
    if (!revenueTrendsCtx) {
        console.error('Revenue trends chart canvas not found');
        return;
    }
    
    const revenueTrendsData = @json($revenueTrends);
    
    // Handle empty data
    if (!revenueTrendsData || revenueTrendsData.length === 0) {
        revenueTrendsCtx.getContext('2d').fillText('No data available', 50, 50);
        return;
    }
    
    new Chart(revenueTrendsCtx.getContext('2d'), {
        type: 'line',
        data: {
            labels: revenueTrendsData.map(item => item.period),
            datasets: [
                {
                    label: 'Total Revenue',
                    data: revenueTrendsData.map(item => item.total_revenue),
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Freight Revenue',
                    data: revenueTrendsData.map(item => item.freight_revenue),
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: false,
                    tension: 0.4
                },
                {
                    label: 'Average Order Value',
                    data: revenueTrendsData.map(item => item.avg_order_value),
                    borderColor: '#F59E0B',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    fill: false,
                    tension: 0.4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Revenue ($)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'AOV ($)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': $' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Revenue by Service Type Stacked Area Chart
    const revenueByServiceCtx = document.getElementById('revenueByServiceChart');
    if (!revenueByServiceCtx) return;
    
    const revenueByServiceData = @json($revenueByService);
    if (!revenueByServiceData || revenueByServiceData.length === 0) {
        revenueByServiceCtx.getContext('2d').fillText('No data available', 50, 50);
        return;
    }
    
    new Chart(revenueByServiceCtx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: revenueByServiceData.map(item => item.service_type),
            datasets: [{
                data: revenueByServiceData.map(item => item.total_revenue),
                backgroundColor: [
                    '#3B82F6',
                    '#10B981',
                    '#F59E0B',
                    '#EF4444'
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return context.label + ': $' + value.toLocaleString() + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

    // Revenue by Customer Segment Chart
    const revenueBySegmentCtx = document.getElementById('revenueBySegmentChart');
    if (!revenueBySegmentCtx) return;
    
    const revenueBySegmentData = @json($revenueBySegment);
    if (!revenueBySegmentData || revenueBySegmentData.length === 0) {
        revenueBySegmentCtx.getContext('2d').fillText('No data available', 50, 50);
        return;
    }
    
    new Chart(revenueBySegmentCtx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: revenueBySegmentData.map(item => item.segment),
            datasets: [{
                label: 'Revenue',
                data: revenueBySegmentData.map(item => item.total_revenue),
                backgroundColor: [
                    '#8B5CF6',
                    '#F59E0B',
                    '#EF4444',
                    '#6B7280'
                ],
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Revenue: $' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Revenue ($)'
                    }
                }
            }
        }
    });

    // Profit Margin Analysis Combination Chart
    const profitMarginCtx = document.getElementById('profitMarginChart');
    if (!profitMarginCtx) return;
    
    const profitMarginData = @json($profitMargins);
    if (!profitMarginData || profitMarginData.length === 0) {
        profitMarginCtx.getContext('2d').fillText('No data available', 50, 50);
        return;
    }
    
    new Chart(profitMarginCtx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: profitMarginData.map(item => item.date),
            datasets: [
                {
                    label: 'Gross Revenue',
                    data: profitMarginData.map(item => item.gross_revenue),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    order: 2
                },
                {
                    label: 'Net Profit',
                    data: profitMarginData.map(item => item.net_profit),
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    order: 2
                },
                {
                    label: 'Profit Margin %',
                    data: profitMarginData.map(item => item.profit_margin),
                    type: 'line',
                    borderColor: '#F59E0B',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    yAxisID: 'y1',
                    order: 1,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Revenue/Profit ($)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Profit Margin (%)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.dataset.label === 'Profit Margin %') {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(1) + '%';
                            }
                            return context.dataset.label + ': $' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Customer Lifetime Value Scatter Plot
    const customerCLVCtx = document.getElementById('customerCLVChart');
    if (!customerCLVCtx) return;
    
    const customerCLVData = @json($customerCLV);
    if (!customerCLVData || customerCLVData.length === 0) {
        customerCLVCtx.getContext('2d').fillText('No data available', 50, 50);
        return;
    }
    
    new Chart(customerCLVCtx.getContext('2d'), {
        type: 'scatter',
        data: {
            datasets: [{
                label: 'Customer CLV',
                data: customerCLVData.map(customer => ({
                    x: customer.total_orders,
                    y: customer.estimated_clv
                })),
                backgroundColor: 'rgba(139, 92, 246, 0.6)',
                borderColor: '#8B5CF6',
                borderWidth: 1,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Total Orders'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Estimated CLV ($)'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const customer = customerCLVData[context.dataIndex];
                            return [
                                'Orders: ' + customer.total_orders,
                                'CLV: $' + customer.estimated_clv.toLocaleString(),
                                'Total Spent: $' + customer.total_spent.toLocaleString(),
                                'AOV: $' + customer.avg_order_value.toLocaleString()
                            ];
                        }
                    }
                }
            }
        }
    });
});

// Listen for Livewire events to refresh charts
document.addEventListener('livewire:load', function () {
}

// Initialize charts when DOM is ready
document.addEventListener('DOMContentLoaded', initializeFinancialCharts);

// Listen for Livewire events to refresh charts
document.addEventListener('livewire:load', function () {
    Livewire.on('dataRefreshed', function () {
        // Charts will be redrawn when the component re-renders
        setTimeout(() => {
            location.reload();
        }, 100);
    });
});
</script>
@endpush