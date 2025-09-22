@extends('reports.pdf.layout')

@section('content')
    {{-- Financial Summary Cards --}}
    <div class="section">
        <h2>Financial Overview</h2>
        <div class="summary-grid">
            <div class="summary-card">
                <h4>Total Revenue</h4>
                <div class="value text-blue">${{ number_format($summary['total_revenue'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-card">
                <h4>Net Collections</h4>
                <div class="value text-green">${{ number_format($summary['net_collections'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-card">
                <h4>Outstanding AR</h4>
                <div class="value text-red">${{ number_format($summary['outstanding_ar'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-card">
                <h4>Collection Rate</h4>
                <div class="value">{{ number_format($summary['collection_rate'] ?? 0, 1) }}%</div>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    @if(isset($charts) && !empty($charts))
        <div class="section">
            <h2>Financial Analytics</h2>
            
            @if(isset($charts['revenue_trends']))
                <div class="chart-container">
                    <div class="chart-title">Monthly Revenue Trends</div>
                    <img src="{{ $charts['revenue_trends'] }}" alt="Revenue Trends Chart">
                </div>
            @endif

            @if(isset($charts['service_breakdown']))
                <div class="chart-container">
                    <div class="chart-title">Revenue by Service Type</div>
                    <img src="{{ $charts['service_breakdown'] }}" alt="Service Breakdown Chart">
                </div>
            @endif
        </div>
    @endif

    {{-- Revenue Breakdown --}}
    @if(isset($revenue_breakdown) && !empty($revenue_breakdown))
        <div class="section">
            <h2>Revenue Breakdown by Service</h2>
            <table>
                <thead>
                    <tr>
                        <th>Service Type</th>
                        <th class="text-right">Count</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">Avg per Item</th>
                        <th class="text-right">% of Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($revenue_breakdown as $service)
                        <tr>
                            <td class="font-bold">{{ $service['service_name'] ?? 'N/A' }}</td>
                            <td class="text-right">{{ number_format($service['count'] ?? 0) }}</td>
                            <td class="text-right">${{ number_format($service['revenue'] ?? 0, 2) }}</td>
                            <td class="text-right">${{ number_format($service['avg_per_item'] ?? 0, 2) }}</td>
                            <td class="text-right">{{ number_format($service['percentage'] ?? 0, 1) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray font-bold">
                        <td>TOTAL</td>
                        <td class="text-right">{{ number_format(collect($revenue_breakdown)->sum('count')) }}</td>
                        <td class="text-right">${{ number_format(collect($revenue_breakdown)->sum('revenue'), 2) }}</td>
                        <td class="text-right">${{ number_format(collect($revenue_breakdown)->avg('avg_per_item'), 2) }}</td>
                        <td class="text-right">100.0%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    {{-- Monthly Financial Performance --}}
    @if(isset($monthly_data) && !empty($monthly_data))
        <div class="section">
            <h2>Monthly Financial Performance</h2>
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">Collections</th>
                        <th class="text-right">Outstanding</th>
                        <th class="text-right">Collection Rate</th>
                        <th class="text-right">Growth %</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($monthly_data as $month)
                        <tr>
                            <td class="font-bold">{{ $month['month'] ?? 'N/A' }}</td>
                            <td class="text-right">${{ number_format($month['revenue'] ?? 0, 2) }}</td>
                            <td class="text-right text-green">${{ number_format($month['collections'] ?? 0, 2) }}</td>
                            <td class="text-right text-red">${{ number_format($month['outstanding'] ?? 0, 2) }}</td>
                            <td class="text-right">{{ number_format($month['collection_rate'] ?? 0, 1) }}%</td>
                            <td class="text-right text-{{ ($month['growth_rate'] ?? 0) >= 0 ? 'green' : 'red' }}">
                                {{ ($month['growth_rate'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($month['growth_rate'] ?? 0, 1) }}%
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Top Customers by Revenue --}}
    @if(isset($top_customers) && !empty($top_customers))
        <div class="section page-break">
            <h2>Top Customers by Revenue</h2>
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th class="text-right">Packages</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">Collections</th>
                        <th class="text-right">Outstanding</th>
                        <th class="text-right">Collection Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($top_customers as $customer)
                        <tr>
                            <td class="font-bold">{{ $customer['customer_name'] ?? 'N/A' }}</td>
                            <td class="text-right">{{ number_format($customer['package_count'] ?? 0) }}</td>
                            <td class="text-right">${{ number_format($customer['revenue'] ?? 0, 2) }}</td>
                            <td class="text-right text-green">${{ number_format($customer['collections'] ?? 0, 2) }}</td>
                            <td class="text-right text-red">${{ number_format($customer['outstanding'] ?? 0, 2) }}</td>
                            <td class="text-right">{{ number_format($customer['collection_rate'] ?? 0, 1) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Accounts Receivable Aging --}}
    @if(isset($ar_aging) && !empty($ar_aging))
        <div class="section">
            <h2>Accounts Receivable Aging</h2>
            <table>
                <thead>
                    <tr>
                        <th>Age Range</th>
                        <th class="text-right">Count</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">% of Total AR</th>
                        <th class="text-right">Risk Level</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ar_aging as $aging)
                        <tr>
                            <td class="font-bold">{{ $aging['age_range'] ?? 'N/A' }}</td>
                            <td class="text-right">{{ number_format($aging['count'] ?? 0) }}</td>
                            <td class="text-right">${{ number_format($aging['amount'] ?? 0, 2) }}</td>
                            <td class="text-right">{{ number_format($aging['percentage'] ?? 0, 1) }}%</td>
                            <td class="text-right">
                                <span class="text-{{ $aging['risk_level'] === 'Low' ? 'green' : ($aging['risk_level'] === 'Medium' ? 'blue' : 'red') }}">
                                    {{ $aging['risk_level'] ?? 'N/A' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray font-bold">
                        <td>TOTAL</td>
                        <td class="text-right">{{ number_format(collect($ar_aging)->sum('count')) }}</td>
                        <td class="text-right">${{ number_format(collect($ar_aging)->sum('amount'), 2) }}</td>
                        <td class="text-right">100.0%</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    {{-- Key Performance Indicators --}}
    @if(isset($kpis) && !empty($kpis))
        <div class="section">
            <h2>Key Performance Indicators</h2>
            <table>
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th class="text-right">Current Period</th>
                        <th class="text-right">Previous Period</th>
                        <th class="text-right">Change</th>
                        <th class="text-right">Target</th>
                        <th class="text-right">Performance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kpis as $kpi)
                        <tr>
                            <td class="font-bold">{{ $kpi['metric_name'] ?? 'N/A' }}</td>
                            <td class="text-right">{{ $kpi['current_value'] ?? 'N/A' }}</td>
                            <td class="text-right">{{ $kpi['previous_value'] ?? 'N/A' }}</td>
                            <td class="text-right text-{{ ($kpi['change_percentage'] ?? 0) >= 0 ? 'green' : 'red' }}">
                                {{ ($kpi['change_percentage'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($kpi['change_percentage'] ?? 0, 1) }}%
                            </td>
                            <td class="text-right">{{ $kpi['target_value'] ?? 'N/A' }}</td>
                            <td class="text-right">
                                <span class="text-{{ ($kpi['performance_rating'] ?? 0) >= 90 ? 'green' : (($kpi['performance_rating'] ?? 0) >= 70 ? 'blue' : 'red') }}">
                                    {{ number_format($kpi['performance_rating'] ?? 0, 1) }}%
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Filter Information --}}
    @if(isset($filters) && !empty($filters))
        <div class="section">
            <h2>Report Filters</h2>
            <table>
                <tbody>
                    @if(isset($filters['date_range']))
                        <tr>
                            <td class="font-bold" style="width: 150px;">Date Range:</td>
                            <td>{{ $filters['date_range'] }}</td>
                        </tr>
                    @endif
                    @if(isset($filters['office']))
                        <tr>
                            <td class="font-bold">Office:</td>
                            <td>{{ $filters['office'] }}</td>
                        </tr>
                    @endif
                    @if(isset($filters['currency']))
                        <tr>
                            <td class="font-bold">Currency:</td>
                            <td>{{ $filters['currency'] }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif
@endsection