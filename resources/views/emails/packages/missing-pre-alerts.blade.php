@component('mail::message')
# Package Pre-Alert Missing

Hello {{ $user->first_name }},

We've received a package for you, but we noticed that a pre-alert wasn't submitted. Pre-alerts help us process your packages more efficiently and ensure they're handled properly.

## Package Details:
**Tracking Number:** {{ $trackingNumber }}<br />
**Description:** {{ $description }}

Your package has been set to **"Pending"** status. To expedite processing, please submit a pre-alert using the button below:

@component('mail::button', ['url' => $url, 'color' => 'primary'])
Submit Pre-Alert Now
@endcomponent

If you have any questions or need assistance, please don't hesitate to contact our customer support team.

Thank you for your cooperation, <br />
{{ config('app.name') }}

@component('mail::subcopy')
If you're having trouble clicking the "Submit Pre-Alert Now" button, copy and paste the URL below into your web browser: {{ $url }}
@endcomponent
@endcomponent