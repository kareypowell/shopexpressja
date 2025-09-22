<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report_title ?? 'Business Report' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 24px;
            color: #1a202c;
            margin-bottom: 10px;
        }
        
        .header .meta {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #718096;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section h2 {
            font-size: 16px;
            color: #2d3748;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .section h3 {
            font-size: 14px;
            color: #4a5568;
            margin-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table th,
        table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        table th {
            background-color: #f7fafc;
            font-weight: 600;
            color: #2d3748;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        table td {
            font-size: 11px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .font-bold {
            font-weight: 600;
        }
        
        .text-green {
            color: #38a169;
        }
        
        .text-red {
            color: #e53e3e;
        }
        
        .text-blue {
            color: #3182ce;
        }
        
        .bg-gray {
            background-color: #f7fafc;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 15px;
            background-color: #f7fafc;
        }
        
        .summary-card h4 {
            font-size: 12px;
            color: #718096;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .summary-card .value {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
        }
        
        .chart-container {
            text-align: center;
            margin: 20px 0;
            page-break-inside: avoid;
        }
        
        .chart-container img {
            max-width: 100%;
            height: auto;
        }
        
        .chart-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #2d3748;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50px;
            border-top: 1px solid #e2e8f0;
            padding: 10px 0;
            font-size: 10px;
            color: #718096;
            text-align: center;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $report_title ?? 'Business Report' }}</h1>
        <div class="meta">
            <div>
                Generated: {{ $generated_at->format('F j, Y g:i A') }}
            </div>
            <div>
                ShipSharkLtd Business Intelligence
            </div>
        </div>
    </div>

    @yield('content')

    <div class="footer">
        <div>
            ShipSharkLtd - Confidential Business Report | Page <span class="pagenum"></span>
        </div>
    </div>
</body>
</html>