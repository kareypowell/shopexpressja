@extends('reports.pdf.layout')

@section('content')
    {{-- Summary Cards --}}
    <div class="section">
        <h2>Manifest Performance Summary</h2>
        <div class="summary-grid">
            <div class="summary-card">
                <h4>Total Manifests</h4>
                <div class="value text-blue">{{ number_format($summary['total_manifests'] ?? 0) }}</div>
            </div>
            <div class="summary-card">
                <h4>Total Packages</h4>
                <div class="value text-green">{{ number_format($summary['total_packages'] ?? 0) }}</div>
            </div>
            <div class="summary-card">
                <h4>Avg Processing Time</h4>
                <div class="value">{{ number_format($summary['avg_processing_time'] ?? 0, 1) }} days</div>
            </div>
            <div class="summary-card">
                <h4>Completion Rate</h4>
                <div class="value">{{ number_format($summary['completion_rate'] ?? 0, 1) }}%</div>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    @if(isset($charts) && !empty($charts))
        <div class="section">
            <h2>Performance Analytics</h2>
            
            @if(isset($charts['processing_times']))
                <div class="chart-container">
                    <div class="chart-title">Processing Times by Manifest Type</div>
                    <img src="{{ $charts['processing_times'] }}" alt="Processing Times Chart">
                </div>
            @endif

            @if(isset($charts['volume_trends']))
                <div class="chart-container">
                    <div class="chart-title">Package Volume Trends</div>
                    <img src="{{ $charts['volume_trends'] }}" alt="Volume Trends Chart">
                </div>
            @endif
        </div>
    @endif

    {{-- Manifest Performance Details --}}
    @if(isset($manifest_data) && !empty($manifest_data))
        <div class="section">
            <h2>Manifest Details</h2>
            <table>
                <thead>
                    <tr>
                        <th>Manifest #</th>
                        <th>Type</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th class="text-right">Packages</th>
                        <th class="text-right">Weight (lbs)</th>
                        <th class="text-right">Volume (ft³)</th>
                        <th class="text-right">Processing Days</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($manifest_data as $manifest)
                        <tr>
                            <td class="font-bold">{{ $manifest['manifest_number'] ?? 'N/A' }}</td>
                            <td>
                                <span class="text-{{ ($manifest['type'] ?? '') === 'air' ? 'blue' : 'green' }}">
                                    {{ strtoupper($manifest['type'] ?? 'N/A') }}
                                </span>
                            </td>
                            <td>{{ isset($manifest['created_at']) ? \Carbon\Carbon::parse($manifest['created_at'])->format('M j, Y') : 'N/A' }}</td>
                            <td>
                                <span class="text-{{ ($manifest['status'] ?? '') === 'completed' ? 'green' : 'blue' }}">
                                    {{ ucfirst($manifest['status'] ?? 'N/A') }}
                                </span>
                            </td>
                            <td class="text-right">{{ number_format($manifest['package_count'] ?? 0) }}</td>
                            <td class="text-right">{{ number_format($manifest['total_weight'] ?? 0, 1) }}</td>
                            <td class="text-right">{{ number_format($manifest['total_volume'] ?? 0, 2) }}</td>
                            <td class="text-right">{{ number_format($manifest['processing_days'] ?? 0, 1) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray font-bold">
                        <td colspan="4">TOTALS</td>
                        <td class="text-right">{{ number_format(collect($manifest_data)->sum('package_count')) }}</td>
                        <td class="text-right">{{ number_format(collect($manifest_data)->sum('total_weight'), 1) }}</td>
                        <td class="text-right">{{ number_format(collect($manifest_data)->sum('total_volume'), 2) }}</td>
                        <td class="text-right">{{ number_format(collect($manifest_data)->avg('processing_days'), 1) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    {{-- Efficiency Metrics --}}
    @if(isset($efficiency_data) && !empty($efficiency_data))
        <div class="section page-break">
            <h2>Efficiency Metrics by Type</h2>
            <table>
                <thead>
                    <tr>
                        <th>Manifest Type</th>
                        <th class="text-right">Count</th>
                        <th class="text-right">Avg Packages</th>
                        <th class="text-right">Avg Weight</th>
                        <th class="text-right">Avg Volume</th>
                        <th class="text-right">Avg Processing</th>
                        <th class="text-right">Efficiency Score</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($efficiency_data as $efficiency)
                        <tr>
                            <td class="font-bold">{{ strtoupper($efficiency['type'] ?? 'N/A') }}</td>
                            <td class="text-right">{{ number_format($efficiency['count'] ?? 0) }}</td>
                            <td class="text-right">{{ number_format($efficiency['avg_packages'] ?? 0, 1) }}</td>
                            <td class="text-right">{{ number_format($efficiency['avg_weight'] ?? 0, 1) }} lbs</td>
                            <td class="text-right">{{ number_format($efficiency['avg_volume'] ?? 0, 2) }} ft³</td>
                            <td class="text-right">{{ number_format($efficiency['avg_processing'] ?? 0, 1) }} days</td>
                            <td class="text-right">
                                <span class="text-{{ ($efficiency['efficiency_score'] ?? 0) >= 80 ? 'green' : (($efficiency['efficiency_score'] ?? 0) >= 60 ? 'blue' : 'red') }}">
                                    {{ number_format($efficiency['efficiency_score'] ?? 0, 1) }}%
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Performance Trends --}}
    @if(isset($trends_data) && !empty($trends_data))
        <div class="section">
            <h2>Monthly Performance Trends</h2>
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th class="text-right">Manifests</th>
                        <th class="text-right">Packages</th>
                        <th class="text-right">Avg Processing</th>
                        <th class="text-right">Completion Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($trends_data as $trend)
                        <tr>
                            <td class="font-bold">{{ $trend['month'] ?? 'N/A' }}</td>
                            <td class="text-right">{{ number_format($trend['manifest_count'] ?? 0) }}</td>
                            <td class="text-right">{{ number_format($trend['package_count'] ?? 0) }}</td>
                            <td class="text-right">{{ number_format($trend['avg_processing'] ?? 0, 1) }} days</td>
                            <td class="text-right">
                                <span class="text-{{ ($trend['completion_rate'] ?? 0) >= 90 ? 'green' : 'blue' }}">
                                    {{ number_format($trend['completion_rate'] ?? 0, 1) }}%
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