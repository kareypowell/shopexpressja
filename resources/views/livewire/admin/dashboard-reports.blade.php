<div>
    <!-- Header Section -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-medium text-gray-900">Business Analytics</h3>
            <p class="text-sm text-gray-600">Key performance metrics and insights</p>
            @if($lastUpdated)
                <p class="text-xs text-gray-500 mt-1">Last updated: {{ $lastUpdated }}</p>
            @endif
        </div>
        <div class="flex items-center space-x-3">
            <button 
                wire:click="refreshData" 
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                wire:loading.attr="disabled"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.class="animate-spin">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span wire:loading.remove>Refresh</span>
                <span wire:loading>Refreshing...</span>
            </button>
            <a href="{{ route('reports.index') }}" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                View All Reports
                <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>

    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Total Revenue -->
        <x-dashboard-report-widget
            title="Total Revenue (This Month)"
            :value="'$' . number_format($totalRevenue, 2)"
            :change="($revenueChange > 0 ? '+' : '') . $revenueChange . '%'"
            :changeType="$revenueChange > 0 ? 'positive' : ($revenueChange < 0 ? 'negative' : 'neutral')"
            description="Compared to last month"
            :url="route('reports.sales')"
            :loading="$loading"
            icon='<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>'
        />

        <!-- Outstanding Balance -->
        <x-dashboard-report-widget
            title="Outstanding Balance"
            :value="'$' . number_format($outstandingBalance, 2)"
            :change="($outstandingChange > 0 ? '+' : '') . $outstandingChange . '%'"
            :changeType="$outstandingChange < 0 ? 'positive' : ($outstandingChange > 0 ? 'negative' : 'neutral')"
            description="Pending collections"
            :url="route('reports.sales')"
            :loading="$loading"
            icon='<svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>'
        />

        <!-- Collection Rate -->
        <x-dashboard-report-widget
            title="Collection Rate"
            :value="number_format($collectionRate, 1) . '%'"
            :change="($collectionRateChange > 0 ? '+' : '') . $collectionRateChange . '%'"
            :changeType="$collectionRateChange > 0 ? 'positive' : ($collectionRateChange < 0 ? 'negative' : 'neutral')"
            description="Payment efficiency"
            :url="route('reports.sales')"
            :loading="$loading"
            icon='<svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>'
        />

        <!-- Active Manifests -->
        <x-dashboard-report-widget
            title="Active Manifests"
            :value="number_format($activeManifests)"
            :change="($manifestChange > 0 ? '+' : '') . $manifestChange . '%'"
            :changeType="$manifestChange > 0 ? 'positive' : ($manifestChange < 0 ? 'negative' : 'neutral')"
            description="This month"
            :url="route('reports.manifests')"
            :loading="$loading"
            icon='<svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>'
        />

        <!-- Average Processing Time -->
        <x-dashboard-report-widget
            title="Avg Processing Time"
            :value="number_format($processingTime, 1) . ' days'"
            :change="($processingTimeChange < 0 ? '' : '+') . $processingTimeChange . '%'"
            :changeType="$processingTimeChange < 0 ? 'positive' : ($processingTimeChange > 0 ? 'negative' : 'neutral')"
            description="Manifest completion"
            :url="route('reports.manifests')"
            :loading="$loading"
            icon='<svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>'
        />

        <!-- Active Customers -->
        <x-dashboard-report-widget
            title="Active Customers"
            :value="number_format($customerCount)"
            :change="($customerChange > 0 ? '+' : '') . $customerChange . '%'"
            :changeType="$customerChange > 0 ? 'positive' : ($customerChange < 0 ? 'negative' : 'neutral')"
            description="This month"
            :url="route('reports.customers')"
            :loading="$loading"
            icon='<svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>'
        />
    </div>

    <!-- Mini Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <x-dashboard-mini-chart
            title="Revenue Trend (Last 7 Days)"
            chartId="revenueTrendChart"
            :chartData="[
                'labels' => ['6 days ago', '5 days ago', '4 days ago', '3 days ago', '2 days ago', 'Yesterday', 'Today'],
                'datasets' => [[
                    'label' => 'Revenue',
                    'data' => [1200, 1500, 1800, 1300, 2100, 1900, 2300],
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true
                ]]
            ]"
            :url="route('reports.sales')"
        />
        
        <x-dashboard-mini-chart
            title="Collection Rate Trend"
            chartId="collectionRateChart"
            chartType="bar"
            :chartData="[
                'labels' => ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                'datasets' => [[
                    'label' => 'Collection Rate %',
                    'data' => [85, 92, 88, 95],
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 1
                ]]
            ]"
            :url="route('reports.sales')"
        />
    </div>

    <!-- Quick Actions -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h4 class="text-sm font-medium text-gray-900 mb-4">Quick Report Access</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('reports.sales') }}" class="flex items-center p-3 bg-white rounded-md shadow-sm hover:shadow-md transition-shadow">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">Sales Report</p>
                    <p class="text-xs text-gray-500">Revenue & collections</p>
                </div>
            </a>

            <a href="{{ route('reports.manifests') }}" class="flex items-center p-3 bg-white rounded-md shadow-sm hover:shadow-md transition-shadow">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">Manifest Report</p>
                    <p class="text-xs text-gray-500">Operations & performance</p>
                </div>
            </a>

            <a href="{{ route('reports.customers') }}" class="flex items-center p-3 bg-white rounded-md shadow-sm hover:shadow-md transition-shadow">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">Customer Report</p>
                    <p class="text-xs text-gray-500">Analytics & insights</p>
                </div>
            </a>

            <a href="{{ route('reports.exports.index') }}" class="flex items-center p-3 bg-white rounded-md shadow-sm hover:shadow-md transition-shadow">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">Export History</p>
                    <p class="text-xs text-gray-500">Download reports</p>
                </div>
            </a>
        </div>
    </div>
</div>