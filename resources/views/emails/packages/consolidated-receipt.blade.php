<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consolidated Package Delivery Receipt</title>
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
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .company-logo {
            margin-bottom: 10px;
        }

        .company-logo img {
            height: 60px;
            width: auto;
            max-width: 200px;
            display: block;
            margin: 0 auto;
        }

        .company-logo-fallback {
            font-size: 28px;
            font-weight: bold;
            color: white;
            display: none;
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

        .receipt-info {
            background-color: #f1f5f9;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }

        .receipt-info h3 {
            color: #1e3a8a;
            margin-bottom: 15px;
            font-size: 18px;
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

        /* Payment status badge */
        .payment-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .payment-status.paid {
            background-color: #dcfce7;
            color: #166534;
        }

        .payment-status.partial {
            background-color: #fef3c7;
            color: #92400e;
        }

        .payment-status.unpaid {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* Package table */
        .packages-section {
            margin: 30px 0;
        }

        .section-title {
            font-size: 20px;
            color: #1e3a8a;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }

        .packages-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .packages-table th {
            background-color: #1e3a8a;
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .packages-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }

        .packages-table tr:last-child td {
            border-bottom: none;
        }

        .packages-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .amount {
            text-align: right;
            font-weight: 600;
        }

        /* Consolidated summary table */
        .consolidated-summary {
            background-color: #7c3aed;
            color: white;
        }

        .consolidated-summary td {
            font-weight: bold;
            font-size: 15px;
        }

        /* Totals section */
        .totals-section {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .totals-table tr:last-child td {
            border-bottom: 2px solid #1e3a8a;
            font-weight: bold;
            font-size: 16px;
            color: #1e3a8a;
            padding-top: 15px;
        }

        .totals-label {
            font-weight: 600;
            color: #64748b;
        }

        .totals-amount {
            text-align: right;
            font-weight: 600;
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

            .packages-table {
                font-size: 12px;
            }

            .packages-table th,
            .packages-table td {
                padding: 8px 5px;
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
            <div class="company-logo">
                <img src="{{ url('/img/shop-express-ja-logo.png') }}" alt="{{ $company_name }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';" />
                <div class="company-logo-fallback">{{ $company_name }}</div>
            </div>
            <div class="header-subtitle">Consolidated Package Delivery Receipt</div>
        </div>

        <!-- Content -->
        <div class="email-content">
            <div class="greeting">
                Hi {{ $customer->first_name }},
            </div>

            <p style="margin-bottom: 20px; color: #64748b; line-height: 1.6;">
                Thank you for choosing {{ $company_name }}! Your consolidated package containing {{ $individualPackages->count() }} individual packages has been successfully distributed. 
                Please find the detailed breakdown of your delivery below.
            </p>

            <!-- Receipt Information -->
            <div class="receipt-info">
                <h3>Receipt Information</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 33.33%; vertical-align: top; padding: 10px 15px 10px 0;">
                            <div class="info-label">Receipt Number</div>
                            <div class="info-value">{{ $receipt_number }}</div>
                        </td>
                        <td style="width: 33.33%; vertical-align: top; padding: 10px 15px;">
                            <div class="info-label">Distribution Date</div>
                            <div class="info-value">{{ $distributed_at }}</div>
                        </td>
                        <td style="width: 33.33%; vertical-align: top; padding: 10px 0 10px 15px;">
                            <div class="info-label">Customer Name</div>
                            <div class="info-value">{{ $customer->full_name }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 33.33%; vertical-align: top; padding: 10px 15px 10px 0;">
                            <div class="info-label">Account Number</div>
                            <div class="info-value">{{ $customer->profile->account_number ?? 'N/A' }}</div>
                        </td>
                        <td style="width: 33.33%; vertical-align: top; padding: 10px 15px;">
                            <div class="info-label">Phone Number</div>
                            <div class="info-value">{{ $customer->profile->telephone_number ?? 'N/A' }}</div>
                        </td>
                        <td style="width: 33.33%; vertical-align: top; padding: 10px 0 10px 15px;">
                            <div class="info-label">Payment Status</div>
                            <div class="info-value">
                                <span class="payment-status {{ strtolower($distribution->payment_status) }}">
                                    {{ $payment_status }}
                                </span>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

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
                            <div class="info-label">Total Individual Packages</div>
                            <div class="info-value">{{ $individualPackages->count() }} packages</div>
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

            <!-- Individual Package Details -->
            <div class="packages-section">
                <h2 class="section-title">Individual Package Details</h2>
                
                <table class="packages-table">
                    <thead>
                        <tr>
                            <th>Tracking Number</th>
                            <th>Description</th>
                            <th>Weight</th>
                            <th>Freight</th>
                            <th>Clearance</th>
                            <th>Storage</th>
                            <th>Delivery</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($individualPackages as $package)
                        <tr>
                            <td><strong>{{ $package->tracking_number }}</strong></td>
                            <td>{{ $package->description ?: 'N/A' }}</td>
                            <td>{{ number_format($package->weight, 2) }} lbs</td>
                            <td class="amount">${{ number_format($package->freight_price, 2) }}</td>
                            <td class="amount">${{ number_format($package->clearance_fee, 2) }}</td>
                            <td class="amount">${{ number_format($package->storage_fee, 2) }}</td>
                            <td class="amount">${{ number_format($package->delivery_fee, 2) }}</td>
                            <td class="amount"><strong>${{ number_format($package->total_cost, 2) }}</strong></td>
                        </tr>
                        @endforeach
                        <!-- Consolidated totals row -->
                        <tr class="consolidated-summary">
                            <td colspan="3"><strong>CONSOLIDATED TOTALS</strong></td>
                            <td class="amount"><strong>${{ number_format($consolidatedPackage->total_freight_price, 2) }}</strong></td>
                            <td class="amount"><strong>${{ number_format($consolidatedPackage->total_clearance_fee, 2) }}</strong></td>
                            <td class="amount"><strong>${{ number_format($consolidatedPackage->total_storage_fee, 2) }}</strong></td>
                            <td class="amount"><strong>${{ number_format($consolidatedPackage->total_delivery_fee, 2) }}</strong></td>
                            <td class="amount"><strong>${{ number_format($consolidatedPackage->total_cost, 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Payment Totals -->
            <div class="totals-section">
                <table class="totals-table">
                    <tr>
                        <td class="totals-label">Freight Total:</td>
                        <td class="totals-amount">${{ number_format($consolidatedPackage->total_freight_price, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="totals-label">Clearance Total:</td>
                        <td class="totals-amount">${{ number_format($consolidatedPackage->total_clearance_fee, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="totals-label">Storage Total:</td>
                        <td class="totals-amount">${{ number_format($consolidatedPackage->total_storage_fee, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="totals-label">Delivery Total:</td>
                        <td class="totals-amount">${{ number_format($consolidatedPackage->total_delivery_fee, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="totals-label">Grand Total:</td>
                        <td class="totals-amount">${{ number_format($totals['total_amount'], 2) }}</td>
                    </tr>
                    @if($totals['write_off_amount'] > 0)
                    <tr>
                        <td class="totals-label">Write-off/Discount:</td>
                        <td class="totals-amount" style="color: #059669;">-${{ number_format($totals['write_off_amount'], 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="totals-label">Cash Collected:</td>
                        <td class="totals-amount">${{ number_format($totals['amount_collected'], 2) }}</td>
                    </tr>
                    @if($totals['credit_applied'] > 0)
                    <tr>
                        <td class="totals-label">Credit Applied:</td>
                        <td class="totals-amount" style="color: #0ea5e9;">${{ number_format($totals['credit_applied'], 2) }}</td>
                    </tr>
                    @endif
                    @if($totals['account_balance_applied'] > 0)
                    <tr>
                        <td class="totals-label">Account Balance Applied:</td>
                        <td class="totals-amount" style="color: #7c3aed;">${{ number_format($totals['account_balance_applied'], 2) }}</td>
                    </tr>
                    @endif
                    @if($totals['outstanding_balance'] > 0)
                    <tr>
                        <td class="totals-label">Outstanding Balance:</td>
                        <td class="totals-amount" style="color: #dc2626;">${{ number_format($totals['outstanding_balance'], 2) }}</td>
                    </tr>
                    @endif
                </table>
            </div>

            @if($totals['outstanding_balance'] > 0)
            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 0 8px 8px 0;">
                <p style="color: #92400e; font-weight: 600; margin-bottom: 5px;">Outstanding Balance</p>
                <p style="color: #92400e; font-size: 14px;">
                    You have an outstanding balance of ${{ number_format($totals['outstanding_balance'], 2) }}. 
                    Please contact us to arrange payment.
                </p>
            </div>
            @endif

            <div style="margin-top: 30px; padding: 20px; background-color: #f0f9ff; border-radius: 8px; border-left: 4px solid #0ea5e9;">
                <p style="color: #0c4a6e; font-size: 14px; line-height: 1.6;">
                    <strong>Important:</strong> This receipt covers all {{ $individualPackages->count() }} packages in your consolidated group. 
                    Please keep this receipt for your records. A PDF copy has been attached to this email for your convenience.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <div class="footer-text">
                Thank you for choosing {{ $company_name }} for your shipping needs.
            </div>
            <div class="contact-info">
                For questions about this consolidated receipt, please contact our customer service team.
            </div>
        </div>
    </div>
</body>
</html>