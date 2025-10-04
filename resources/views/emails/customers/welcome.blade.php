@component('mail::message')
# Welcome to Shop Express JA!

Hi {{ $firstName }},

Welcome to the Shop Express JA family! We're excited to have you as our customer and look forward to being your trusted freight forwarding partner.

## Your Account Details

Your customer account has been successfully created with the following details:

@if($accountNumber)
**Account Number:** {{ $accountNumber }}
@endif

**Email:** {{ $email }}

@if($temporaryPassword)
**Temporary Password:** {{ $temporaryPassword }}

@component('mail::panel')
**Important:** Please change your temporary password after your first login for security purposes.
@endcomponent
@endif

## Getting Started

To get started with your shipping, please follow these steps:

1. **Login to Your Account**
@component('mail::button', ['url' => $loginUrl])
Login to Your Account
@endcomponent

2. **Access Your Shipping Information**
   Visit the "Shipping Information" page to get your unique shipping address. Make sure to use the exact format provided to avoid any delays.

@if(isset($shippingInfoUrl))
@component('mail::button', ['url' => $shippingInfoUrl, 'color' => 'success'])
Get Shipping Address
@endcomponent
@endif

3. **Start Shipping**
   Once you have your shipping address, you can start sending packages to our facility.

## Important Reminders

- Always include your account number on packages
- Use the exact shipping address format provided
- Contact us if you have any questions about the shipping process

## Need Help?

Our support team is here to help you every step of the way. If you have any questions or need assistance, don't hesitate to reach out:

- **Email:** [{{ $supportEmail }}](mailto:{{ $supportEmail }})
- **Phone:** Contact us through your customer portal

We're committed to providing you with excellent service and making your shipping experience as smooth as possible.

Thank you for choosing Shop Express JA as your freight forwarding partner!

Best regards,<br>
The Shop Express JA Team

---

@component('mail::subcopy')
If you're having trouble clicking the buttons above, copy and paste the following URLs into your web browser:

Login URL: {{ $loginUrl }}
@if(isset($shippingInfoUrl))
Shipping Info URL: {{ $shippingInfoUrl }}
@endif
@endcomponent

@endcomponent