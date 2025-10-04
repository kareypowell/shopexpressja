<?php

namespace App\Services;

use App\Models\PackageDistribution;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Exception;

class ReceiptGeneratorService
{
    /**
     * Generate PDF receipt for package distribution
     *
     * @param PackageDistribution $distribution
     * @return string Path to generated PDF
     */
    public function generatePDF(PackageDistribution $distribution): string
    {
        try {
            // Load distribution with relationships
            $distribution->load(['customer', 'distributedBy', 'items.package']);

            // Format receipt data
            $receiptData = $this->formatReceiptData($distribution);

            // Calculate totals
            $totals = $this->calculateTotals($distribution);

            // Merge data
            $data = array_merge($receiptData, $totals);

            // Generate PDF
            $pdf = Pdf::loadView('receipts.package-distribution', $data);
            
            // Set PDF options for better layout
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
                'dpi' => 150,
                'defaultPaperSize' => 'a4',
                'chroot' => public_path(),
                'debugKeepTemp' => false,
                'debugCss' => false,
                'debugLayout' => false,
                'debugLayoutLines' => false,
                'debugLayoutBlocks' => false,
                'debugLayoutInline' => false,
                'debugLayoutPaddingBox' => false,
            ]);
            
            // Set margins to ensure content fits properly
            $pdf->setOption('margin-top', '12mm');
            $pdf->setOption('margin-right', '8mm');
            $pdf->setOption('margin-bottom', '12mm');
            $pdf->setOption('margin-left', '8mm');

            // Generate filename
            $filename = 'receipts/' . $distribution->receipt_number . '.pdf';
            
            // Save PDF to storage
            $pdfContent = $pdf->output();
            Storage::disk('public')->put($filename, $pdfContent);

            // Update distribution with receipt path
            $distribution->update(['receipt_path' => $filename]);

