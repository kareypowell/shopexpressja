<div class="space-y-6">
    {{-- Loading State --}}
    <div wire:loading.flex class="items-center justify-center p-8">
        <div class="flex items-center space-x-2">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span class="text-gray-600">Loading shipment analytics...</span>
        </div>
    </div>

    {{-- Shipment Volume Trend Chart --}}
    <div wire:loading.remove class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Shipment Volume Trends</h3>
            <div class="flex items-center space-x-4 text-sm text-gray-600">
                <div class="flex items-center space-x-1">
                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                    <span>Package Count</span>
                </div>
                <div class="flex items-center space-x-1">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span>Total Weight (lbs)</span>
                </div>
            </div>
        </div>
        
        {{-- Volume Summary Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 rounded-lg p-4">
                <div class="text-2xl font-bold text-blue-600">{{ collect($shipmentVolumeData)->sum('volume') }}</div>
                <div class="text-sm text-blue-600">Total Packages</div>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <div class="text-2xl font-bold text-green-600">{{ number_format(collect($shipmentVolumeData)->sum('weight'), 1) }}</div>
                <div class="text-sm text-green-600">Total Weight (lbs)</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-2xl font-bold text-gray-600">
                    {{ collect($shipmentVolumeData)->count() > 0 ? number_format(collect($shipmentVolumeData)->avg('volume'), 1) : 0 }}
                </div>
                <div class="text-sm text-gray-600">Avg Daily Volume</div>
            </div>
        </div>

        <div class="relative h-80">
            <canvas id="shipmentVolumeChart"></canvas>
        </div>
    </div>

    {{-- Package Status Distribution --}}
    <div wire:loading.remove class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Package Status Distribution Over Time</h3>
            <div class="text-sm text-gray-600">
                Stacked view of package statuses
            </div>
        </div>

        <div class="relative h-80">
            <canvas id="packageStatusChart"></canvas>
        </div>
    </div>

    {{-- Processing Time Analysis --}}
    <div wire:loading.remove class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Processing Time Analysis</h3>
            <div class="text-sm text-gray-600">
                Average processing time in days
            </div>
        </div>

        {{-- Processing Time Summary --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            @php
                $avgProcessingTime = collect($processingTimeData)->avg('avg_processing_time');
                $minProcessingTime = collect($processingTimeData)->min('min_processing_time');
                $maxProcessingTime = collect($processingTimeData)->max('max_processing_time');
                $totalProcessed = collect($processingTimeData)->sum('count');
            @endphp
            <div class="bg-blue-50 rounded-lg p-4">
                <div class="text-xl font-bold text-blue-600">{{ number_format($avgProcessingTime, 1) }}</div>
                <div class="text-sm text-blue-600">Avg Processing Days</div>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <div class="text-xl font-bold text-green-600">{{ $minProcessingTime ?? 0 }}</div>
                <div class="text-sm text-green-600">Fastest Processing</div>
            </div>
            <div class="bg-red-50 rounded-lg p-4">
                <div class="text-xl font-bold text-red-600">{{ $maxProcessingTime ?? 0 }}</div>
                <div class="text-sm text-red-600">Slowest Processing</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-xl font-bold text-gray-600">{{ $totalProcessed }}</div>
                <div class="text-sm text-gray-600">Packages Processed</div>
            </div>
        </div>

        <div class="relative h-80">
            <canvas id="processingTimeChart"></canvas>
        </div>
    </div>

    {{-- Shipping Method Comparison and Delivery Performance --}}
    <div wire:loading.remove class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Shipping Method Breakdown --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Shipping Method Breakdown</h3>
                <div class="text-sm text-gray-600">
                    {{ collect($shippingMethodData)->sum('count') }} total packages
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6">
                {{-- Pie Chart --}}
                <div class="relative h-64">
                    <canvas id="shippingMethodChart"></canvas>
                </div>

                {{-- Method Summary --}}
                <div class="space-y-3">
                    @foreach($shippingMethodData as $method)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-4 h-4 rounded-full" style="background-color: {{ $method['method'] === 'Sea' ? '#3B82F6' : '#10B981' }}"></div>
                                <span class="font-medium text-gray-800">{{ $method['method'] }}</span>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-gray-800">{{ number_format($method['count']) }}</div>
                                <div class="text-sm text-gray-600">{{ $method['percentage'] }}%</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Delivery Performance Metrics --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Delivery Performance</h3>
                <div class="text-sm text-gray-600">
                    {{ number_format($deliveryMetrics['total_packages']) }} total packages
                </div>
            </div>

            {{-- Performance Gauges --}}
            <div class="space-y-6">
                {{-- On-Time Delivery Rate --}}
                <div class="text-center">
                    <div class="relative inline-flex items-center justify-center w-32 h-32">
                        <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 36 36">
                            <path class="text-gray-300" stroke="currentColor" stroke-width="3" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                            <path class="text-green-500" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="{{ $deliveryMetrics['on_time_delivery_rate'] }}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-2xl font-bold text-green-600">{{ $deliveryMetrics['on_time_delivery_rate'] }}%</span>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600 mt-2">On-Time Delivery Rate</div>
                </div>

                {{-- Performance Stats --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                        <span class="font-medium text-green-800">Delivered Packages</span>
                        <span class="font-bold text-green-800">{{ number_format($deliveryMetrics['delivered_packages']) }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                        <span class="font-medium text-red-800">Delayed Packages</span>
                        <span class="font-bold text-red-800">{{ number_format($deliveryMetrics['delayed_packages']) }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                        <span class="font-medium text-blue-800">Overall Delivery Rate</span>
                        <span class="font-bold text-blue-800">{{ $deliveryMetrics['overall_delivery_rate'] }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Processing Time by Method --}}
    <div wire:loading.remove class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Processing Time by Shipping Method</h3>
            <div class="text-sm text-gray-600">
                Comparison of processing efficiency
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Chart --}}
            <div class="relative h-64">
                <canvas id="processingTimeByMethodChart"></canvas>
            </div>

            {{-- Method Details --}}
            <div class="space-y-4">
                @foreach($processingTimeByMethod as $method)
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-medium text-gray-800">{{ $method['method'] }} Shipping</h4>
                            <span class="text-sm text-gray-600">{{ number_format($method['package_count']) }} packages</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-sm">
                            <div class="text-center">
                                <div class="font-bold text-blue-600">{{ $method['avg_processing_time'] }}</div>
                                <div class="text-gray-600">Avg Days</div>
                            </div>
                            <div class="text-center">
                                <div class="font-bold text-green-600">{{ $method['min_processing_time'] }}</div>
                                <div class="text-gray-600">Min Days</div>
                            </div>
                            <div class="text-center">
                                <div class="font-bold text-red-600">{{ $method['max_processing_time'] }}</div>
                                <div class="text-gray-600">Max Days</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Capacity Utilization --}}
    <div wire:loading.remove class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Manifest Capacity Utilization</h3>
            <div class="text-sm text-gray-600">
                {{ count($capacityData) }} manifests in period
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Manifest</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Packages</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight (lbs)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Volume (ft³)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ship Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($capacityData as $manifest)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $manifest['manifest_name'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $manifest['type'] === 'sea' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                    {{ ucfirst($manifest['type']) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($manifest['package_count']) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($manifest['total_weight'], 1) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($manifest['total_volume'], 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $manifest['shipment_date'] ? \Carbon\Carbon::parse($manifest['shipment_date'])->format('M j, Y') : 'Not set' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                No manifest data available for the selected period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Refresh Button --}}
    <div class="flex justify-end">
        <button 
            wire:click="refreshData" 
            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150"
            wire:loading.attr="disabled"
        >
            <svg wire:loading.remove class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <div wire:loading class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
            Refresh Data
        </button>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Shipment Volume Chart (Area Chart)
    const shipmentVolumeCtx = document.getElementById('shipmentVolumeChart').getContext('2d');
    const shipmentVolumeData = @json($shipmentVolumeData);
    
    const volumeLabels = shipmentVolumeData.map(item => item.date);
    const volumeCounts = shipmentVolumeData.map(item => item.volume);
    const volumeWeights = shipmentVolumeData.map(item => item.weight);
    
    new Chart(shipmentVolumeCtx, {
        type: 'line',
        data: {
            labels: volumeLabels,
            datasets: [{
                label: 'Package Count',
                data: volumeCounts,
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Total Weight (lbs)',
                data: volumeWeights,
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4,
                yAxisID: 'y1'
            }]
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
                        text: 'Package Count'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Weight (lbs)'
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

    // Package Status Distribution Chart (Stacked Bar Chart)
    const packageStatusCtx = document.getElementById('packageStatusChart').getContext('2d');
    const packageStatusData = @json($packageStatusData);
    
    const statusLabels = packageStatusData.map(item => item.date);
    const statusColors = {
        pending: '#F59E0B',
        processing: '#3B82F6',
        in_transit: '#8B5CF6',
        shipped: '#10B981',
        ready_for_pickup: '#059669',
        delivered: '#065F46',
        delayed: '#DC2626'
    };
    
    const statusDatasets = Object.keys(statusColors).map(status => ({
        label: status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()),
        data: packageStatusData.map(item => item[status] || 0),
        backgroundColor: statusColors[status],
        borderColor: statusColors[status],
        borderWidth: 1
    }));
    
    new Chart(packageStatusCtx, {
        type: 'bar',
        data: {
            labels: statusLabels,
            datasets: statusDatasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: true,
                },
                y: {
                    stacked: true,
                    title: {
                        display: true,
                        text: 'Number of Packages'
                    }
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

    // Processing Time Analysis Chart
    const processingTimeCtx = document.getElementById('processingTimeChart').getContext('2d');
    const processingTimeData = @json($processingTimeData);
    
    const processingLabels = processingTimeData.map(item => item.date);
    const avgProcessingTimes = processingTimeData.map(item => item.avg_processing_time);
    const minProcessingTimes = processingTimeData.map(item => item.min_processing_time);
    const maxProcessingTimes = processingTimeData.map(item => item.max_processing_time);
    
    new Chart(processingTimeCtx, {
        type: 'line',
        data: {
            labels: processingLabels,
            datasets: [{
                label: 'Average Processing Time',
                data: avgProcessingTimes,
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: false,
                tension: 0.4
            }, {
                label: 'Min Processing Time',
                data: minProcessingTimes,
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: false,
                tension: 0.4,
                borderDash: [5, 5]
            }, {
                label: 'Max Processing Time',
                data: maxProcessingTimes,
                borderColor: '#DC2626',
                backgroundColor: 'rgba(220, 38, 38, 0.1)',
                fill: false,
                tension: 0.4,
                borderDash: [5, 5]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Processing Time (Days)'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toFixed(1) + ' days';
                        }
                    }
                }
            }
        }
    });

    // Shipping Method Breakdown Chart (Pie Chart)
    const shippingMethodCtx = document.getElementById('shippingMethodChart').getContext('2d');
    const shippingMethodData = @json($shippingMethodData);
    
    const methodLabels = shippingMethodData.map(item => item.method);
    const methodCounts = shippingMethodData.map(item => item.count);
    const methodColors = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6'];
    
    new Chart(shippingMethodCtx, {
        type: 'pie',
        data: {
            labels: methodLabels,
            datasets: [{
                data: methodCounts,
                backgroundColor: methodColors.slice(0, methodLabels.length),
                borderColor: methodColors.slice(0, methodLabels.length),
                borderWidth: 2
            }]
        },
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

    // Processing Time by Method Chart
    const processingTimeByMethodCtx = document.getElementById('processingTimeByMethodChart').getContext('2d');
    const processingTimeByMethodData = @json($processingTimeByMethod);
    
    const methodLabels2 = processingTimeByMethodData.map(item => item.method);
    const avgTimes = processingTimeByMethodData.map(item => item.avg_processing_time);
    const minTimes = processingTimeByMethodData.map(item => item.min_processing_time);
    const maxTimes = processingTimeByMethodData.map(item => item.max_processing_time);
    
    new Chart(processingTimeByMethodCtx, {
        type: 'bar',
        data: {
            labels: methodLabels2,
            datasets: [{
                label: 'Average Processing Time',
                data: avgTimes,
                backgroundColor: '#3B82F6',
                borderColor: '#3B82F6',
                borderWidth: 1
            }, {
                label: 'Min Processing Time',
                data: minTimes,
                backgroundColor: '#10B981',
                borderColor: '#10B981',
                borderWidth: 1
            }, {
                label: 'Max Processing Time',
                data: maxTimes,
                backgroundColor: '#DC2626',
                borderColor: '#DC2626',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Processing Time (Days)'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toFixed(1) + ' days';
                        }
                    }
                }
            }
        }
    });
});

// Listen for Livewire events to refresh charts
document.addEventListener('livewire:load', function () {
    Livewire.on('dataRefreshed', function () {
        location.reload(); // Simple refresh for now - could be optimized to update charts directly
    });
});
</script>
@endpush