<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Breadcrumb Navigation --}}
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('home') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li class="inline-flex items-center">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <a href="{{ route('reports.index') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                        Reports & Analytics
                    </a>
                </li>
                <li class="inline-flex items-center">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="inline-flex items-center text-sm font-medium text-gray-500">
                        {{ $this->getCurrentBreadcrumbTitle() }}
                    </span>
                </li>
            </ol>
        </nav>
        
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Business Reports</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Comprehensive analytics and reporting dashboard
                    </p>
                    @if($lastUpdated)
                        <p class="mt-1 text-xs text-gray-500">
                            Last updated: {{ $lastUpdated }}
                        </p>
                    @endif
                </div>
                
                <div class="flex items-center space-x-4">
                    {{-- Auto Refresh Toggle --}}
                    <button 
                        wire:click="toggleAutoRefresh"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ $autoRefresh ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                    >
                        <svg class="w-4 h-4 mr-2 {{ $autoRefresh ? 'animate-spin' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        {{ $autoRefresh ? 'Auto-refresh ON' : 'Auto-refresh OFF' }}
                    </button>
                    
                    {{-- Refresh Button --}}
                    <button 
                        wire:click="refreshReport" 
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
                </div>
            </div>
            
            {{-- Report Type Navigation --}}
            @if(count($availableReports) > 1)
                <div class="mt-6">
                    <nav class="flex space-x-8" aria-label="Report Types">
                        @foreach($availableReports as $reportType => $config)
                            <button
                                wire:click="changeReportType('{{ $reportType }}')"
                                class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ $activeReportType === $reportType ? 'bg-' . $config['color'] . '-100 text-' . $config['color'] . '-800' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($config['icon'] === 'currency-dollar')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    @elseif($config['icon'] === 'truck')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    @elseif($config['icon'] === 'users')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                    @elseif($config['icon'] === 'chart-bar')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    @endif
                                </svg>
                                <div class="text-left">
                                    <div class="font-medium">{{ $config['name'] }}</div>
                                    <div class="text-xs opacity-75">{{ $config['description'] }}</div>
                                </div>
                            </button>
                        @endforeach
                    </nav>
                </div>
            @endif
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

        {{-- Dashboard Components --}}
        <div class="space-y-6">
            @foreach($sortedComponents as $componentName)
                @if($componentName === 'filters' && $this->shouldShowComponent('filters'))
                    {{-- Report Filters Component --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        @livewire('reports.report-filters', ['reportType' => $activeReportType], key('report-filters-' . $activeReportType))
                    </div>
                @elseif($componentName === 'summary_cards' && $this->shouldShowComponent('summary_cards'))
                    {{-- Summary Statistics Cards --}}
                    @if(!empty($summaryStats))
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            @foreach($summaryStats as $stat)
                                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 bg-{{ $stat['color'] ?? 'blue' }}-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-{{ $stat['color'] ?? 'blue' }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <h3 class="text-sm font-medium text-gray-900">{{ $stat['label'] }}</h3>
                                            <p class="text-2xl font-bold text-gray-900">{{ $stat['value'] }}</p>
                                            @if(isset($stat['change']))
                                                <p class="text-sm {{ $stat['change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                    {{ $stat['change'] >= 0 ? '+' : '' }}{{ $stat['change'] }}%
                                                    <span class="text-gray-500">vs last period</span>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @elseif($componentName === 'main_chart' && $this->shouldShowComponent('main_chart'))
                    {{-- Main Chart Component --}}
                    @if($activeReportType === 'sales_collections')
                        @livewire('reports.collections-chart', ['filters' => $activeFilters], key('collections-chart-' . md5(serialize($activeFilters))))
                    @elseif($activeReportType === 'manifest_performance')
                        @livewire('reports.manifest-performance-chart', ['filters' => $activeFilters], key('manifest-chart-' . md5(serialize($activeFilters))))
                    @elseif($activeReportType === 'financial_summary')
                        @livewire('reports.financial-analytics-chart', ['filters' => $activeFilters], key('financial-chart-' . md5(serialize($activeFilters))))
                    @else
                        {{-- Fallback Generic Chart --}}
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ $currentReport['name'] ?? 'Report' }} Visualization
                                </h3>
                                <button 
                                    wire:click="loadChartData"
                                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Load Chart
                                </button>
                            </div>
                            
                            <div class="h-96" id="report-chart-container">
                                @if($chartsLoaded && !empty($chartData))
                                    {{-- Chart will be rendered here via JavaScript --}}
                                    <canvas id="report-main-chart" class="w-full h-full"></canvas>
                                @else
                                    <div class="flex items-center justify-center h-full bg-gray-50 rounded-lg">
                                        <div class="text-center">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                            </svg>
                                            <h3 class="mt-2 text-sm font-medium text-gray-900">No chart data</h3>
                                            <p class="mt-1 text-sm text-gray-500">Click "Load Chart" to visualize the data</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @elseif($componentName === 'data_table' && $this->shouldShowComponent('data_table'))
                    {{-- Data Table Component --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        @livewire('reports.report-data-table', [
                            'reportType' => $activeReportType,
                            'data' => $tableData,
                            'filters' => $activeFilters
                        ], key('report-data-table-' . $activeReportType))
                    </div>
                @elseif($componentName === 'export_controls' && $this->shouldShowComponent('export_controls'))
                    {{-- Export Controls Component --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        @livewire('reports.report-exporter', [
                            'reportType' => $activeReportType,
                            'reportData' => $reportData,
                            'filters' => $activeFilters
                        ], key('report-exporter-' . $activeReportType))
                    </div>
                @endif
            @endforeach
        </div>

        {{-- Loading Overlay --}}
        <div wire:loading.flex wire:target="refreshReport,changeReportType,loadReportData" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 items-center justify-center">
            <div class="bg-white rounded-lg p-6 shadow-lg">
                <div class="flex items-center">
                    <svg class="animate-spin h-5 w-5 text-blue-600 mr-3" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-gray-900">Loading report data...</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Package Detail Modal --}}
    <div x-data="{ show: false }" 
         @show-manifest-details.window="show = true"
         @close-modal.window="show = false">
        @livewire('reports.manifest-package-detail-modal')
    </div>
</div>

{{-- Chart.js Integration --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    let reportChart = null;
    let autoRefreshInterval = null;
    
    // Listen for chart data loaded event
    Livewire.on('chartDataLoaded', function(chartData) {
        renderChart(chartData);
    });
    
    // Listen for auto-refresh events
    window.addEventListener('startAutoRefresh', function(event) {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
        
        autoRefreshInterval = setInterval(function() {
            @this.call('refreshReport');
        }, event.detail.interval);
    });
    
    window.addEventListener('stopAutoRefresh', function() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    });
    
    function renderChart(chartData) {
        const canvas = document.getElementById('report-main-chart');
        if (!canvas || !chartData) return;
        
        const ctx = canvas.getContext('2d');
        
        // Destroy existing chart
        if (reportChart) {
            reportChart.destroy();
        }
        
        // Create new chart based on type
        const config = getChartConfig(chartData);
        reportChart = new Chart(ctx, config);
    }
    
    function getChartConfig(chartData) {
        const baseConfig = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: chartData.xAxisLabel || 'Date'
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: chartData.yAxisLabel || 'Value'
                    }
                }
            }
        };
        
        switch (chartData.type) {
            case 'collections':
                return {
                    type: 'line',
                    data: chartData.data,
                    options: {
                        ...baseConfig,
                        interaction: {
                            mode: 'nearest',
                            axis: 'x',
                            intersect: false
                        }
                    }
                };
            case 'manifest_performance':
                return {
                    type: 'bar',
                    data: chartData.data,
                    options: baseConfig
                };
            case 'customer_analytics':
                return {
                    type: 'doughnut',
                    data: chartData.data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                            }
                        }
                    }
                };
            case 'financial_summary':
                return {
                    type: 'bar',
                    data: chartData.data,
                    options: {
                        ...baseConfig,
                        scales: {
                            ...baseConfig.scales,
                            y: {
                                ...baseConfig.scales.y,
                                beginAtZero: true
                            }
                        }
                    }
                };
            default:
                return {
                    type: 'line',
                    data: chartData.data,
                    options: baseConfig
                };
        }
    }
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
        if (reportChart) {
            reportChart.destroy();
        }
    });
});
</script>