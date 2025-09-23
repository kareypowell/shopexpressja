<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consolidated Package Ready for Pickup</title>
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
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
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
            color: #16a34a;
        }

        .consolidated-info {
            background-color: #f0fdf4;
            border-left: 4px solid #22c55e;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }

        .consolidated-info h3 {
            color: #16a34a;
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
            background-color: #16a34a;
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

        .packages-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* Cost breakdown table */
        .cost-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .cost-table th {
            background-color: #16a34a;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        .cost-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }

        .cost-table tr:last-child td {
            border-bottom: none;
            font-weight: bold;
            background-color: #f0fdf4;
        }

        .amount {
            text-align: right;
            font-weight: 600;
        }

        /* Button */
        .cta-button {
            display: inline-block;
            background-color: #16a34a;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }

        .cta-button:hover {
            background-color: #15803d;
        }

        /* Important notice */
        .important-notice {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }

        .important-notice p {
            color: #92400e;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .important-notice .notice-text {
            color: #92400e;
            font-size: 14px;
        }

        /* Pickup info */
        .pickup-info {
            background-color: #f0f9ff;
            border-left: 4px solid #0ea5e9;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }

        .pickup-info h4 {
            color: #0ea5e9;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .pickup-info p {
            color: #0c4a6e;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .pickup-info ul {
            color: #0c4a6e;
            font-size: 14px;
            line-height: 1.6;
            margin-left: 20px;
        }

        .pickup-info li {
            margin-bottom: 5px;
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
            color: #16a34a;
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

            .packages-table {
                font-size: 12px;
            }

            .packages-table th,
            .packages-table td {
                padding: 8px;
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
            <div class="header-subtitle">Consolidated Package Ready for Pickup</div>
        </div>

        <!-- Content -->
        <div class="email-content">
            <div class="greeting">
                Hi {{ $user->first_name }},
            </div>

            <p style="margin-bottom: 20px; color: #64748b; line-height: 1.6;">
                Excellent news! Your consolidated package containing <strong>{{ $individualPackages->count() }} individual packages</strong> is now <strong>ready for pickup</strong> at our office. 
                All packages in your consolidated group can be collected together with a single visit.
            </p>

            <!-- Consolidated Package Information -->
            <div class="consolidated-info">
                <h3>Consolidated Package Information</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 50%; vertical-align: top; padding: 10px 15px 10px 0;">
                            <div class="info-label">Consolidated Tracking Number</div>
                            <div class="info-value">{{ $consolidatedPackage->consolidated_tracking_number }}</div>
                        </td>
                        <td style="width: 50%; vertical-align: top; padding: 10px 0 10px 15px;">
                            <div class="info-label">Total Packages</div>
                            <div class="info-value">{{ $individualPackages->count() }} packages</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 50%; vertical-align: top; padding: 10px 15px 10px 0;">
                            <div class="info-label">Customer Name</div>
                            <div class="info-value">{{ $user->full_name }}</div>
                        </td>
                        <td style="width: 50%; vertical-align: top; padding: 10px 0 10px 15px;">
                            <div class="info-label">Account Number</div>
                            <div class="info-value">{{ $user->profile->account_number ?? 'N/A' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 50%; vertical-align: top; padding: 10px 15px 10px 0;">
                            <div class="info-label">Total Weight</div>
                            <div class="info-value">{{ number_format($consolidatedPackage->total_weight, 2) }} lbs</div>
                        </td>
                        <td style="width: 50%; vertical-align: top; padding: 10px 0 10px 15px;">
                            <div class="info-label">Total Quantity</div>
                            <div class="info-value">{{ $consolidatedPackage->total_quantity }} items</div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Individual Packages in Consolidation -->
            <h3 style="color: #16a34a; margin: 30px 0 15px 0;">Individual Packages Ready for Pickup</h3>
            <table class="packages-table">
                <thead>
                    <tr>
                        <th>Tracking Number</th>
                        <th>Description</th>
                        <th>Weight</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($individualPackages as $package)
                    <tr>
                        <td><strong>{{ $package->tracking_number }}</strong></td>
                        <td>{{ $package->description ?: 'N/A' }}</td>
                        <td>{{ number_format($package->weight, 2) }} lbs</td>
                        <td>1</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Cost Breakdown -->
            @if(isset($showCosts) && $showCosts)
            <h3 style="color: #16a34a; margin: 30px 0 15px 0;">Cost Breakdown</h3>
            <table class="cost-table">
                <thead>
                    <tr>
                        <th>Fee Type</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Freight Charges</td>
                        <td class="amount">${{ number_format($consolidatedPackage->total_freight_price, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Clearance Fee</td>
                        <td class="amount">${{ number_format($consolidatedPackage->total_clearance_fee, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Storage Fees</td>
                        <td class="amount">${{ number_format($consolidatedPackage->total_storage_fee, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Delivery Fees</td>
                        <td class="amount">${{ number_format($consolidatedPackage->total_delivery_fee, 2) }}</td>
                    </tr>
                    <tr>
                        <td><strong>Total Amount Due</strong></td>
                        <td class="amount"><strong>${{ number_format($consolidatedPackage->total_cost, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
            @endif

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.url') }}" class="cta-button">Track Your Consolidated Package</a>
            </div>

            <!-- Pickup Information -->
            <div class="pickup-info">
                <h4>Pickup Information & Requirements</h4>
                <p><strong>Pickup Hours:</strong> Monday - Friday: 9:00 AM - 5:00 PM, Saturday: 10:00 AM - 3:00 PM</p>
                <p><strong>What to Bring:</strong></p>
                <ul>
                    <li>Valid government-issued photo identification</li>
                    <li>This email notification (printed or on your mobile device)</li>
                    <li>Payment for any outstanding fees (cash, card, or check accepted)</li>
                    <li>Any additional documentation if requested during processing</li>
                </ul>
                <p><strong>Note:</strong> All {{ $individualPackages->count() }} packages in your consolidated group will be released together as a single pickup.</p>
            </div>

            <!-- Important Notice -->
            <div class="important-notice">
                <p>Important Pickup Information</p>
                <div class="notice-text">
                    <strong>Consolidated Pickup Benefits:</strong><br>
                    • Single visit to collect all your packages<br>
                    • One payment transaction for all fees<br>
                    • Simplified documentation process<br>
                    • Reduced handling and processing time<br><br>
                    
                    <strong>Please Note:</strong> Your consolidated package must be collected as a complete group. 
                    Individual packages cannot be released separately from the consolidation.
                </div>
            </div>

            @if(isset($specialInstructions) && $specialInstructions)
            <div style="margin-top: 20px; padding: 15px; background-color: #fef2f2; border-radius: 8px; border-left: 4px solid #ef4444;">
                <p style="color: #dc2626; font-weight: 600; margin-bottom: 5px;">Special Instructions</p>
                <p style="color: #dc2626; font-size: 14px;">{{ $specialInstructions }}</p>
            </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <div class="footer-text">
                Thank you for choosing {{ config('app.name') }} for your shipping needs.
            </div>
            <div class="contact-info">
                For questions about your consolidated package pickup, please contact our customer service team.
            </div>
        </div>
    </div>
</body>
</html>