@component('mail::message')
# Weekly User Signups Report

Hello,
Here is the report of user signups for the week:
- **Total Signups**: {{ $newUserCount }}
- **New Users**: {{ $newUsers }}

@component('mail::button', ['url' => ''])
Button Text
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent