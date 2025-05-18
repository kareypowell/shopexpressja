@component('mail::message')
# Package Delayed

Hello {{ $user->first_name }},

We regret to inform you that your package has been **"Delayed"**. We are currently working to resolve the issue and will keep you updated on the status.

## Package Details:
**Tracking Number:** {{ $trackingNumber }}<br />
**Description:** {{ $description }}

If you have any questions or need assistance, please don't hesitate to contact our customer support team.

Thank you for your cooperation, <br />
{{ config('app.name') }}
@endcomponent