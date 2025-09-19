<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $company['name'] }} - Package Distribution Receipt</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: white;
            padding: 20px;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
        }

        /* Header Section */
        .header {
            width: 100%;
            margin-bottom: 30px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-left {
            width: 60%;
            vertical-align: top;
        }

        .header-right {
            width: 40%;
            vertical-align: top;
            text-align: right;
        }

        .logo {
            height: 60px;
            width: auto;
            margin-right: 15px;
            vertical-align: middle;
        }

        .header-title {
            display: inline-block;
            vertical-align: middle;
        }

        .header-title h1 {
            font-weight: 900;
            font-size: 30px;
            color: #111827;
            margin-bottom: 5px;
        }

        .header-title p {
            font-size: 14px;
            color: #6b7280;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .status-badge.paid {
            background-color: #059669;
            color: white;
        }

        .status-badge.partial {
            background-color: #d97706;
            color: white;
        }

        .status-badge.unpaid {
            background-color: #dc2626;
            color: white;
        }

        /* Company Info */
        .company-info {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
        }

        .company-info p {
            color: #374151;
            font-size: 14px;
        }

        /* Receipt Details */
        .receipt-details {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-column {
            width: 33.33%;
            vertical-align: top;
            padding-right: 20px;
        }

        .details-section h3 {
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .details-section p {
            font-size: 14px;
            margin-bottom: 4px;
        }

        .details-section .label {
            font-weight: 600;
        }

        .total-highlight {
            background-color: #fef3c7;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .total-amount {
            font-weight: 900;
            font-size: 24px;
            color: #d97706;
            margin-bottom: 4px;
        }

        .total-label {
            font-size: 14px;
            color: #d97706;
        }

        .receipt-number {
            font-family: 'Courier New', monospace;
            font-weight: 700;
            color: #0891b2;
            background: #f0f9ff;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #e0f2fe;
        }

        /* Items Section */
        .items-section h3 {
            font-weight: 700;
            color: #111827;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 25px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }

        .items-table {
            width: 100%;
            min-width: 800px;
            border-collapse: collapse;
        }

        .items-table thead {
            background-color: #0891b2;
            color: white;
        }

        .items-table th,
        .items-table td {
            padding: 12px;
            text-align: left;
            font-size: 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table th {
            font-weight: 600;
        }

        .items-table .text-right {
            text-align: right;
        }

        .tracking-number {
            color: #0891b2;
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }

        /* Payment Summary */
        .payment-summary {
            width: 100%;
            margin-bottom: 30px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-spacer {
            width: 60%;
        }

        .summary-box {
            width: 40%;
            vertical-align: top;
        }

        .summary-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .summary-row td {
            padding: 8px 0;
            font-size: 14px;
        }

        .summary-label {
            text-align: right;
            padding-right: 20px;
        }

        .summary-amount {
            text-align: right;
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }

        .summary-total {
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 700;
            font-size: 18px;
        }

        .summary-total td {
            padding: 12px 0;
        }

        .summary-paid {
            color: #059669;
            font-weight: 600;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding-top: 25px;
            border-top: 1px solid #e5e7eb;
        }

        .footer h4 {
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }

        .footer p {
            font-size: 14px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header Section -->
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="header-left">
                        <img src="{{ asset('img/shipsharkltd-logo.png') }}" alt="{{ $company['name'] }}" class="logo">
                        <div class="header-title">
                            <h1>RECEIPT</h1>
                            <p>Package Distribution Receipt</p>
                        </div>
                    </td>
                    <td class="header-right">
                        <div class="status-badge {{ strtolower($payment_status) }}">{{ $payment_status }}</div><br>
                        <span style="font-size: 14px; color: #6b7280;">Generated {{ date('F j, Y \a\t g:i A') }}</span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Company Info -->
        <div class="company-info">
            <p>{{ $company['address'] }} • {{ $company['phone'] }} • {{ $company['email'] }}</p>
        </div>

        <!-- Receipt Details -->
        <div class="receipt-details">
            <table class="details-table">
                <tr>
                    <td class="details-column">
                        <div class="details-section">
                            <h3>Receipt Details</h3>
                            <p><span class="label">Receipt #:</span><br><span class="receipt-number">{{ $receipt_number }}</span></p>
                            <p><span class="label">Date:</span> {{ $distribution_date }} at {{ $distribution_time }}</p>
                            <p><span class="label">Processed by:</span> {{ $distributed_by['name'] }}</p>
                        </div>
                    </td>
                    <td class="details-column">
                        <div class="details-section">
                            <h3>Customer Information</h3>
                            <p><span class="label">Name:</span> {{ $customer['name'] }}</p>
                            <p><span class="label">Email:</span> {{ $customer['email'] }}</p>
                            <p><span class="label">Account:</span> {{ $customer['account_number'] }}</p>
                        </div>
                    </td>
                    <td class="details-column">
                        <div class="total-highlight">
                            <div class="total-amount">${{ $total_amount }}</div>
                            <div class="total-label">Total Amount</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Items Table -->
        <div class="items-section">
            <h3>Package Details</h3>
            <div class="table-container">
                <table class="items-table">
                <thead>
                    <tr>
                        <th>Tracking</th>
                        <th>Description</th>
                        <th>
                            @php
                                $hasSeaPackages = collect($packages)->contains('is_sea_package', true);
                                $hasAirPackages = collect($packages)->contains('is_sea_package', false);
                            @endphp
                            @if($hasSeaPackages && $hasAirPackages)
                                Weight/Volume
                            @elseif($hasSeaPackages)
                                Cubic Feet
                            @else
                                Weight
                            @endif
                        </th>
                        <th class="text-right">Freight</th>
                        <th class="text-right">Customs</th>
                        <th class="text-right">Storage</th>
                        <th class="text-right">Delivery</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($packages as $package)
                    <tr>
                        <td class="tracking-number">{{ $package['tracking_number'] }}</td>
                        <td>{{ $package['description'] }}</td>
                        <td>{{ $package['weight_display'] }}</td>
                        <td class="text-right">${{ $package['freight_price'] }}</td>
                        <td class="text-right">${{ $package['customs_duty'] }}</td>
                        <td class="text-right">${{ $package['storage_fee'] }}</td>
                        <td class="text-right">${{ $package['delivery_fee'] }}</td>
                        <td class="text-right">${{ $package['total_cost'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>

        <!-- Payment Summary -->
        <div class="payment-summary">
            <table class="summary-table">
                <tr>
                    <td class="summary-spacer"></td>
                    <td class="summary-box">
                        <table class="summary-row">
                            <tr>
                                <td class="summary-label">Subtotal:</td>
                                <td class="summary-amount">${{ $subtotal }}</td>
                            </tr>
                        </table>
                        <table class="summary-row summary-total">
                            <tr>
                                <td class="summary-label">Total Amount:</td>
                                <td class="summary-amount">${{ $total_amount }}</td>
                            </tr>
                        </table>
                        <table class="summary-row">
                            <tr>
                                <td class="summary-label">Cash Collected:</td>
                                <td class="summary-amount">${{ $amount_collected }}</td>
                            </tr>
                        </table>
                        @if($credit_applied > 0)
                        <table class="summary-row">
                            <tr>
                                <td class="summary-label">Credit Applied:</td>
                                <td class="summary-amount">${{ $credit_applied }}</td>
                            </tr>
                        </table>
                        @endif
                        @if($account_balance_applied > 0)
                        <table class="summary-row">
                            <tr>
                                <td class="summary-label">Account Balance Applied:</td>
                                <td class="summary-amount">${{ $account_balance_applied }}</td>
                            </tr>
                        </table>
                        @endif
                        @if($write_off_amount > 0)
                        <table class="summary-row">
                            <tr>
                                <td class="summary-label">Discount/Write-off:</td>
                                <td class="summary-amount">-${{ $write_off_amount }}</td>
                            </tr>
                        </table>
                        @if($write_off_reason)
                        <table class="summary-row">
                            <tr>
                                <td class="summary-label" style="font-size: 12px; color: #6b7280; font-style: italic;">Reason:</td>
                                <td class="summary-amount" style="font-size: 12px; color: #6b7280; font-style: italic; text-align: right;">{{ $write_off_reason }}</td>
                            </tr>
                        </table>
                        @endif
                        @endif
                        <table class="summary-row summary-paid">
                            <tr>
                                <td class="summary-label">Total Paid:</td>
                                <td class="summary-amount">${{ $total_paid }}</td>
                            </tr>
                        </table>
                        @if($outstanding_balance > 0)
                        <table class="summary-row">
                            <tr>
                                <td class="summary-label">Outstanding Balance:</td>
                                <td class="summary-amount">${{ $outstanding_balance }}</td>
                            </tr>
                        </table>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            <h4>Thank you for choosing {{ $company['name'] }}</h4>
            <p>Keep this receipt for your records</p>
        </div>
    </div>
</body>
</html>