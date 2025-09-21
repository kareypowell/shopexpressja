<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Scheduled Audit Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #0a274c;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .report-info {
            background-color: white;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #0a274c;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .label {
            font-weight: bold;
            color: #555;
        }
        .value {
            color: #333;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 14px;
        }
        .attachment-notice {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .attachment-notice .icon {
            display: inline-block;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name', 'ShipShark Ltd') }}</h1>
        <p>Scheduled Audit Report</p>
    </div>

    <div class="content">
        <h2>{{ $report_name }}</h2>
        
        <p>Your scheduled audit report has been generated and is attached to this email.</p>

        <div class="report-info">
            <div class="info-row">
                <span class="label">Report Name:</span>
                <span class="value">{{ $report_name }}</span>
            </div>
            <div class="info-row">
                <span class="label">Generated At:</span>
                <span class="value">{{ $generated_at->format('F j, Y \a\t g:i A') }}</span>
            </div>
            <div class="info-row">
                <span class="label">Format:</span>
                <span class="value">{{ $format }}</span>
            </div>
        </div>

        <div class="attachment-notice">
            <span class="icon">📎</span>
            <strong>Attachment:</strong> The audit report is attached to this email as a {{ $format }} file.
        </div>

        <h3>About This Report</h3>
        <p>This audit report contains detailed information about system activities and user actions within your specified criteria. The report includes:</p>
        
        <ul>
            <li>Complete audit trail of system events</li>
            <li>User activity summaries</li>
            <li>Security event analysis</li>
            <li>Compliance-ready documentation</li>
        </ul>

        <h3>Important Notes</h3>
        <ul>
            <li>This report contains sensitive information and should be handled according to your organization's data security policies.</li>
            <li>The report reflects system activity up to the time of generation.</li>
            <li>For questions about this report, please contact your system administrator.</li>
        </ul>

        <div class="footer">
            <p>This is an automated message from {{ config('app.name', 'ShipShark Ltd') }} audit system.</p>
            <p>If you believe you received this email in error, please contact your system administrator.</p>
            <p>
                <strong>{{ config('app.name', 'ShipShark Ltd') }}</strong><br>
                Shop #24b Reliance Plaza, Mandeville, Manchester<br>
                Phone: 876-237-1191 | Email: support@shipsharkltd.com
            </p>
        </div>
    </div>
</body>
</html>