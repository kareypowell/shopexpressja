<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $company['name'] }} - Consolidated Package Distribution Receipt</title>
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

        /* Consolidated Package Summary */
        .consolidated-summary {
            background-color: #dbeafe;
            border: 1px solid #3b82f6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .consolidated-summary h3 {
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .consolidated-details {
            display: table;
            width: 100%;
        }

        .consolidated-details > div {
            display: table-cell;
            width: 33.33%;
            padding-right: 20px;
            vertical-align: top;
        }

        .consolidated-details p {
            font-size: 14px;
            margin-bottom: 8px;
        }

        .consolidated-details .label {
            font-weight: 600;
            color: #1e40af;
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
            margin-bottom: 0;
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

        .items-table .text-center {
            text-align: center;
        }

        .tracking-number {
            color: #0891b2;
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }

        .weight-badge {
            background-color: #e0e7ff;
            color: #3730a3;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            margin-left: 8px;
        }

        .sea-badge {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .air-badge {
            background-color: #fef3c7;
            color: #92400e;
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
                            <p>Consolidated Package Distribution Receipt</p>
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
                            <div class="total-amount">${{ is_numeric($total_amount) ? number_format((float)$total_amount, 2) : $total_amount }}</div>
                            <div class="total-label">Total Amount</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Consolidated Package Summary -->
        <div class="consolidated-summary">
            <h3>Consolidated Package: {{ $consolidated_tracking_number }}</h3>
            <div class="consolidated-details">
                <div>
                    <p><span class="label">Total Weight:</span> {{ $consolidated_totals['total_weight'] }} lbs</p>
                    <p><span class="label">Package Count:</span> {{ $consolidated_totals['total_quantity'] }}</p>
                </div>
                <div>
                    <p><span class="label">Freight:</span> ${{ is_numeric($consolidated_totals['total_freight_price'] ?? 0) ? number_format((float)$consolidated_totals['total_freight_price'], 2) : ($consolidated_totals['total_freight_price'] ?? '0.00') }}</p>
                    <p><span class="label">Clearance:</span> ${{ is_numeric($consolidated_totals['total_clearance_fee'] ?? 0) ? number_format((float)$consolidated_totals['total_clearance_fee'], 2) : ($consolidated_totals['total_clearance_fee'] ?? '0.00') }}</p>
                </div>
                <div>
                    <p><span class="label">Storage:</span> ${{ is_numeric($consolidated_totals['total_storage_fee'] ?? 0) ? number_format((float)$consolidated_totals['total_storage_fee'], 2) : ($consolidated_totals['total_storage_fee'] ?? '0.00') }}</p>
                    <p><span class="label">Delivery:</span> ${{ is_numeric($consolidated_totals['total_delivery_fee'] ?? 0) ? number_format((float)$consolidated_totals['total_delivery_fee'], 2) : ($consolidated_totals['total_delivery_fee'] ?? '0.00') }}</p>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="items-section">
            <h3>Individual Packages in Consolidation</h3>
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
                        <th class="text-right">Clearance</th>
                        <th class="text-right">Storage</th>
                        <th class="text-right">Delivery</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($packages as $package)
                    <tr>
                        <td class="tracking-number">
                            {{ $package['tracking_number'] }}
                            @if($package['is_sea_package'])
                                <span class="weight-badge sea-badge">SEA</span>
                            @else
                                <span class="weight-badge air-badge">AIR</span>
                            @endif
                        </td>
                        <td>{{ $package['description'] }}</td>
                        <td>{{ $package['weight_display'] }}</td>
                        <td class="text-right">${{ is_numeric($package['freight_price'] ?? 0) ? number_format((float)$package['freight_price'], 2) : ($package['freight_price'] ?? '0.00') }}</td>
                        <td class="text-right">${{ is_numeric($package['clearance_fee'] ?? 0) ? number_format((float)$package['clearance_fee'], 2) : ($package['clearance_fee'] ?? '0.00') }}</td>
                        <td class="text-right">${{ is_numeric($package['storage_fee'] ?? 0) ? number_format((float)$package['storage_fee'], 2) : ($package['storage_fee'] ?? '0.00') }}</td>
                        <td class="text-right">${{ is_numeric($package['delivery_fee'] ?? 0) ? number_format((float)$package['delivery_fee'], 2) : ($package['delivery_fee'] ?? '0.00') }}</td>
                        <td class="text-right">${{ is_numeric($package['total_cost'] ?? 0) ? number_format((float)$package['total_cost'], 2) : ($package['total_cost'] ?? '0.00') }}</td>
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
                                <td class="summary-amount">${{ is_numeric($subtotal) ? number_format((float)$subtotal, 2) : $subtotal }}</td>
                            </tr>
                        </table>
                        <table class="summary-row summary-total">
                            <tr>
                                <td class="summary-label">Total Amount:</td>
                                <td class="summary-amount">${{ is_numeric($total_amount) ? number_format((float)$total_amount, 2) : $total_amount }}</td>
                            </tr>
                        </table>
                        <table class="summary-row">
                            <tr>
                                <td class="summary-label">Cash Collected:</td>
                                <td class="summary-amount">${{ is_numeric($amount_collected) ? number_format((float)$amount_collected, 2) : $amount_collected }}</td>
                            </tr>
                        </table>
                        @if($credit_applied > 0)
                        <table class="summary-row">
                            <tr>
                                <td class="summary-label">Credit Applied:</td>
                                <td class="summary-amount">${{ is_numeric($credit_applied) ? number_format((float)$credit_applied, 2) : ($credit_applied ?? '0.00') }}</td>
                            </tr>
                        </table>
                        @endif
                        @if($account_balance_applied > 0)
                        <table class="summary-row">
                            <tr>
                                <td class="summary-label">Account Balance Applied:</td>
                                <td class="summary-amount">${{ is_numeric($account_balance_applied) ? number_format((float)$account_balance_applied, 2) : ($account_balance_applied ?? '0.00') }}</td>
                            </tr>
                        </table>
                        @endif
                        @if($write_off_amount > 0)
                        <table class="summary-row">
                            <tr>
                                <td class="summary-label">Discount/Write-off:</td>
                                <td class="summary-amount">-${{ is_numeric($write_off_amount) ? number_format((float)$write_off_amount, 2) : ($write_off_amount ?? '0.00') }}</td>
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
                                <td class="summary-amount">${{ is_numeric($total_paid ?? $total_received ?? 0) ? number_format((float)($total_paid ?? $total_received ?? 0), 2) : ($total_paid ?? $total_received ?? '0.00') }}</td>
                            </tr>
                        </table>
                        @if($outstanding_balance > 0)
                        <table class="summary-row">
                            <tr>
                                <td class="summary-label">Outstanding Balance:</td>
                                <td class="summary-amount">${{ is_numeric($outstanding_balance) ? number_format((float)$outstanding_balance, 2) : ($outstanding_balance ?? '0.00') }}</td>
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