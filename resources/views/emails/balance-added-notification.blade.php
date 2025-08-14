<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Balance Updated - Ship Heaven Sharks Ltd</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;900&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Open Sans', Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background-color: #f9fafb;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
            color: white;
            text-align: center;
            padding: 32px 24px;
        }
        .header h1 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 900;
            font-size: 24px;
            margin-bottom: 8px;
        }
        .header p {
            color: #c4b5fd;
            font-size: 14px;
        }
        .content {
            padding: 32px 24px;
        }
        .greeting {
            color: #1f2937;
            margin-bottom: 24px;
        }
        .intro {
            color: #374151;
            margin-bottom: 32px;
        }
        .amount-highlight {
            background: linear-gradient(135deg, #22d3ee 0%, #0891b2 100%);
            color: white;
            text-align: center;
            padding: 32px 24px;
            border-radius: 8px;
            margin-bottom: 32px;
        }
        .amount-highlight .label {
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
            color: #a5f3fc;
        }
        .amount-highlight .amount {
            font-family: 'Montserrat', sans-serif;
            font-weight: 900;
            font-size: 36px;
            margin-bottom: 4px;
        }
        .amount-highlight .description {
            color: #a5f3fc;
            font-size: 14px;
        }
        .transaction-details {
            margin-bottom: 32px;
        }
        .transaction-details h2 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 16px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }
        .detail-row.border-top {
            border-top: 1px solid #e5e7eb;
            padding-top: 12px;
        }
        .detail-label {
            color: #6b7280;
            font-weight: 500;
        }
        .detail-value {
            color: #1f2937;
        }
        .detail-value.bold {
            font-weight: 600;
        }
        .balance-summary {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            margin-bottom: 32px;
        }
        .balance-summary h3 {
            color: #374151;
            font-weight: 500;
            margin-bottom: 12px;
        }
        .balance-summary .total-balance {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 28px;
            color: #059669;
            margin-bottom: 8px;
        }
        .balance-summary .balance-label {
            color: #6b7280;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .balance-summary .balance-note {
            color: #9ca3af;
            font-size: 14px;
        }
        .cta-button {
            text-align: center;
            margin-bottom: 32px;
        }
        .cta-button a {
            display: inline-block;
            background-color: #7c3aed;
            color: white;
            font-weight: 500;
            padding: 12px 32px;
            border-radius: 8px;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        .cta-button a:hover {
            background-color: #6d28d9;
        }
        .additional-info {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 32px;
        }
        .additional-info p {
            margin-bottom: 12px;
        }
        .footer {
            background-color: #1f2937;
            color: #d1d5db;
            padding: 24px;
            text-align: center;
            font-size: 14px;
        }
        .footer .logo-section {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }
        .footer .logo {
            width: 24px;
            height: 24px;
            margin-right: 8px;
            filter: brightness(0) invert(1);
        }
        .footer .company-name {
            font-weight: 500;
        }
        .footer p {
            margin-bottom: 8px;
        }
        .footer .copyright {
            color: #9ca3af;
            font-size: 12px;
        }
        @media (max-width: 640px) {
            .email-container {
                margin: 0;
                box-shadow: none;
            }
            .content {
                padding: 24px 16px;
            }
            .header {
                padding: 24px 16px;
            }
            .amount-highlight .amount {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>Account Balance Updated</h1>
            <p>Your account has been credited</p>
        </div>

        <!-- Main Content -->
        <div class="content">
            <!-- Greeting -->
            <p class="greeting">Hi {{ $customerName }},</p>
            <p class="intro">Great news! Your {{ strtolower($balanceType) }} has been updated. Here are the details:</p>

            <!-- Amount Added Highlight -->
            <div class="amount-highlight">
                <p class="label">{{ strtoupper($balanceType) }} ADDED</p>
                <p class="amount">+${{ $amount }}</p>
                <p class="description">{{ $description }}</p>
            </div>

            <!-- Transaction Details -->
            <div class="transaction-details">
                <h2>Transaction Details</h2>
                <div class="detail-row">
                    <span class="detail-label">Amount Added: </span>
                    <span class="detail-value">+${{ $amount }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Balance Type: </span>
                    <span class="detail-value">{{ $balanceType }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Description: </span>
                    <span class="detail-value">{{ $description }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date & Time: </span>
                    <span class="detail-value">{{ $transactionDate }}</span>
                </div>
                <div class="detail-row border-top">
                    <span class="detail-label">New {{ $balanceType }}: </span>
                    <span class="detail-value bold">${{ $newBalance }}</span>
                </div>
            </div>

            <!-- Balance Summary -->
            <div class="balance-summary">
                <h3>Your Current Balance Summary</h3>
                <p class="total-balance">${{ $totalAvailableBalance }}</p>
                <p class="balance-label">Total Available Balance</p>
                <p class="balance-note">This includes both your account balance and any available credit.</p>
            </div>

            <!-- CTA Button -->
            <div class="cta-button">
                <a href="{{ config('app.url') }}">View Your Account</a>
            </div>

            <!-- Additional Info -->
            <div class="additional-info">
                <p>
                    @if($isAccountBalance)
                        This amount has been added to your main account balance and can be used for any charges or fees.
                    @else
                        This amount has been added to your credit balance, which will be automatically applied to future charges.
                    @endif
                </p>
                <p>If you have any questions about this transaction, please don't hesitate to contact our support team.</p>
                <p>Thank you for choosing our services!</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="logo-section">
                <img src="{{ asset('/img/shs-logo-white.png') }}" alt="Ship Heaven Sharks Ltd" class="logo">
                <!-- <span class="company-name">Ship Heaven Sharks Ltd</span> -->
            </div>
            <p>This is an automated notification from Ship Heaven Sharks Ltd.</p>
            <p>If you have questions, contact us at {{ config('mail.admin.email') }}</p>
            <p class="copyright">© {{ date('Y') }} Ship Heaven Sharks Ltd. All rights reserved.</p>
        </div>
    </div>
</body>
</html>