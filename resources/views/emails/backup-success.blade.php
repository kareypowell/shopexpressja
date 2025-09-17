@component('mail::message')
# Backup Completed Successfully

A backup operation has completed successfully on {{ config('app.name') }}.

**Backup Details:**
- **Backup ID:** {{ $backup->id }}
- **Backup Name:** {{ $backup->name }}
- **Backup Type:** {{ ucfirst($backup->type) }}
- **Completed At:** {{ $backup->completed_at->format('Y-m-d H:i:s') }}
- **File Size:** {{ $backup->formatted_file_size }}

@if($backup->metadata && isset($backup->metadata['backup_paths']))
**Backup Contents:**
@if(isset($backup->metadata['backup_paths']['database']))
- Database backup created
@endif
@if(isset($backup->metadata['backup_paths']['files']) && count($backup->metadata['backup_paths']['files']) > 0)
- {{ count($backup->metadata['backup_paths']['files']) }} file archive(s) created
@endif
@endif

**System Information:**
- **Server:** {{ request()->getHost() }}
- **Environment:** {{ config('app.env') }}

@component('mail::button', ['url' => route('admin.backup.dashboard')])
View Backup Dashboard
@endcomponent

Thanks,<br>
{{ config('app.name') }} Backup System
@endcomponent