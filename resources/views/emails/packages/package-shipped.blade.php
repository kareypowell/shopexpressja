@component('mail::message')
# Package has been Shipped

Hello {{ $user->first_name }},

This is to inform you that your package has been **"Shipped"** and on its way to Jamaica. Once we've cleared 
customs, we will send you a notification when it's ready for pickup.

## Package Details:
**Tracking Number:** {{ $trackingNumber }}<br />
**Description:** {{ $description }}

If you have any questions or need assistance, please don't hesitate to contact our customer support team.

Thank you for your cooperation, <br />
{{ config('app.name') }}
@endcomponent