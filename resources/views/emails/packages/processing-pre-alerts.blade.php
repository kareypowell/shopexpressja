@component('mail::message')
# Package being Processed

Hello {{ $user->first_name }},

We've received a package for you, and it's currently being **"Processed"** at our warehouse.

## Package Details:
**Tracking Number:** {{ $trackingNumber }}<br />
**Description:** {{ $description }}

If you have any questions or need assistance, please don't hesitate to contact our customer support team.

Thank you for your cooperation, <br />
{{ config('app.name') }}
@endcomponent