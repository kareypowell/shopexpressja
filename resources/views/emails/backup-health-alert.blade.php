@component('mail::message')
# Backup System Health Alert

@if($criticalWarnings->isNotEmpty())
The backup system has **{{ $criticalWarnings->count() }}** critical issue(s) that require immediate attention.
@else
The backup system has **{{ $warningCount }}** warning(s) that should be reviewed.
@endif

## System Warnings

@foreach($warnings as $warning)
@component('mail::panel')
**{{ ucfirst(str_replace('_', ' ', $warning['type'])) }}**
@if($warning['severity'] === 'critical')
🚨 **CRITICAL:** {{ $warning['message'] }}
@else
⚠️ **WARNING:** {{ $warning['message'] }}
@endif
@endcomponent
@endforeach

## System Health Overview

@component('mail::table')
| Component | Status | Details |
|:----------|:-------|:--------|
| Overall Status | {{ ucfirst($systemHealth['overall_status']) }} | @if($systemHealth['overall_status'] === 'critical') 🚨 Critical @elseif($systemHealth['overall_status'] === 'warning') ⚠️ Warning @else ✅ Healthy @endif |
| Recent Backups | {{ $systemHealth['recent_backups']['success_rate'] }}% success | {{ $systemHealth['recent_backups']['successful'] }}/{{ $systemHealth['recent_backups']['total'] }} successful |
| Storage Usage | {{ $systemHealth['storage_usage']['usage_percentage'] }}% | {{ $systemHealth['storage_usage']['total_size_mb'] }} MB used |
| Active Schedules | {{ $systemHealth['schedule_health']['health_percentage'] }}% healthy | {{ $systemHealth['schedule_health']['healthy_schedules'] }}/{{ $systemHealth['schedule_health']['total_schedules'] }} schedules |
@endcomponent

@if(!empty($systemHealth['failed_backups']))
## Recent Failed Backups

@foreach($systemHealth['failed_backups']->take(5) as $failedBackup)
- **{{ $failedBackup['name'] }}** ({{ $failedBackup['type'] }}) - {{ $failedBackup['created_at']->format('Y-m-d H:i') }}
  Error: {{ $failedBackup['error_message'] }}
@endforeach
@endif

@component('mail::button', ['url' => route('admin.backups.index')])
View Backup Dashboard
@endcomponent

**Action Required:** Please review the backup system and address the issues listed above.

Thanks,<br>
{{ config('app.name') }}
@endcomponent