            return $filename;

        } catch (Exception $e) {
            throw new Exception('Failed to generate PDF receipt: ' . $e->getMessage());
        }
    }

    /**
     * Calculate totals for the receipt
     *
     * @param PackageDistribution $distribution
     * @return array
     */
    public function calculateTotals(PackageDistribution $distribution): array
    {
        $subtotal = 0;
        $totalFreight = 0;
        $totalClearance = 0;
        $totalStorage = 0;
        $totalDelivery = 0;

        foreach ($distribution->items as $item) {
            $subtotal += $item->total_cost;
            $totalFreight += $item->freight_price;
            $totalClearance += $item->clearance_fee;
            $totalStorage += $item->storage_fee;
            $totalDelivery += $item->delivery_fee;
        }

        $totalPaid = $distribution->amount_collected + $distribution->credit_applied + ($distribution->account_balance_applied ?? 0) + $distribution->write_off_amount;
        $outstandingBalance = max(0, $distribution->total_amount - $totalPaid);

        return [
            'subtotal' => number_format($subtotal, 2),
            'total_freight' => number_format($totalFreight, 2),
            'total_clearance' => number_format($totalClearance, 2),
            'total_storage' => number_format($totalStorage, 2),
            'total_delivery' => number_format($totalDelivery, 2),
            'total_amount' => number_format($distribution->total_amount, 2),
            'amount_collected' => number_format($distribution->amount_collected, 2),
            'credit_applied' => number_format($distribution->credit_applied, 2),
            'account_balance_applied' => number_format($distribution->account_balance_applied ?? 0, 2),
            'write_off_amount' => number_format($distribution->write_off_amount, 2),
            'write_off_reason' => $distribution->write_off_reason,
            'total_paid' => number_format($totalPaid, 2),
            'outstanding_balance' => number_format($outstandingBalance, 2),
            'payment_status' => ucfirst($distribution->payment_status),
        ];
    }

    /**
     * Format receipt data for PDF template
     *
     * @param PackageDistribution $distribution
     * @return array
     */
    public function formatReceiptData(PackageDistribution $distribution): array
    {
        return [
            'receipt_number' => $distribution->receipt_number,
            'distribution_date' => $distribution->distributed_at->format('F j, Y'),
            'distribution_time' => $distribution->distributed_at->format('g:i A'),
            'customer' => [
                'name' => $distribution->customer->full_name,
                'email' => $distribution->customer->email,
                'account_number' => $distribution->customer->profile->account_number ?? 'N/A',
            ],
            'distributed_by' => [
                'name' => $distribution->distributedBy->full_name,
                'role' => $distribution->distributedBy->role->name ?? 'Staff',
            ],
            'packages' => $distribution->items->map(function ($item) {
                $package = $item->package;
                $isSeaPackage = $package->isSeaPackage();
                
                return [
                    'tracking_number' => $package->tracking_number,
                    'description' => $package->description ?? 'Package',
                    'weight_display' => $isSeaPackage 
                        ? number_format($package->cubic_feet ?? 0, 2) . ' ft³'
                        : number_format($package->weight ?? 0, 1) . ' lbs',
                    'weight_label' => $isSeaPackage ? 'Cubic Feet' : 'Weight',
                    'is_sea_package' => $isSeaPackage,
                    'freight_price' => number_format($item->freight_price, 2),
                    'clearance_fee' => number_format($item->clearance_fee, 2),
                    'storage_fee' => number_format($item->storage_fee, 2),
                    'delivery_fee' => number_format($item->delivery_fee, 2),
                    'total_cost' => number_format($item->total_cost, 2),
                ];
            })->toArray(),
            'company' => [
                'name' => config('app.name', 'ShipShark Ltd'),
                'address' => 'Shop #24b Reliance Plaza, Mandeville, Manchester',
                'phone' => '876-237-1191',
                'email' => 'support@shopexpressja.com',
                'website' => 'www.shipsharkltd.com',
            ],
        ];
    }

    /**
     * Create receipt template view if it doesn't exist
     *
     * @return void
     */
    public function createReceiptTemplate(): void
    {
        $templatePath = resource_path('views/receipts');
        
        if (!file_exists($templatePath)) {
            mkdir($templatePath, 0755, true);
        }

        $templateFile = $templatePath . '/package-distribution.blade.php';
        
        if (!file_exists($templateFile)) {
            $template = $this->getReceiptTemplate();
            file_put_contents($templateFile, $template);
        }
    }

    /**
     * Get the receipt template content
     *
     * @return string
     */
    private function getReceiptTemplate(): string
    {
        return '<!DOCTYPE html>
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
            border-bottom: 2px solid #0a274c;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #0a274c;
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
            border-top: 2px solid #0a274c;
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
        <div class="company-name">{{ $company[\'name\'] }}</div>
        <div class="company-info">
            {{ $company[\'address\'] }}<br>
            Phone: {{ $company[\'phone\'] }} | Email: {{ $company[\'email\'] }}<br>
            Website: {{ $company[\'website\'] }}
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
                <div>{{ $distributed_by[\'name\'] }} ({{ $distributed_by[\'role\'] }})</div>
            </div>
        </div>
        <div class="receipt-info-right">
            <div class="info-section">
                <div class="info-label">Customer Information:</div>
                <div>{{ $customer[\'name\'] }}</div>
                <div>{{ $customer[\'email\'] }}</div>
                <div>Account: {{ $customer[\'account_number\'] }}</div>
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
                <th>Clearnace</th>
                <th>Storage</th>
                <th>Delivery</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($packages as $package)
            <tr>
                <td>{{ $package[\'tracking_number\'] }}</td>
                <td>{{ $package[\'description\'] }}</td>
                <td class="number-cell">{{ $package[\'weight\'] }} lbs</td>
                <td class="number-cell">${{ $package[\'freight_price\'] }}</td>
                <td class="number-cell">${{ $package[\'clearance_fee\'] }}</td>
                <td class="number-cell">${{ $package[\'storage_fee\'] }}</td>
                <td class="number-cell">${{ $package[\'delivery_fee\'] }}</td>
                <td class="number-cell">${{ $package[\'total_cost\'] }}</td>
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
                <td class="label">Cash Collected:</td>
                <td class="amount">${{ $amount_collected }}</td>
            </tr>
            @if($credit_applied > 0)
            <tr>
                <td class="label">Credit Applied:</td>
                <td class="amount">${{ $credit_applied }}</td>
            </tr>
            @endif
            @if($account_balance_applied > 0)
            <tr>
                <td class="label">Account Balance Applied:</td>
                <td class="amount">${{ $account_balance_applied }}</td>
            </tr>
            @endif
            @if($write_off_amount > 0)
            <tr>
                <td class="label">Discount/Write-off:</td>
                <td class="amount">-${{ $write_off_amount }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Total Paid:</td>
                <td class="amount">${{ $total_paid }}</td>
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
        <p>Thank you for choosing {{ $company[\'name\'] }}!</p>
        <p>This receipt was generated on {{ date(\'F j, Y \at g:i A\') }}</p>
        <p>For inquiries, please contact us at {{ $company[\'email\'] }} or {{ $company[\'phone\'] }}</p>
    </div>
</body>
</html>';
    }
}