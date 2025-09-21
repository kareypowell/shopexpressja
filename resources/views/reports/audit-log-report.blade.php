<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $report_title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 15px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0a274c;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #0a274c;
            margin-bottom: 5px;
        }
        .company-info {
            font-size: 9px;
            color: #666;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 15px 0;
            color: #333;
        }
        .report-meta {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 10px;
        }
        .meta-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }
        .meta-label {
            display: table-cell;
            font-weight: bold;
            width: 150px;
            color: #555;
        }
        .meta-value {
            display: table-cell;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #0a274c;
            margin: 20px 0 10px 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .filters-section {
            background-color: #fff3cd;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 10px;
        }
        .filter-item {
            margin-bottom: 3px;
        }
        .statistics-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .stat-column {
            display: table-cell;
            width: 33.33%;
            vertical-align: top;
            padding-right: 10px;
        }
        .stat-box {
            background-color: #f8f9fa;
            padding: 8px;
            border-radius: 3px;
            margin-bottom: 10px;
            text-align: center;
        }
        .stat-number {
            font-size: 18px;
            font-weight: bold;
            color: #0a274c;
        }
        .stat-label {
            font-size: 9px;
            color: #666;
            margin-top: 2px;
        }
        .audit-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 9px;
        }
        .audit-table th,
        .audit-table td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
            vertical-align: top;
        }
        .audit-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
            font-size: 8px;
        }
        .audit-table .date-cell {
            width: 80px;
            font-size: 8px;
        }
        .audit-table .event-cell {
            width: 60px;
        }
        .audit-table .action-cell {
            width: 60px;
        }
        .audit-table .user-cell {
            width: 80px;
        }
        .audit-table .type-cell {
            width: 80px;
        }
        .audit-table .data-cell {
            font-size: 8px;
            max-width: 100px;
            word-wrap: break-word;
        }
        .event-type-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .event-authentication {
            background-color: #d4edda;
            color: #155724;
        }
        .event-security {
            background-color: #f8d7da;
            color: #721c24;
        }
        .event-model {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .event-business {
            background-color: #fff3cd;
            color: #856404;
        }
        .event-financial {
            background-color: #e2e3e5;
            color: #383d41;
        }
        .event-system {
            background-color: #f4f4f5;
            color: #6c757d;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .page-break {
            page-break-before: always;
        }
        .no-data {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company['name'] }}</div>
        <div class="company-info">
            {{ $company['address'] }}<br>
            Phone: {{ $company['phone'] }} | Email: {{ $company['email'] }}<br>
            Website: {{ $company['website'] }}
        </div>
    </div>

    <div class="report-title">{{ $report_title }}</div>

    <div class="report-meta">
        <div class="meta-row">
            <div class="meta-label">Generated At:</div>
            <div class="meta-value">{{ $generated_at->format('F j, Y \a\t g:i A') }}</div>
        </div>
        <div class="meta-row">
            <div class="meta-label">Total Records:</div>
            <div class="meta-value">{{ number_format($total_records) }}</div>
        </div>
        @if($audit_logs->isNotEmpty())
        <div class="meta-row">
            <div class="meta-label">Date Range:</div>
            <div class="meta-value">
                {{ $audit_logs->min('created_at')->format('M j, Y') }} - 
                {{ $audit_logs->max('created_at')->format('M j, Y') }}
            </div>
        </div>
        @endif
    </div>

    @if(!empty($filters))
    <div class="section-title">Applied Filters</div>
    <div class="filters-section">
        @foreach($filters as $key => $value)
            @if(!empty($value))
            <div class="filter-item">
                <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}
            </div>
            @endif
        @endforeach
    </div>
    @endif

    @if($audit_logs->isNotEmpty())
    <div class="section-title">Summary Statistics</div>
    <div class="statistics-grid">
        <div class="stat-column">
            <div class="stat-box">
                <div class="stat-number">{{ number_format($total_records) }}</div>
                <div class="stat-label">Total Events</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">{{ $audit_logs->pluck('user_id')->filter()->unique()->count() }}</div>
                <div class="stat-label">Unique Users</div>
            </div>
        </div>
        <div class="stat-column">
            <div class="stat-box">
                <div class="stat-number">{{ $audit_logs->pluck('event_type')->unique()->count() }}</div>
                <div class="stat-label">Event Types</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">{{ $audit_logs->pluck('ip_address')->filter()->unique()->count() }}</div>
                <div class="stat-label">Unique IP Addresses</div>
            </div>
        </div>
        <div class="stat-column">
            <div class="stat-box">
                <div class="stat-number">{{ $audit_logs->where('event_type', 'security_event')->count() }}</div>
                <div class="stat-label">Security Events</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">{{ $audit_logs->where('action', 'failed_login')->count() }}</div>
                <div class="stat-label">Failed Logins</div>
            </div>
        </div>
    </div>

    <div class="section-title">Event Type Breakdown</div>
    <table class="audit-table">
        <thead>
            <tr>
                <th>Event Type</th>
                <th>Count</th>
                <th>Percentage</th>
                <th>Most Common Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($audit_logs->groupBy('event_type') as $eventType => $logs)
            <tr>
                <td>
                    <span class="event-type-badge event-{{ str_replace('_', '-', $eventType) }}">
                        {{ ucfirst(str_replace('_', ' ', $eventType)) }}
                    </span>
                </td>
                <td>{{ $logs->count() }}</td>
                <td>{{ number_format(($logs->count() / $total_records) * 100, 1) }}%</td>
                <td>{{ $logs->groupBy('action')->sortByDesc(function($actions) { return $actions->count(); })->keys()->first() ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    <div class="section-title">Detailed Audit Log</div>
    <table class="audit-table">
        <thead>
            <tr>
                <th class="date-cell">Date/Time</th>
                <th class="event-cell">Event Type</th>
                <th class="action-cell">Action</th>
                <th class="user-cell">User</th>
                <th class="type-cell">Auditable Type</th>
                <th>IP Address</th>
                <th class="data-cell">Changes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($audit_logs->take(500) as $log)
            <tr>
                <td class="date-cell">{{ $log->created_at->format('m/d/Y H:i') }}</td>
                <td class="event-cell">
                    <span class="event-type-badge event-{{ str_replace('_', '-', $log->event_type) }}">
                        {{ $log->event_type }}
                    </span>
                </td>
                <td class="action-cell">{{ $log->action }}</td>
                <td class="user-cell">{{ $log->user ? $log->user->name : 'System' }}</td>
                <td class="type-cell">{{ class_basename($log->auditable_type) }}</td>
                <td>{{ $log->ip_address ?? 'N/A' }}</td>
                <td class="data-cell">
                    @if($log->old_values || $log->new_values)
                        @if($log->old_values && $log->new_values)
                            Changed: {{ count($log->new_values) }} field(s)
                        @elseif($log->new_values)
                            Created with {{ count($log->new_values) }} field(s)
                        @else
                            Deleted
                        @endif
                    @else
                        {{ $log->additional_data ? 'Additional data available' : 'No changes recorded' }}
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($audit_logs->count() > 500)
    <div style="text-align: center; margin: 10px 0; font-style: italic; color: #666;">
        Note: Only the first 500 records are shown in this report. 
        Total records: {{ number_format($total_records) }}
    </div>
    @endif

    @else
    <div class="no-data">
        No audit log data available for the specified criteria.
    </div>
    @endif

    <div class="footer">
        <p>This audit report was generated by {{ $company['name'] }} audit system.</p>
        <p>Generated on {{ $generated_at->format('F j, Y \a\t g:i A') }}</p>
        <p>For questions about this report, please contact {{ $company['email'] }}</p>
    </div>
</body>
</html>