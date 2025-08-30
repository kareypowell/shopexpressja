{{ $broadcastMessage->subject }}

Dear {{ $customer->full_name }},

{!! strip_tags($broadcastMessage->content) !!}

Best regards,
{{ $companyName }} Team

---

This message was sent to {{ $customer->email }}
If you have any questions, please contact us at {{ $supportEmail }}

To unsubscribe from these notifications, visit: {{ $unsubscribeUrl }}

This is an automated message. Please do not reply directly to this email.