@extends('reports.pdf.layout')

@section('content')
    {{-- Summary Cards --}}
    <div class="section">
        <h2>Financial Summary</h2>
        <div class="summary-grid">
            <div class="summary-card">
                <h4>Total Revenue</h4>
                <div class="value text-blue">${{ number_format($summary['total_revenue'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-card">
                <h4>Collections</h4>
                <div class="value text-green">${{ number_format($summary['total_collected'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-card">
                <h4>Outstanding</h4>
                <div class="value text-red">${{ number_format($summary['total_outstanding'] ?? 0, 2) }}</div>
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
            <h2>Visual Analytics</h2>
            
            @if(isset($charts['collections_trend']))
                <div class="chart-container">
                    <div class="chart-title">Collections Trend Over Time</div>
                    <img src="{{ $charts['collections_trend'] }}" alt="Collections Trend Chart">
                </div>
            @endif

            @if(isset($charts['revenue_breakdown']))
                <div class="chart-container">
                    <div class="chart-title">Revenue Breakdown by Service Type</div>
                    <img src="{{ $charts['revenue_breakdown'] }}" alt="Revenue Breakdown Chart">
                </div>
            @endif
        </div>
    @endif

    {{-- Detailed Sales Data --}}
    @if(isset($sales_data) && !empty($sales_data))
        <div class="section">
            <h2>Sales Details</h2>
            <table>
                <thead>
                    <tr>
                        <th>Manifest</th>
                        <th>Date</th>
                        <th>Packages</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">Collected</th>
                        <th class="text-right">Outstanding</th>
                        <th class="text-right">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sales_data as $sale)
                        <tr>
                            <td class="font-bold">{{ $sale['manifest_number'] ?? 'N/A' }}</td>
                            <td>{{ isset($sale['date']) ? \Carbon\Carbon::parse($sale['date'])->format('M j, Y') : 'N/A' }}</td>
                            <td class="text-center">{{ $sale['package_count'] ?? 0 }}</td>
                            <td class="text-right">${{ number_format($sale['total_revenue'] ?? 0, 2) }}</td>
                            <td class="text-right text-green">${{ number_format($sale['collected'] ?? 0, 2) }}</td>
                            <td class="text-right text-red">${{ number_format($sale['outstanding'] ?? 0, 2) }}</td>
                            <td class="text-right">{{ number_format($sale['collection_rate'] ?? 0, 1) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray font-bold">
                        <td colspan="3">TOTALS</td>
                        <td class="text-right">${{ number_format(collect($sales_data)->sum('total_revenue'), 2) }}</td>
                        <td class="text-right text-green">${{ number_format(collect($sales_data)->sum('collected'), 2) }}</td>
                        <td class="text-right text-red">${{ number_format(collect($sales_data)->sum('outstanding'), 2) }}</td>
                        <td class="text-right">{{ number_format(collect($sales_data)->avg('collection_rate'), 1) }}%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    {{-- Outstanding Balances by Age --}}
    @if(isset($aging_data) && !empty($aging_data))
        <div class="section page-break">
            <h2>Outstanding Balance Aging</h2>
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th class="text-right">Current</th>
                        <th class="text-right">30 Days</th>
                        <th class="text-right">60 Days</th>
                        <th class="text-right">90+ Days</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($aging_data as $aging)
                        <tr>
                            <td>{{ $aging['customer_name'] ?? 'Unknown' }}</td>
                            <td class="text-right">${{ number_format($aging['current'] ?? 0, 2) }}</td>
                            <td class="text-right">${{ number_format($aging['days_30'] ?? 0, 2) }}</td>
                            <td class="text-right">${{ number_format($aging['days_60'] ?? 0, 2) }}</td>
                            <td class="text-right text-red">${{ number_format($aging['days_90_plus'] ?? 0, 2) }}</td>
                            <td class="text-right font-bold">${{ number_format($aging['total'] ?? 0, 2) }}</td>
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
                    @if(isset($filters['manifest_type']))
                        <tr>
                            <td class="font-bold">Manifest Type:</td>
                            <td>{{ ucfirst($filters['manifest_type']) }}</td>
                        </tr>
                    @endif
                    @if(isset($filters['office']))
                        <tr>
                            <td class="font-bold">Office:</td>
                            <td>{{ $filters['office'] }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif
@endsection