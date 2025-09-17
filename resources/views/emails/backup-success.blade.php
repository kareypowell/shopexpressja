@component('mail::message')
# Backup Completed Successfully

The backup **{{ $backup->name }}** has been completed successfully.

@component('mail::panel')
**Backup Details:**
- **Type:** {{ ucfirst($backup->type) }}
- **File Size:** {{ $fileSizeMB }} MB
- **Duration:** {{ $duration }}
- **Created:** {{ $backup->created_at->format('Y-m-d H:i:s') }}
- **File Path:** {{ $backup->file_path }}
@endcomponent

@if(!empty($systemHealth))
## System Health Summary

@component('mail::table')
| Metric | Value |
|:-------|:------|
| Recent Success Rate | {{ $systemHealth['recent_backups']['success_rate'] ?? 'N/A' }}% |
| Storage Usage | {{ $systemHealth['storage_usage']['usage_percentage'] ?? 'N/A' }}% |
| Active Schedules | {{ $systemHealth['schedule_health']['total_schedules'] ?? 'N/A' }} |
@endcomponent
@endif

This is an automated notification from the ShipSharkLtd backup system.

Thanks,<br>
{{ config('app.name') }}
@endcomponent