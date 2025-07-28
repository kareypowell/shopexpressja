@component('mail::message')
# Weekly User Signups Report

Hello,

Here is the report of user signups for the week:

**Total Signups**: {{ $newUserCount }}

## New Users

@foreach($newUsers as $user)
- **{{ $user->first_name }} {{ $user->last_name }}** ({{ $user->email }}) - Registered {{ $user->created_at->format('M j, Y') }}
@endforeach

Thanks,<br>
{{ config('app.name') }}
@endcomponent