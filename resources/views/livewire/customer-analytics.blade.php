<div class="space-y-4">
    {{-- Loading State --}}
    <div wire:loading.flex class="items-center justify-center p-6">
        <div class="flex items-center space-x-2">
            <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
            <span class="text-sm text-gray-600">Loading customer analytics...</span>
        </div>
    </div>

    {{-- First Row: Customer Growth Trends and Customer Status Distribution --}}
    <div wire:loading.remove class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Customer Growth Trend Chart --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-semibold text-gray-900">Customer Growth Trends</h3>
                <div class="flex items-center space-x-3 text-xs text-gray-600">
                    <div class="flex items-center space-x-1">
                        <div class="w-2.5 h-2.5 bg-blue-500 rounded-full"></div>
                        <span>New Customers</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-2.5 h-2.5 bg-green-500 rounded-full"></div>
                        <span>Total Customers</span>
                    </div>
                </div>
            </div>
            
            {{-- Growth Summary Stats --}}
            <div class="grid grid-cols-3 gap-3 mb-4">
                <div class="bg-blue-50 rounded-lg p-3">
                    <div class="text-xl font-bold text-blue-600">{{ $customerGrowthData['summary']['total_new'] }}</div>
                    <div class="text-xs text-blue-600 mt-1">New Customers</div>
                </div>
                <div class="bg-green-50 rounded-lg p-3">
                    <div class="text-xl font-bold text-green-600">{{ number_format($customerGrowthData['summary']['current_total']) }}</div>
                    <div class="text-xs text-green-600 mt-1">Total Customers</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="text-xl font-bold text-gray-600">{{ $customerGrowthData['summary']['average_daily'] }}</div>
                    <div class="text-xs text-gray-600 mt-1">Avg Daily Growth</div>
                </div>
            </div>

            <div class="relative h-80">
                <canvas id="customerGrowthChart"></canvas>
            </div>
        </div>

        {{-- Customer Status Distribution --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-semibold text-gray-900">Customer Status Distribution</h3>
                <div class="text-xs text-gray-600">
                    Total: {{ number_format($customerStatusDistribution['summary']['total']) }} customers
                </div>
            </div>

            {{-- Status Summary --}}
            <div class="space-y-3 mb-4">
                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                        <span class="text-sm font-medium text-green-800">Active</span>
                    </div>
                    <div class="text-right">
                        <div class="text-xl font-bold text-green-800">{{ number_format($customerStatusDistribution['summary']['active']) }}</div>
                        <div class="text-xs text-green-600">{{ $customerStatusDistribution['summary']['active_percentage'] }}%</div>
                    </div>
                </div>

                <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="w-4 h-4 bg-yellow-500 rounded-full"></div>
                        <span class="text-sm font-medium text-yellow-800">Inactive</span>
                    </div>
                    <div class="text-right">
                        <div class="text-xl font-bold text-yellow-800">{{ number_format($customerStatusDistribution['summary']['inactive']) }}</div>
                        <div class="text-xs text-yellow-600">
                            {{ $customerStatusDistribution['summary']['total'] > 0 ? round(($customerStatusDistribution['summary']['inactive'] / $customerStatusDistribution['summary']['total']) * 100, 1) : 0 }}%
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="w-4 h-4 bg-red-500 rounded-full"></div>
                        <span class="text-sm font-medium text-red-800">Suspended</span>
                    </div>
                    <div class="text-right">
                        <div class="text-xl font-bold text-red-800">{{ number_format($customerStatusDistribution['summary']['suspended']) }}</div>
                        <div class="text-xs text-red-600">
                            {{ $customerStatusDistribution['summary']['total'] > 0 ? round(($customerStatusDistribution['summary']['suspended'] / $customerStatusDistribution['summary']['total']) * 100, 1) : 0 }}%
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative h-64">
                <canvas id="customerStatusChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Second Row: Geographic Distribution and Customer Activity Levels --}}
    <div wire:loading.remove class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Geographic Distribution --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-semibold text-gray-900">Geographic Distribution</h3>
                <div class="text-xs text-gray-600">
                    {{ number_format($geographicDistribution['total_with_location']) }} customers with location data
                </div>
            </div>

            {{-- Country Summary --}}
            <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Country Distribution</h4>
                <div class="space-y-2">
                    @foreach($geographicDistribution['country_summary'] as $country)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="font-medium text-gray-700">{{ $country['country'] ?: 'Not specified' }}</span>
                            <span class="text-xl font-bold text-gray-600">{{ number_format($country['count']) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Parish Distribution Chart --}}
            <div>
                <h4 class="text-sm font-medium text-gray-700 mb-3">Top Parishes</h4>
                <div class="relative h-64">
                    <canvas id="geographicChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Customer Activity Levels --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-semibold text-gray-900">Customer Activity Levels</h3>
                <div class="text-xs text-gray-600">
                    {{ number_format($customerActivityLevels['summary']['total_customers']) }} total customers analyzed
                </div>
            </div>

            {{-- Activity Summary Stats --}}
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="bg-blue-50 rounded-lg p-3">
                    <div class="text-xl font-bold text-blue-600">{{ number_format($customerActivityLevels['summary']['active_customers']) }}</div>
                    <div class="text-xs text-blue-600 mt-1">Active Customers</div>
                </div>
                <div class="bg-green-50 rounded-lg p-3">
                    <div class="text-xl font-bold text-green-600">{{ $customerActivityLevels['summary']['average_packages_per_customer'] }}</div>
                    <div class="text-xs text-green-600 mt-1">Avg Packages/Customer</div>
                </div>
                <div class="bg-purple-50 rounded-lg p-3">
                    <div class="text-xl font-bold text-purple-600">${{ number_format($customerActivityLevels['summary']['total_revenue'], 2) }}</div>
                    <div class="text-xs text-purple-600 mt-1">Total Revenue</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="text-xl font-bold text-gray-600">
                        {{ $customerActivityLevels['summary']['active_customers'] > 0 ? round(($customerActivityLevels['summary']['active_customers'] / $customerActivityLevels['summary']['total_customers']) * 100, 1) : 0 }}%
                    </div>
                    <div class="text-xs text-gray-600 mt-1">Activity Rate</div>
                </div>
            </div>

            {{-- Activity Distribution Chart --}}
            <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Customer Activity Distribution</h4>
                <div class="relative h-64">
                    <canvas id="activityDistributionChart"></canvas>
                </div>
            </div>

            {{-- Revenue by Activity Chart --}}
            <div>
                <h4 class="text-sm font-medium text-gray-700 mb-3">Revenue by Activity Level</h4>
                <div class="relative h-64">
                    <canvas id="revenueByActivityChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Refresh Button --}}
    <div class="flex justify-end mt-2">
        <button 
            wire:click="refreshData" 
            class="inline-flex items-center px-3 py-1.5 bg-blue-600 border border-transparent rounded-md font-medium text-xs text-white uppercase tracking-wide hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150"
            wire:loading.attr="disabled"
        >
            <svg wire:loading.remove class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <div wire:loading class="animate-spin rounded-full h-3.5 w-3.5 border-b-2 border-white mr-1.5"></div>
            Refresh Data
        </button>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function initializeCharts() {
    // Add delay to ensure DOM is fully rendered
    setTimeout(function() {
        try {
            // Customer Growth Chart
            const customerGrowthEl = document.getElementById('customerGrowthChart');
            if (customerGrowthEl) {
                const customerGrowthCtx = customerGrowthEl.getContext('2d');
                const customerGrowthData = @json($customerGrowthData);
                
                if (customerGrowthData && customerGrowthData.datasets) {
                    new Chart(customerGrowthCtx, {
                        type: 'line',
                        data: customerGrowthData,
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
                                        text: 'New Customers'
                                    }
                                },
                                y1: {
                                    type: 'linear',
                                    display: true,
                                    position: 'right',
                                    title: {
                                        display: true,
                                        text: 'Total Customers'
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
                                    mode: 'index',
                                    intersect: false,
                                }
                            }
                        }
                    });
                }
            }

            // Customer Status Distribution Chart
            const customerStatusEl = document.getElementById('customerStatusChart');
            if (customerStatusEl) {
                const customerStatusCtx = customerStatusEl.getContext('2d');
                const customerStatusData = @json($customerStatusDistribution);
                
                if (customerStatusData && customerStatusData.datasets) {
                    new Chart(customerStatusCtx, {
                        type: 'doughnut',
                        data: customerStatusData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }

            // Geographic Distribution Chart
            const geographicEl = document.getElementById('geographicChart');
            if (geographicEl) {
                const geographicCtx = geographicEl.getContext('2d');
                const geographicData = @json($geographicDistribution['parish_distribution']);
                
                if (geographicData && geographicData.datasets) {
                    new Chart(geographicCtx, {
                        type: 'bar',
                        data: geographicData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            indexAxis: 'y',
                            plugins: {
                                legend: {
                                    display: false,
                                },
                                tooltip: {
                                    callbacks: {
                                        title: function(context) {
                                            return context[0].label;
                                        },
                                        label: function(context) {
                                            return 'Customers: ' + context.parsed.x;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Customers'
                                    }
                                }
                            }
                        }
                    });
                }
            }

            // Activity Distribution Chart
            const activityEl = document.getElementById('activityDistributionChart');
            if (activityEl) {
                const activityCtx = activityEl.getContext('2d');
                const activityData = @json($customerActivityLevels['activity_distribution']);
                
                if (activityData && activityData.datasets) {
                    new Chart(activityCtx, {
                        type: 'doughnut',
                        data: activityData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }

            // Revenue by Activity Chart
            const revenueActivityEl = document.getElementById('revenueByActivityChart');
            if (revenueActivityEl) {
                const revenueActivityCtx = revenueActivityEl.getContext('2d');
                const revenueActivityData = @json($customerActivityLevels['revenue_by_activity']);
                
                if (revenueActivityData && revenueActivityData.datasets) {
                    new Chart(revenueActivityCtx, {
                        type: 'bar',
                        data: revenueActivityData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false,
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
                                    },
                                    ticks: {
                                        callback: function(value) {
                                            return '$' + value.toLocaleString();
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
        } catch (error) {
            console.error('Error initializing charts:', error);
        }
    }, 100);
}

// Initialize charts when DOM is ready
document.addEventListener('DOMContentLoaded', initializeCharts);

// Initialize charts when Livewire is loaded
document.addEventListener('livewire:load', initializeCharts);

// Listen for Livewire events to refresh charts
document.addEventListener('livewire:load', function () {
    Livewire.on('dataRefreshed', function () {
        location.reload(); // Simple refresh for now - could be optimized to update charts directly
    });
});
</script>
@endpush