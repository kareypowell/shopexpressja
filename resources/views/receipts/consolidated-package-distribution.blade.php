<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consolidated Package Distribution Receipt - {{ $receipt_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        
        .company-details {
            font-size: 11px;
            color: #666;
            line-height: 1.3;
        }
        
        .receipt-title {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
            margin: 20px 0 10px 0;
            text-align: center;
            background-color: #f3f4f6;
            padding: 10px;
            border-radius: 5px;
        }
        
        .receipt-info {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .receipt-info-left,
        .receipt-info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .receipt-info-right {
            text-align: right;
        }
        
        .info-section {
            margin-bottom: 20px;
        }
        
        .info-title {
            font-weight: bold;
            color: #374151;
            margin-bottom: 8px;
            font-size: 13px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 3px;
        }
        
        .consolidated-summary {
            background-color: #dbeafe;
            border: 1px solid #3b82f6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .consolidated-summary h3 {
            margin: 0 0 10px 0;
            color: #1e40af;
            font-size: 14px;
        }
        
        .consolidated-details {
            display: table;
            width: 100%;
        }
        
        .consolidated-details > div {
            display: table-cell;
            width: 33.33%;
            padding: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #f9fafb;
            font-weight: bold;
            font-size: 11px;
            color: #374151;
        }
        
        td {
            font-size: 11px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .font-bold {
            font-weight: bold;
        }
        
        .totals-section {
            margin-top: 20px;
            border-top: 2px solid #374151;
            padding-top: 15px;
        }
        
        .totals-table {
            width: 60%;
            margin-left: auto;
            border: none;
        }
        
        .totals-table td {
            border: none;
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .totals-table .total-row {
            border-top: 1px solid #374151;
            font-weight: bold;
            font-size: 13px;
        }
        
        .payment-status {
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .payment-status.paid {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        
        .payment-status.partial {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #f59e0b;
        }
        
        .payment-status.unpaid {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
        
        .tracking-numbers {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            padding: 8px;
            font-size: 10px;
            margin-top: 5px;
        }
        
        .weight-badge {
            background-color: #e0e7ff;
            color: #3730a3;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .sea-badge {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .air-badge {
            background-color: #fef3c7;
            color: #92400e;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">{{ $company['name'] }}</div>
        <div class="company-details">
            {{ $company['address'] }}<br>
            Phone: {{ $company['phone'] }} | Email: {{ $company['email'] }}<br>
            Website: {{ $company['website'] }}
        </div>
    </div>

    <!-- Receipt Title -->
    <div class="receipt-title">
        CONSOLIDATED PACKAGE DISTRIBUTION RECEIPT
    </div>

    <!-- Receipt Information -->
    <div class="receipt-info">
        <div class="receipt-info-left">
            <div class="info-section">
                <div class="info-title">Receipt Details</div>
                <strong>Receipt #:</strong> {{ $receipt_number }}<br>
                <strong>Date:</strong> {{ $distribution_date }}<br>
                <strong>Time:</strong> {{ $distribution_time }}<br>
                <strong>Distributed By:</strong> {{ $distributed_by['name'] }}
            </div>
        </div>
        <div class="receipt-info-right">
            <div class="info-section">
                <div class="info-title">Customer Information</div>
                <strong>Name:</strong> {{ $customer['name'] }}<br>
                <strong>Email:</strong> {{ $customer['email'] }}<br>
                <strong>Account:</strong> {{ $customer['account_number'] }}
            </div>
        </div>
    </div>

    <!-- Consolidated Package Summary -->
    <div class="consolidated-summary">
        <h3>Consolidated Package: {{ $consolidated_tracking_number }}</h3>
        <div class="consolidated-details">
            <div>
                <strong>Total Weight:</strong> {{ $consolidated_totals['total_weight'] }} lbs<br>
                <strong>Package Count:</strong> {{ $consolidated_totals['total_quantity'] }}
            </div>
            <div>
                <strong>Freight:</strong> ${{ $consolidated_totals['total_freight_price'] }}<br>
                <strong>Customs:</strong> ${{ $consolidated_totals['total_customs_duty'] }}
            </div>
            <div>
                <strong>Storage:</strong> ${{ $consolidated_totals['total_storage_fee'] }}<br>
                <strong>Delivery:</strong> ${{ $consolidated_totals['total_delivery_fee'] }}
            </div>
        </div>
    </div>

    <!-- Individual Packages Table -->
    <div class="info-title">Individual Packages in Consolidation</div>
    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Tracking #</th>
                <th style="width: 25%;">Description</th>
                <th style="width: 10%;">{{ $packages[0]['weight_label'] ?? 'Weight' }}</th>
                <th style="width: 12%;">Freight</th>
                <th style="width: 12%;">Customs</th>
                <th style="width: 12%;">Storage</th>
                <th style="width: 12%;">Delivery</th>
                <th style="width: 12%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($packages as $package)
            <tr>
                <td class="font-bold">
                    {{ $package['tracking_number'] }}
                    @if($package['is_sea_package'])
                        <span class="weight-badge sea-badge">SEA</span>
                    @else
                        <span class="weight-badge air-badge">AIR</span>
                    @endif
                </td>
                <td>{{ $package['description'] }}</td>
                <td class="text-center">{{ $package['weight_display'] }}</td>
                <td class="text-right">${{ $package['freight_price'] }}</td>
                <td class="text-right">${{ $package['customs_duty'] }}</td>
                <td class="text-right">${{ $package['storage_fee'] }}</td>
                <td class="text-right">${{ $package['delivery_fee'] }}</td>
                <td class="text-right font-bold">${{ $package['total_cost'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals Section -->
    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td><strong>Subtotal:</strong></td>
                <td class="text-right">${{ number_format($subtotal, 2) }}</td>
            </tr>
            @if($write_off_amount > 0)
            <tr>
                <td><strong>Write-off/Discount:</strong></td>
                <td class="text-right">-${{ number_format($write_off_amount, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td><strong>Total Amount:</strong></td>
                <td class="text-right"><strong>${{ number_format($total_amount, 2) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Cash Collected:</strong></td>
                <td class="text-right">${{ number_format($amount_collected, 2) }}</td>
            </tr>
            @if($credit_applied > 0)
            <tr>
                <td><strong>Credit Applied:</strong></td>
                <td class="text-right">${{ number_format($credit_applied, 2) }}</td>
            </tr>
            @endif
            @if($account_balance_applied > 0)
            <tr>
                <td><strong>Account Balance Applied:</strong></td>
                <td class="text-right">${{ number_format($account_balance_applied, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td><strong>Total Received:</strong></td>
                <td class="text-right"><strong>${{ number_format($total_received, 2) }}</strong></td>
            </tr>
            @if($outstanding_balance > 0)
            <tr>
                <td><strong>Outstanding Balance:</strong></td>
                <td class="text-right" style="color: #dc2626;"><strong>${{ number_format($outstanding_balance, 2) }}</strong></td>
            </tr>
            @endif
        </table>
    </div>

    <!-- Payment Status -->
    <div class="payment-status {{ strtolower($payment_status) }}">
        PAYMENT STATUS: {{ strtoupper($payment_status) }}
    </div>

    <!-- Footer -->
    <div class="footer">
        <p><strong>Thank you for choosing {{ $company['name'] }}!</strong></p>
        <p>This is a computer-generated receipt. Please keep this receipt for your records.</p>
        <p>For inquiries, contact us at {{ $company['phone'] }} or {{ $company['email'] }}</p>
        <p><em>Generated on {{ now()->format('F j, Y \a\t g:i A') }}</em></p>
    </div>
</body>
</html>