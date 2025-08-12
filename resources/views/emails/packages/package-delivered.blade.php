<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Delivered</title>
    <style>
        /* Reset styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Base styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            padding: 20px;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        /* Header */
        .email-header {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .company-logo {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .header-subtitle {
            font-size: 16px;
            opacity: 0.9;
        }

        /* Content */
        .email-content {
            padding: 30px 20px;
        }

        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #1e3a8a;
        }

        .status-success {
            background-color: #f0fdf4;
            border-left: 4px solid #059669;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }

        .status-success h3 {
            color: #059669;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .package-info {
            background-color: #f1f5f9;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }

        .package-info h3 {
            color: #1e3a8a;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
        }

        .info-label {
            font-weight: 600;
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            line-height: 1.4;
            display: block;
        }

        .info-value {
            font-size: 15px;
            color: #1e293b;
            line-height: 1.5;
            font-weight: 600;
            display: block;
            padding-top: 2px;
        }

        /* Customer info section */
        .customer-info {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .customer-info h4 {
            color: #1e3a8a;
            margin-bottom: 15px;
            font-size: 16px;
        }

        /* Footer */
        .email-footer {
            background-color: #f1f5f9;
            padding: 30px 20px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .footer-text {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .contact-info {
            color: #1e3a8a;
            font-weight: 600;
            font-size: 14px;
        }

        /* Responsive design */
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }

            .email-content {
                padding: 20px 15px;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .company-logo {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div class="company-logo">{{ config('app.name') }}</div>
            <div class="header-subtitle">Package Delivery Confirmation</div>
        </div>

        <!-- Content -->
        <div class="email-content">
            <div class="greeting">
                Hi {{ $user->first_name }},
            </div>

            <div class="status-success">
                <h3>✅ Package Successfully Delivered</h3>
                <p style="color: #065f46; line-height: 1.6;">
                    This is to confirm that your package has been successfully delivered. Thank you for choosing our services!
                </p>
            </div>

            <!-- Package Information -->
            <div class="package-info">
                <h3>Package Details</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Tracking Number</div>
                        <div class="info-value">{{ $trackingNumber }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Description</div>
                        <div class="info-value">{{ $description }}</div>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="customer-info">
                <h4>Your Contact Information</h4>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Customer Name</div>
                        <div class="info-value">{{ $user->full_name }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Account Number</div>
                        <div class="info-value">{{ $user->profile->account_number ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value">{{ $user->profile->telephone_number ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email Address</div>
                        <div class="info-value">{{ $user->email }}</div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 30px; padding: 20px; background-color: #f0fdf4; border-radius: 8px; border-left: 4px solid #10b981;">
                <p style="color: #065f46; font-size: 14px; line-height: 1.6;">
                    <strong>Thank You!</strong> We hope you are satisfied with our service. If you have any feedback or need assistance with future shipments, please don't hesitate to contact our customer support team.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <div class="footer-text">
                We appreciate your business and look forward to serving you again.
            </div>
            <div class="contact-info">
                Thank you for your business - {{ config('app.name') }}
            </div>
        </div>
    </div>
</body>
</html>