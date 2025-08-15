<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Packages Have Been Consolidated</title>
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
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
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
            color: #7c3aed;
        }

        .consolidated-info {
            background-color: #faf5ff;
            border-left: 4px solid #a855f7;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }

        .consolidated-info h3 {
            color: #7c3aed;
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
            background-color: #7c3aed;
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

        /* Benefits section */
        .benefits-section {
            background-color: #f0f9ff;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .benefits-section h4 {
            color: #0ea5e9;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .benefits-list {
            list-style: none;
            padding: 0;
        }

        .benefits-list li {
            padding: 8px 0;
            color: #0c4a6e;
            font-size: 14px;
            position: relative;
            padding-left: 20px;
        }

        .benefits-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #0ea5e9;
            font-weight: bold;
        }

        /* Button */
        .cta-button {
            display: inline-block;
            background-color: #7c3aed;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }

        .cta-button:hover {
            background-color: #6d28d9;
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
            color: #7c3aed;
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
            <div class="header-subtitle">Package Consolidation Notification</div>
        </div>

        <!-- Content -->
        <div class="email-content">
            <div class="greeting">
                Hi {{ $user->first_name }},
            </div>

            <p style="margin-bottom: 20px; color: #64748b; line-height: 1.6;">
                Great news! We've consolidated {{ $packageCount }} of your packages into a single group for more efficient processing and delivery. 
                This means easier tracking and potentially lower shipping costs for you.
            </p>

            <!-- Consolidated Package Information -->
            <div class="consolidated-info">
                <h3>New Consolidated Package</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 50%; vertical-align: top; padding: 10px 15px 10px 0;">
                            <div class="info-label">Consolidated Tracking Number</div>
                            <div class="info-value">{{ $consolidatedPackage->consolidated_tracking_number }}</div>
                        </td>
                        <td style="width: 50%; vertical-align: top; padding: 10px 0 10px 15px;">
                            <div class="info-label">Total Packages</div>
                            <div class="info-value">{{ $packageCount }} packages</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 50%; vertical-align: top; padding: 10px 15px 10px 0;">
                            <div class="info-label">Total Weight</div>
                            <div class="info-value">{{ number_format($consolidatedPackage->total_weight, 2) }} lbs</div>
                        </td>
                        <td style="width: 50%; vertical-align: top; padding: 10px 0 10px 15px;">
                            <div class="info-label">Current Status</div>
                            <div class="info-value">{{ ucfirst($consolidatedPackage->status) }}</div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Individual Packages -->
            <h3 style="color: #7c3aed; margin: 30px 0 15px 0;">Packages Included in This Consolidation</h3>
            <table class="packages-table">
                <thead>
                    <tr>
                        <th>Original Tracking Number</th>
                        <th>Description</th>
                        <th>Weight</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($individualPackages as $package)
                    <tr>
                        <td>{{ $package->tracking_number }}</td>
                        <td>{{ $package->description ?: 'N/A' }}</td>
                        <td>{{ number_format($package->weight, 2) }} lbs</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Benefits of Consolidation -->
            <div class="benefits-section">
                <h4>Benefits of Package Consolidation</h4>
                <ul class="benefits-list">
                    <li>Simplified tracking with one consolidated tracking number</li>
                    <li>Potentially reduced shipping and handling costs</li>
                    <li>Single pickup or delivery for all your packages</li>
                    <li>Streamlined notifications and updates</li>
                </ul>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.url') }}" class="cta-button">Track Your Consolidated Package</a>
            </div>

            <div style="margin-top: 30px; padding: 20px; background-color: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
                <p style="color: #92400e; font-size: 14px; line-height: 1.6;">
                    <strong>Important:</strong> You can still reference your original tracking numbers, but all future updates will be sent for the consolidated package. 
                    Individual package details remain accessible in your account.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <div class="footer-text">
                Thank you for choosing {{ config('app.name') }} for your shipping needs.
            </div>
            <div class="contact-info">
                For questions about your consolidated package, please contact our customer service team.
            </div>
        </div>
    </div>
</body>
</html>