<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Backup Completed Successfully</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #d4edda; padding: 20px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .content { background: #fff; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px; }
        .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #dee2e6; color: #6c757d; }
        .button { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 15px 0; }
        .details { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .details ul { margin: 0; padding-left: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; color: #155724;">✅ Backup Completed Successfully</h1>
    </div>
    
    <div class="content">
        <p>A backup operation has completed successfully on {{ config('app.name') }}.</p>
        
        <div class="details">
            <h3>Backup Details:</h3>
            <ul>
                <li><strong>Backup ID:</strong> {{ $backup->id }}</li>
                <li><strong>Backup Name:</strong> {{ $backup->name }}</li>
                <li><strong>Backup Type:</strong> {{ ucfirst($backup->type) }}</li>
                <li><strong>Completed At:</strong> {{ $backup->completed_at ? $backup->completed_at->format('Y-m-d H:i:s') : $backup->created_at->format('Y-m-d H:i:s') }}</li>
                <li><strong>File Size:</strong> {{ $backup->formatted_file_size ?? 'Unknown' }}</li>
            </ul>
        </div>

        @if($backup->metadata && isset($backup->metadata['backup_paths']))
        <div class="details">
            <h3>Backup Contents:</h3>
            <ul>
                @if(isset($backup->metadata['backup_paths']['database']))
                <li>Database backup created</li>
                @endif
                @if(isset($backup->metadata['backup_paths']['files']) && count($backup->metadata['backup_paths']['files']) > 0)
                <li>{{ count($backup->metadata['backup_paths']['files']) }} file archive(s) created</li>
                @endif
            </ul>
        </div>
        @endif

        <div class="details">
            <h3>System Information:</h3>
            <ul>
                <li><strong>Server:</strong> {{ request()->getHost() }}</li>
                <li><strong>Environment:</strong> {{ config('app.env') }}</li>
            </ul>
        </div>

        <a href="{{ route('backup-dashboard') }}" class="button">View Backup Dashboard</a>
    </div>
    
    <div class="footer">
        <p>Thanks,<br>{{ config('app.name') }} Backup System</p>
    </div>
</body>
</html>