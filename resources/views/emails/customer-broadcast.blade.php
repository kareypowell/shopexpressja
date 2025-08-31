<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $broadcastMessage->subject }}</title>
    <style>
        /* Reset styles */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
        }

        /* Base styles */
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            min-width: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f8f9fa;
        }

        /* Container */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
            font-weight: 600;
        }
        .company-name {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }

        /* Content */
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .message-content {
            font-size: 16px;
            line-height: 1.7;
            color: #444444;
            margin-bottom: 30px;
        }
        .message-content p {
            margin: 0 0 15px 0;
        }
        .message-content h1, .message-content h2, .message-content h3 {
            color: #2c3e50;
            margin: 25px 0 15px 0;
        }
        .message-content ul, .message-content ol {
            padding-left: 20px;
            margin: 15px 0;
        }
        .message-content li {
            margin-bottom: 8px;
        }

        /* Footer */
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            border-top: 1px solid #e9ecef;
        }
        .footer-content {
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .footer-content p {
            margin: 0 0 10px 0;
        }
        .signature {
            font-weight: 600;
            color: #495057;
            margin-bottom: 20px;
        }
        .support-info {
            font-size: 12px;
            color: #868e96;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        .unsubscribe-link {
            color: #6c757d;
            text-decoration: underline;
        }
        .unsubscribe-link:hover {
            color: #495057;
        }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                margin: 0 !important;
            }
            .header, .content, .footer {
                padding: 20px !important;
            }
            .header h1 {
                font-size: 20px !important;
            }
            .greeting {
                font-size: 16px !important;
            }
            .message-content {
                font-size: 14px !important;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>{{ $broadcastMessage->subject }}</h1>
            <p class="company-name">{{ $companyName }}</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="message-content">
                {!! $content !!}
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-content">
                <div class="signature">
                    Best regards,<br>
                    {{ $companyName }} Team
                </div>

                <div class="support-info">
                    <p>This message was sent to {{ $customer->email }}</p>
                    <p>If you have any questions, please contact us at {{ $supportEmail }}</p>
                    <p>
                        <a href="{{ $unsubscribeUrl }}" class="unsubscribe-link">
                            Unsubscribe from these notifications
                        </a>
                    </p>
                    <p style="margin-top: 15px;">
                        <small>This is an automated message. Please do not reply directly to this email.</small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>