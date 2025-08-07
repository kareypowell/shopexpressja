<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Package Distribution Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        .company-info {
            font-size: 10px;
            color: #666;
        }
        .receipt-title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            color: #333;
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
        .info-section {
            margin-bottom: 15px;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .packages-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .packages-table th,
        .packages-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .packages-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        .packages-table .number-cell {
            text-align: right;
        }
        .totals-section {
            margin-top: 30px;
            border-top: 2px solid #007bff;
            padding-top: 15px;
        }
        .totals-table {
            width: 100%;
            max-width: 400px;
            margin-left: auto;
        }
        .totals-table td {
            padding: 5px 10px;
            border: none;
        }
        .totals-table .label {
            font-weight: bold;
            text-align: right;
        }
        .totals-table .amount {
            text-align: right;
            width: 100px;
        }
        .total-row {
            border-top: 1px solid #333;
            font-weight: bold;
            font-size: 14px;
        }
        .payment-status {
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            border-radius: 5px;
        }
        .payment-status.paid {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .payment-status.partial {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .payment-status.unpaid {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company['name'] }}</div>
        <div class="company-info">
            {{ $company['address'] }}<br>
            Phone: {{ $company['phone'] }} | Email: {{ $company['email'] }}<br>
            Website: {{ $company['website'] }}
        </div>
    </div>

    <div class="receipt-title">PACKAGE DISTRIBUTION RECEIPT</div>

    <div class="receipt-info">
        <div class="receipt-info-left">
            <div class="info-section">
                <div class="info-label">Receipt Number:</div>
                <div>{{ $receipt_number }}</div>
            </div>
            <div class="info-section">
                <div class="info-label">Distribution Date:</div>
                <div>{{ $distribution_date }} at {{ $distribution_time }}</div>
            </div>
            <div class="info-section">
                <div class="info-label">Distributed By:</div>
                <div>{{ $distributed_by['name'] }} ({{ $distributed_by['role'] }})</div>
            </div>
        </div>
        <div class="receipt-info-right">
            <div class="info-section">
                <div class="info-label">Customer Information:</div>
                <div>{{ $customer['name'] }}</div>
                <div>{{ $customer['email'] }}</div>
                <div>Account: {{ $customer['account_number'] }}</div>
            </div>
        </div>
    </div>

    <table class="packages-table">
        <thead>
            <tr>
                <th>Tracking Number</th>
                <th>Description</th>
                <th>Weight</th>
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
                <td>{{ $package['tracking_number'] }}</td>
                <td>{{ $package['description'] }}</td>
                <td class="number-cell">{{ $package['weight'] }} lbs</td>
                <td class="number-cell">${{ $package['freight_price'] }}</td>
                <td class="number-cell">${{ $package['customs_duty'] }}</td>
                <td class="number-cell">${{ $package['storage_fee'] }}</td>
                <td class="number-cell">${{ $package['delivery_fee'] }}</td>
                <td class="number-cell">${{ $package['total_cost'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="amount">${{ $subtotal }}</td>
            </tr>
            <tr class="total-row">
                <td class="label">Total Amount:</td>
                <td class="amount">${{ $total_amount }}</td>
            </tr>
            <tr>
                <td class="label">Amount Collected:</td>
                <td class="amount">${{ $amount_collected }}</td>
            </tr>
            @if($outstanding_balance > 0)
            <tr>
                <td class="label">Outstanding Balance:</td>
                <td class="amount">${{ $outstanding_balance }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="payment-status {{ strtolower($payment_status) }}">
        <strong>Payment Status: {{ $payment_status }}</strong>
    </div>

    <div class="footer">
        <p>Thank you for choosing {{ $company['name'] }}!</p>
        <p>This receipt was generated on {{ date('F j, Y \at g:i A') }}</p>
        <p>For inquiries, please contact us at {{ $company['email'] }} or {{ $company['phone'] }}</p>
    </div>
</body>
</html>