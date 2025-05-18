@component('mail::message')
# Package ready for Pickup

Hello {{ $user->first_name }},

This is to inform you that your package is **"Ready"** and at our office. Please ensure you bring valid proof 
of identification along with you.

## Package Details:
**Tracking Number:** {{ $trackingNumber }}<br />
**Description:** {{ $description }}

If you have any questions or need assistance, please don't hesitate to contact our customer support team.

Thank you for your cooperation, <br />
{{ config('app.name') }}
@endcomponent