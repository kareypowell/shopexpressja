@component('mail::message')
# Backup Failed

A backup operation has failed on {{ config('app.name') }}.

**Backup Details:**
- **Backup ID:** {{ $backup->id }}
- **Backup Name:** {{ $backup->name }}
- **Backup Type:** {{ ucfirst($backup->type) }}
- **Failed At:** {{ $backup->updated_at->format('Y-m-d H:i:s') }}
- **Error:** {{ $error }}

**System Information:**
- **Server:** {{ request()->getHost() }}
- **Environment:** {{ config('app.env') }}

Please check the backup system and resolve the issue as soon as possible.

@component('mail::button', ['url' => route('admin.backup.dashboard')])
View Backup Dashboard
@endcomponent

Thanks,<br>
{{ config('app.name') }} Backup System
@endcomponent