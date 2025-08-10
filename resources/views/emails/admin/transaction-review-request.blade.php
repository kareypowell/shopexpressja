<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Review Request</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        .alert {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .info-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .info-section h3 {
            margin-top: 0;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 5px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }
        .info-item {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #6c757d;
        }
        .info-value {
            color: #212529;
        }
        .transaction-amount {
            font-size: 1.2em;
            font-weight: bold;
        }
        .transaction-amount.credit {
            color: #28a745;
        }
        .transaction-amount.debit {
            color: #dc3545;
        }
        .review-reason {
            background-color: #fff;
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 4px;
            font-style: italic;
        }
        .action-buttons {
            text-align: center;
            margin-top: 30px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            text-align: center;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 0.9em;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚩 Transaction Review Request</h1>
        <p>A customer has flagged a transaction for review and requires your attention.</p>
    </div>

    <div class="alert">
        <strong>Action Required:</strong> Please review the transaction details below and take appropriate action.
    </div>

    <!-- Customer Information -->
    <div class="info-section">
        <h3>👤 Customer Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Name:</span>
                <span class="info-value">{{ $customer->full_name }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Email:</span>
                <span class="info-value">{{ $customer->email }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Phone:</span>
                <span class="info-value">{{ $customer->profile->phone ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Account Number:</span>
                <span class="info-value">{{ $customer->profile->account_number ?? 'N/A' }}</span>
            </div>
        </div>
        <div class="info-item">
            <span class="info-label">Current Account Balance:</span>
            <span class="info-value">${{ number_format($customer->account_balance, 2) }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Current Credit Balance:</span>
            <span class="info-value">${{ number_format($customer->credit_balance, 2) }}</span>
        </div>
    </div>

    <!-- Transaction Details -->
    <div class="info-section">
        <h3>💳 Transaction Details</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Transaction ID:</span>
                <span class="info-value">#{{ $transaction->id }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Date & Time:</span>
                <span class="info-value">{{ $transaction->created_at->format('M j, Y g:i A') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Type:</span>
                <span class="info-value">{{ ucfirst($transaction->type) }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Reference:</span>
                <span class="info-value">
                    @if($transaction->reference_type && $transaction->reference_id)
                        {{ $transaction->reference_type }}#{{ $transaction->reference_id }}
                    @else
                        N/A
                    @endif
                </span>
            </div>
        </div>
        <div class="info-item">
            <span class="info-label">Amount:</span>
            <span class="info-value transaction-amount {{ $transaction->isCredit() ? 'credit' : 'debit' }}">
                {{ $transaction->isCredit() ? '+' : '-' }}${{ number_format($transaction->amount, 2) }}
            </span>
        </div>
        <div class="info-item">
            <span class="info-label">Balance After:</span>
            <span class="info-value">${{ number_format($transaction->balance_after, 2) }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Description:</span>
            <span class="info-value">{{ $transaction->description }}</span>
        </div>
    </div>

    <!-- Package Information (if available) -->
    @if($package)
    <div class="info-section">
        <h3>📦 Package Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Tracking Number:</span>
                <span class="info-value">{{ $package->tracking_number }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Description:</span>
                <span class="info-value">{{ $package->description }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Weight:</span>
                <span class="info-value">{{ $package->weight }} lbs</span>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="info-value">{{ $package->status ? ucfirst($package->status) : 'N/A' }}</span>
            </div>
        </div>
        <div class="info-item">
            <span class="info-label">Total Cost:</span>
            <span class="info-value">${{ number_format($package->total_cost, 2) }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Breakdown:</span>
            <span class="info-value">
                Freight: ${{ number_format($package->freight_price, 2) }}, 
                Customs: ${{ number_format($package->customs_duty, 2) }}, 
                Storage: ${{ number_format($package->storage_fee, 2) }}, 
                Delivery: ${{ number_format($package->delivery_fee, 2) }}
            </span>
        </div>
    </div>
    @endif

    <!-- Manifest Information (if available) -->
    @if($manifest)
    <div class="info-section">
        <h3>📋 Manifest Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Manifest Number:</span>
                <span class="info-value">{{ $manifest->manifest_number }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Type:</span>
                <span class="info-value">{{ ucfirst($manifest->type) }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="info-value">{{ $manifest->status ? ucfirst($manifest->status) : 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Created:</span>
                <span class="info-value">{{ $manifest->created_at->format('M j, Y') }}</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Distribution Information (if available) -->
    @if($distribution)
    <div class="info-section">
        <h3>🚚 Distribution Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Receipt Number:</span>
                <span class="info-value">{{ $distribution->receipt_number }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Distribution Date:</span>
                <span class="info-value">{{ $distribution->distributed_at->format('M j, Y g:i A') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Total Amount:</span>
                <span class="info-value">${{ number_format($distribution->total_amount, 2) }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Amount Collected:</span>
                <span class="info-value">${{ number_format($distribution->amount_collected, 2) }}</span>
            </div>
        </div>
        @if($distribution->credit_applied > 0)
        <div class="info-item">
            <span class="info-label">Credit Applied:</span>
            <span class="info-value">${{ number_format($distribution->credit_applied, 2) }}</span>
        </div>
        @endif
        @if($distribution->write_off_amount > 0)
        <div class="info-item">
            <span class="info-label">Write-off Amount:</span>
            <span class="info-value">${{ number_format($distribution->write_off_amount, 2) }}</span>
        </div>
        @endif
        <div class="info-item">
            <span class="info-label">Payment Status:</span>
            <span class="info-value">{{ $distribution->payment_status ? ucfirst($distribution->payment_status) : 'N/A' }}</span>
        </div>
        @if($distribution->notes)
        <div class="info-item">
            <span class="info-label">Distribution Notes:</span>
            <span class="info-value">{{ $distribution->notes }}</span>
        </div>
        @endif
    </div>
    @endif

    <!-- Review Reason -->
    <div class="info-section">
        <h3>❓ Customer's Review Request</h3>
        <div class="info-item">
            <span class="info-label">Flagged On:</span>
            <span class="info-value">{{ $transaction->flagged_at->format('M j, Y g:i A') }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Reason for Review:</span>
        </div>
        <div class="review-reason">
            {{ $transaction->review_reason }}
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="{{ config('app.url') }}/admin/transactions" class="btn btn-primary">
            View in Admin Panel
        </a>
        <a href="mailto:{{ $customer->email }}?subject=Re: Transaction Review Request #{{ $transaction->id }}" class="btn btn-secondary">
            Contact Customer
        </a>
    </div>

    <div class="footer">
        <p><strong>Next Steps:</strong></p>
        <ol>
            <li>Review the transaction details and customer information above</li>
            <li>Investigate the customer's concern</li>
            <li>Access the admin panel to view full transaction history</li>
            <li>Contact the customer if additional information is needed</li>
            <li>Resolve the review request in the admin panel</li>
        </ol>
        
        <p><em>This email was automatically generated by the SHS Client Management System.</em></p>
        <p><em>Transaction flagged at: {{ $transaction->flagged_at->format('M j, Y g:i A T') }}</em></p>
    </div>
</body>
</html>