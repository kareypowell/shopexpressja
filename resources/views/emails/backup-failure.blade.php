@component('mail::message')
# Backup Failed

The backup **{{ $backup->name }}** has failed and requires immediate attention.

@component('mail::panel')
**Backup Details:**
- **Type:** {{ ucfirst($backup->type) }}
- **Failed At:** {{ $failedAt->format('Y-m-d H:i:s') }}
- **Error Message:** {{ $errorMessage }}
@endcomponent

## Error Details

The backup process encountered the following error:

```
{{ $errorMessage }}
```

@if(!empty($systemHealth))
## System Health Status

@component('mail::table')
| Metric | Value | Status |
|:-------|:------|:-------|
| Overall Status | {{ ucfirst($systemHealth['overall_status'] ?? 'Unknown') }} | @if(($systemHealth['overall_status'] ?? '') === 'critical') ⚠️ Critical @elseif(($systemHealth['overall_status'] ?? '') === 'warning') ⚠️ Warning @else ✅ Healthy @endif |
| Recent Success Rate | {{ $systemHealth['recent_backups']['success_rate'] ?? 'N/A' }}% | @if(($systemHealth['recent_backups']['success_rate'] ?? 0) < 80) ⚠️ Low @else ✅ Good @endif |
| Storage Usage | {{ $systemHealth['storage_usage']['usage_percentage'] ?? 'N/A' }}% | @if(($systemHealth['storage_usage']['usage_percentage'] ?? 0) > 90) ⚠️ Critical @elseif(($systemHealth['storage_usage']['usage_percentage'] ?? 0) > 75) ⚠️ Warning @else ✅ Normal @endif |
@endcomponent
@endif

@component('mail::button', ['url' => route('admin.backups.index')])
View Backup Dashboard
@endcomponent

**Action Required:** Please check the backup system and resolve the issue to ensure data protection.

Thanks,<br>
{{ config('app.name') }}
@endcomponent