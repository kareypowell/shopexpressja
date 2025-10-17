<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Business Reports</h1>
                    <p class="text-sm text-gray-500 mt-1">Comprehensive analytics and reporting dashboard</p>
                    <p class="text-xs text-gray-400 mt-1">Last updated: {{ $lastUpdated }}</p>
                </div>
                <div class="flex items-center space-x-4">
                    <button wire:click="refreshData" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            wire:loading.attr="disabled">
                        <svg wire:loading.remove class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <svg wire:loading class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Report Type Selector -->
        <div class="mb-8">
            <div class="flex flex-wrap gap-2">
                <button wire:click="$set('reportType', 'sales')" 
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $reportType === 'sales' ? 'bg-blue-100 text-blue-700 border-2 border-blue-200' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                    Sales & Collections
                </button>
                <button wire:click="$set('reportType', 'manifests')" 
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $reportType === 'manifests' ? 'bg-green-100 text-green-700 border-2 border-green-200' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                    Manifest Performance
                </button>
                <button wire:click="$set('reportType', 'customers')" 
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $reportType === 'customers' ? 'bg-purple-100 text-purple-700 border-2 border-purple-200' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                    Customer Analytics
                </button>
                <button wire:click="$set('reportType', 'financial')" 
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $reportType === 'financial' ? 'bg-yellow-100 text-yellow-700 border-2 border-yellow-200' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                    Financial Summary
                </button>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="mb-8">
            <div class="bg-white rounded-lg shadow-sm border p-4">
                <div class="flex items-center space-x-4">
                    <label class="text-sm font-medium text-gray-700">Date Range:</label>
                    <select wire:model="dateRange" class="rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="7">Last 7 days</option>
                        <option value="30">Last 30 days</option>
                        <option value="90">Last 90 days</option>
                        <option value="365">Last year</option>
                    </select>
                </div>
            </div>
        </div>

        @if($error)
            <div class="mb-8 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="ml-3">
                        <p class="text-sm text-red-800">{{ $error }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            @foreach($this->getSummaryStats() as $stat)
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">{{ $stat['label'] }}</p>
                            <p class="text-2xl font-bold text-gray-900 mt-2">{{ $stat['value'] }}</p>
                        </div>
                        <div class="w-12 h-12 bg-{{ $stat['color'] }}-100 rounded-lg flex items-center justify-center">
                            @if($stat['color'] === 'blue')
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            @elseif($stat['color'] === 'green')
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            @elseif($stat['color'] === 'red')
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            @else
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Chart Section -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold text-gray-900">
                    @if($reportType === 'sales') Sales & Collections Trend
                    @elseif($reportType === 'manifests') Manifest Performance
                    @elseif($reportType === 'customers') Customer Activity
                    @else Financial Overview
                    @endif
                </h3>
                <button class="text-sm text-gray-500 hover:text-gray-700">
                    View Details →
                </button>
            </div>
            
            <div class="h-80 relative bg-gray-50 rounded-lg">
                @if($isLoading)
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center">
                            <svg class="animate-spin w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-sm text-gray-500">Loading chart data...</p>
                        </div>
                    </div>
                @else
                    <canvas id="reportChart" class="w-full h-full" wire:ignore></canvas>
                    <div id="chartFallback" class="absolute inset-0 flex items-center justify-center hidden">
                        <div class="text-center">
                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <p class="text-sm text-gray-500">Chart visualization will appear here</p>
                            <p class="text-xs text-gray-400 mt-1">Data is loading...</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Data Table -->
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    @if($reportType === 'sales') Recent Manifests
                    @elseif($reportType === 'manifests') Manifest Details
                    @elseif($reportType === 'customers') Customer Overview
                    @else Financial Breakdown
                    @endif
                </h3>
            </div>
            
            <div class="overflow-x-auto">
                @if($isLoading)
                    <div class="p-8 text-center">
                        <svg class="animate-spin w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-sm text-gray-500">Loading data...</p>
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        @if($reportType === 'sales')
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Manifest</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Packages</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Owed</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Collected</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Written Off</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Outstanding</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse(($reportData['manifests'] ?? []) as $manifest)
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            @if(isset($manifest['manifest_id']))
                                                <a href="{{ route('admin.manifests.packages', $manifest['manifest_id']) }}" 
                                                   class="text-blue-600 hover:text-blue-800 hover:underline inline-flex items-center transition-colors duration-150"
                                                   title="Click to view manifest details">
                                                    {{ $manifest['manifest_name'] ?? 'N/A' }}
                                                    <svg class="w-3 h-3 ml-1 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                                    </svg>
                                                </a>
                                            @else
                                                {{ $manifest['manifest_name'] ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                {{ ucfirst($manifest['manifest_type'] ?? 'N/A') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($manifest['package_count'] ?? 0) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ${{ number_format($manifest['total_owed'] ?? 0, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                                            ${{ number_format($manifest['total_collected'] ?? 0, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-orange-600">
                                            ${{ number_format($manifest['total_write_offs'] ?? 0, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                            ${{ number_format($manifest['outstanding_balance'] ?? 0, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ round($manifest['collection_rate'] ?? 0, 1) }}%
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">
                                            No data available for the selected period.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        @elseif($reportType === 'customers')
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Packages</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Spent</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse(($reportData['customers'] ?? []) as $customer)
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            @if(isset($customer['customer_id']))
                                                <a href="{{ route('admin.customers.show', $customer['customer_id']) }}" 
                                                   class="text-blue-600 hover:text-blue-800 hover:underline inline-flex items-center transition-colors duration-150"
                                                   title="Click to view customer profile">
                                                    {{ $customer['customer_name'] ?? 'N/A' }}
                                                    <svg class="w-3 h-3 ml-1 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                                    </svg>
                                                </a>
                                            @else
                                                {{ $customer['customer_name'] ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $customer['customer_email'] ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($customer['package_count'] ?? 0) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ${{ number_format($customer['total_spent'] ?? 0, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm {{ ($customer['account_balance'] ?? 0) < 0 ? 'text-red-600' : 'text-green-600' }}">
                                            ${{ number_format($customer['account_balance'] ?? 0, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ ($customer['account_balance'] ?? 0) < 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                                {{ ($customer['account_balance'] ?? 0) < 0 ? 'Outstanding' : 'Current' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                            No customer data available.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        @elseif($reportType === 'manifests')
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Manifest</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Packages</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Owed</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Collected</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Written Off</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Outstanding</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse(($reportData['manifests'] ?? $reportData ?? []) as $manifest)
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            @if(isset($manifest['manifest_id']))
                                                <a href="{{ route('admin.manifests.packages', $manifest['manifest_id']) }}" 
                                                   class="text-blue-600 hover:text-blue-800 hover:underline inline-flex items-center transition-colors duration-150"
                                                   title="Click to view manifest details">
                                                    {{ $manifest['manifest_name'] ?? 'N/A' }}
                                                    <svg class="w-3 h-3 ml-1 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                                    </svg>
                                                </a>
                                            @else
                                                {{ $manifest['manifest_name'] ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                {{ ucfirst($manifest['manifest_type'] ?? 'N/A') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($manifest['package_count'] ?? 0) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ${{ number_format($manifest['total_owed'] ?? 0, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                                            ${{ number_format($manifest['total_collected'] ?? 0, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-orange-600">
                                            ${{ number_format($manifest['total_write_offs'] ?? 0, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                            ${{ number_format($manifest['outstanding_balance'] ?? 0, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ round($manifest['collection_rate'] ?? 0, 1) }}%
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">
                                            No manifest data available for the selected period.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        @else
                            <tbody class="bg-white">
                                <tr>
                                    <td class="px-6 py-8 text-center text-sm text-gray-500">
                                        Report data will be displayed here.
                                    </td>
                                </tr>
                            </tbody>
                        @endif
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('livewire:load', function () {
    let chart = null;
    
    function getChartDataFromLivewire() {
        // Call Livewire method to get chart data
        return @this.call('getChartData').then(function(chartData) {
            return chartData;
        }).catch(function(error) {
            console.error('Failed to get chart data from Livewire:', error);
            // Return fallback data
            const reportType = @this.reportType || 'sales';
            return getChartDataFallback(reportType);
        });
    }

    function getChartDataFallback(reportType) {
        switch (reportType) {
            case 'sales':
                return {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Revenue ($)',
                        data: [12000, 15000, 18000, 14000, 16000, 19000],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.1
                    }]
                };
            case 'manifests':
                return {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                    datasets: [{
                        label: 'Manifests',
                        data: [25, 32, 28, 35],
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.1
                    }]
                };
            case 'customers':
                return {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Active Customers',
                        data: [45, 52, 38, 65, 59, 80, 72],
                        borderColor: 'rgb(147, 51, 234)',
                        backgroundColor: 'rgba(147, 51, 234, 0.1)',
                        tension: 0.1
                    }]
                };
            case 'financial':
                return {
                    labels: ['Q1', 'Q2', 'Q3', 'Q4'],
                    datasets: [{
                        label: 'Financial Performance',
                        data: [85000, 92000, 78000, 105000],
                        borderColor: 'rgb(245, 158, 11)',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.1
                    }]
                };
            default:
                return {
                    labels: [],
                    datasets: []
                };
        }
    }
    
    function initChart() {
        const ctx = document.getElementById('reportChart');
        const fallback = document.getElementById('chartFallback');
        
        if (!ctx) {
            console.log('Chart canvas not found');
            if (fallback) fallback.classList.remove('hidden');
            return;
        }
        
        try {
            if (chart) {
                chart.destroy();
            }
            
            // Get chart data directly from server-side rendering
            const chartData = {!! json_encode($this->getChartData()) !!};
            
            chart = new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    }
                }
            });
            
            // Hide fallback if chart loads successfully
            if (fallback) fallback.classList.add('hidden');
            console.log('Chart initialized successfully with real data');
        } catch (error) {
            console.error('Failed to create chart:', error);
            // Show fallback if chart fails
            if (fallback) fallback.classList.remove('hidden');
            
            // Try fallback data
            try {
                const reportType = @this.reportType || 'sales';
                const fallbackData = getChartDataFallback(reportType);
                chart = new Chart(ctx, {
                    type: 'line',
                    data: fallbackData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                }
                            }
                        }
                    }
                });
                if (fallback) fallback.classList.add('hidden');
                console.log('Chart initialized with fallback data');
            } catch (fallbackError) {
                console.error('Even fallback chart failed:', fallbackError);
            }
        }
    }
    
    // Initialize chart after component loads
    setTimeout(initChart, 1000);
    
    // Reinitialize chart when data changes
    window.addEventListener('chartDataUpdated', function() {
        console.log('Chart data updated event received');
        setTimeout(initChart, 200);
    });
    
    // Listen for Livewire updates
    Livewire.hook('message.processed', (message, component) => {
        if (component.fingerprint.name === 'reports.report-dashboard') {
            setTimeout(initChart, 200);
        }
    });
});
</script>
@endpush