<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Backup Failed</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8d7da; padding: 20px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        .content { background: #fff; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px; }
        .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #dee2e6; color: #6c757d; }
        .button { display: inline-block; padding: 12px 24px; background: #dc3545; color: white; text-decoration: none; border-radius: 4px; margin: 15px 0; }
        .details { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .details ul { margin: 0; padding-left: 20px; }
        .error { background: #f8d7da; padding: 15px; border-radius: 4px; margin: 15px 0; border-left: 4px solid #dc3545; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; color: #721c24;">❌ Backup Failed</h1>
    </div>
    
    <div class="content">
        <p>A backup operation has failed on {{ config('app.name') }}.</p>
        
        <div class="details">
            <h3>Backup Details:</h3>
            <ul>
                <li><strong>Backup ID:</strong> {{ $backup->id }}</li>
                <li><strong>Backup Name:</strong> {{ $backup->name }}</li>
                <li><strong>Backup Type:</strong> {{ ucfirst($backup->type) }}</li>
                <li><strong>Failed At:</strong> {{ $backup->updated_at->format('Y-m-d H:i:s') }}</li>
            </ul>
        </div>

        <div class="error">
            <h3>Error Details:</h3>
            <p><strong>Error Message:</strong> {{ $error }}</p>
        </div>

        <div class="details">
            <h3>System Information:</h3>
            <ul>
                <li><strong>Server:</strong> {{ request()->getHost() }}</li>
                <li><strong>Environment:</strong> {{ config('app.env') }}</li>
            </ul>
        </div>

        <p><strong>Action Required:</strong> Please check the backup system and resolve the issue as soon as possible.</p>

        <a href="{{ route('backup-dashboard') }}" class="button">View Backup Dashboard</a>
    </div>
    
    <div class="footer">
        <p>Thanks,<br>{{ config('app.name') }} Backup System</p>
    </div>
</body>
</html>