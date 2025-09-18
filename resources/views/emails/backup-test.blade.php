<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Backup System Test Notification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .content { background: #fff; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px; }
        .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #dee2e6; color: #6c757d; }
        .success-list { list-style: none; padding: 0; }
        .success-list li { padding: 5px 0; }
        .success-list li:before { content: "✅ "; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Backup System Test Notification</h1>
    </div>
    
    <div class="content">
        <p>{{ $testMessage }}</p>
        
        <h3>Test Details:</h3>
        <ul>
            <li><strong>System:</strong> {{ $systemName }}</li>
            <li><strong>Test Time:</strong> {{ $timestamp->format('Y-m-d H:i:s') }}</li>
            <li><strong>Server:</strong> {{ request()->getHost() }}</li>
            <li><strong>Environment:</strong> {{ config('app.env') }}</li>
        </ul>
        
        <p>This test email confirms that:</p>
        <ul class="success-list">
            <li>Your email configuration is working correctly</li>
            <li>Backup notifications will be delivered successfully</li>
            <li>The notification system is operational</li>
        </ul>
    </div>
    
    <div class="footer">
        <p>Thanks,<br>{{ config('app.name') }} Backup System</p>
    </div>
</body>
</html>