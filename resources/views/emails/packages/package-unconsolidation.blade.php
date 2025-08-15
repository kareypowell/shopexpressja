<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Consolidated Package Has Been Separated</title>
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
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
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
            color: #dc2626;
        }

        .unconsolidation-info {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }

        .unconsolidation-info h3 {
            color: #dc2626;
            margin-bottom: 15px;
            font-size: 18px;
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

        /* Individual packages table */
        .packages-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .packages-table th {
            background-color: #dc2626;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        .packages-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }

        .packages-table tr:last-child td {
            border-bottom: none;
        }

        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background-color: #d1fae5;
            color: #065f46;
        }

        /* Button */
        .cta-button {
            display: inline-block;
            background-color: #dc2626;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }

        .cta-button:hover {
            background-color: #b91c1c;
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
            color: #dc2626;
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

            .company-logo {
                font-size: 24px;
            }

            .packages-table {
                font-size: 12px;
            }

            .packages-table th,
            .packages-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div class="company-logo">{{ config('app.name') }}</div>
            <div class="header-subtitle">Package Unconsolidation Notification</div>
        </div>

        <!-- Content -->
        <div class="email-content">
            <div class="greeting">
                Hi {{ $user->first_name }},
            </div>

            <p style="margin-bottom: 20px; color: #64748b; line-height: 1.6;">
                We wanted to inform you that your consolidated package has been separated back into {{ $packageCount }} individual packages. 
                Each package will now be processed and tracked independently using their original tracking numbers.
            </p>

            <!-- Former Consolidated Package Information -->
            <div class="unconsolidation-info">
                <h3>Former Consolidated Package</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 50%; vertical-align: top; padding: 10px 15px 10px 0;">
                            <div class="info-label">Former Consolidated Tracking Number</div>
                            <div class="info-value">{{ $formerConsolidatedTrackingNumber }}</div>
                        </td>
                        <td style="width: 50%; vertical-align: top; padding: 10px 0 10px 15px;">
                            <div class="info-label">Separated Into</div>
                            <div class="info-value">{{ $packageCount }} individual packages</div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Individual Packages -->
            <h3 style="color: #dc2626; margin: 30px 0 15px 0;">Your Individual Packages</h3>
            <p style="margin-bottom: 15px; color: #64748b; font-size: 14px;">
                Each package below is now being tracked and processed independently:
            </p>
            <table class="packages-table">
                <thead>
                    <tr>
                        <th>Tracking Number</th>
                        <th>Description</th>
                        <th>Weight</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($packages as $package)
                    <tr>
                        <td>{{ $package->tracking_number }}</td>
                        <td>{{ $package->description ?: 'N/A' }}</td>
                        <td>{{ number_format($package->weight, 2) }} lbs</td>
                        <td>
                            <span class="status-badge">
                                {{ ucfirst($package->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.url') }}" class="cta-button">Track Your Packages</a>
            </div>

            <div style="margin-top: 30px; padding: 20px; background-color: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
                <p style="color: #92400e; font-size: 14px; line-height: 1.6;">
                    <strong>What This Means:</strong><br>
                    • Each package will now receive individual status updates<br>
                    • You can track each package separately using their original tracking numbers<br>
                    • Delivery and pickup arrangements may be handled individually<br>
                    • Any fees or charges will be calculated per individual package
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <div class="footer-text">
                Thank you for choosing {{ config('app.name') }} for your shipping needs.
            </div>
            <div class="contact-info">
                For questions about your packages, please contact our customer service team.
            </div>
        </div>
    </div>
</body>
</html>