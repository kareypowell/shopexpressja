<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Delivery Receipt</title>
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

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        @media only screen and (min-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr 1fr 1fr;
            }
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-weight: 600;
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 14px;
            color: #1e293b;
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

            .info-grid {
                grid-template-columns: 1fr;
                gap: 10px;
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
            <div class="company-logo">{{ $company_name }}</div>
            <div class="header-subtitle">Package Delivery Receipt</div>
        </div>

        <!-- Content -->
        <div class="email-content">
            <div class="greeting">
                Hello {{ $customer->first_name }},
            </div>

            <p style="margin-bottom: 20px; color: #64748b; line-height: 1.6;">
                Thank you for choosing {{ $company_name }}! Your packages have been successfully distributed. 
                Please find the details of your delivery below.
            </p>

            <!-- Receipt Information -->
            <div class="receipt-info">
                <h3>Receipt Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Receipt Number</div>
                        <div class="info-value">{{ $receipt_number }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Distribution Date</div>
                        <div class="info-value">{{ $distributed_at }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Customer Name</div>
                        <div class="info-value">{{ $customer->full_name }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Account Number</div>
                        <div class="info-value">{{ $customer->profile->account_number ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value">{{ $customer->profile->telephone_number ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Payment Status</div>
                        <div class="info-value">
                            <span class="payment-status {{ strtolower($distribution->payment_status) }}">
                                {{ $payment_status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Package Details -->
            <div class="packages-section">
                <h2 class="section-title">Package Details</h2>
                
                <table class="packages-table">
                    <thead>
                        <tr>
                            <th>Tracking Number</th>
                            <th>Description</th>
                            <th>Freight</th>
                            <th>Customs</th>
                            <th>Storage</th>
                            <th>Delivery</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($packages as $package)
                        <tr>
                            <td><strong>{{ $package['tracking_number'] }}</strong></td>
                            <td>{{ $package['description'] }}</td>
                            <td class="amount">${{ number_format($package['freight_price'], 2) }}</td>
                            <td class="amount">${{ number_format($package['customs_duty'], 2) }}</td>
                            <td class="amount">${{ number_format($package['storage_fee'], 2) }}</td>
                            <td class="amount">${{ number_format($package['delivery_fee'], 2) }}</td>
                            <td class="amount"><strong>${{ number_format($package['total_cost'], 2) }}</strong></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="totals-section">
                <table class="totals-table">
                    <tr>
                        <td class="totals-label">Freight Total:</td>
                        <td class="totals-amount">${{ number_format($totals['freight_total'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="totals-label">Customs Total:</td>
                        <td class="totals-amount">${{ number_format($totals['customs_total'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="totals-label">Storage Total:</td>
                        <td class="totals-amount">${{ number_format($totals['storage_total'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="totals-label">Delivery Total:</td>
                        <td class="totals-amount">${{ number_format($totals['delivery_total'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="totals-label">Grand Total:</td>
                        <td class="totals-amount">${{ number_format($totals['grand_total'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="totals-label">Amount Collected:</td>
                        <td class="totals-amount">${{ number_format($totals['amount_collected'], 2) }}</td>
                    </tr>
                    @if($totals['balance'] > 0)
                    <tr>
                        <td class="totals-label">Outstanding Balance:</td>
                        <td class="totals-amount" style="color: #dc2626;">${{ number_format($totals['balance'], 2) }}</td>
                    </tr>
                    @endif
                </table>
            </div>

            @if($totals['balance'] > 0)
            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 0 8px 8px 0;">
                <p style="color: #92400e; font-weight: 600; margin-bottom: 5px;">Outstanding Balance</p>
                <p style="color: #92400e; font-size: 14px;">
                    You have an outstanding balance of ${{ number_format($totals['balance'], 2) }}. 
                    Please contact us to arrange payment.
                </p>
            </div>
            @endif

            <div style="margin-top: 30px; padding: 20px; background-color: #f0f9ff; border-radius: 8px; border-left: 4px solid #0ea5e9;">
                <p style="color: #0c4a6e; font-size: 14px; line-height: 1.6;">
                    <strong>Important:</strong> Please keep this receipt for your records. 
                    A PDF copy has been attached to this email for your convenience.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <div class="footer-text">
                Thank you for choosing {{ $company_name }} for your shipping needs.
            </div>
            <div class="contact-info">
                For questions about this receipt, please contact our customer service team.
            </div>
        </div>
    </div>
</body>
</html>