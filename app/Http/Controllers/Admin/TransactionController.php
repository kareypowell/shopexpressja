<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerTransaction;
use App\Models\PackageDistribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class TransactionController extends Controller
{
    /**
     * View receipt PDF for a payment transaction
     *
     * @param CustomerTransaction $transaction
     * @return Response
     */
    public function viewReceipt(CustomerTransaction $transaction)
    {
        // Check if user has permission to view transactions
        $this->authorize('viewAny', CustomerTransaction::class);

        // Validate that this is a payment transaction
        if ($transaction->type !== 'payment') {
            abort(404, 'Receipt not available for this transaction type.');
        }

        // Check if this is a package distribution payment
        if ($transaction->reference_type !== 'package_distribution') {
            abort(404, 'Receipt not available for this transaction type.');
        }

        // Find the related package distribution
        $distribution = PackageDistribution::find($transaction->reference_id);
        
        if (!$distribution) {
            abort(404, 'Related package distribution not found.');
        }

        // Check if receipt exists
        if (!$distribution->receipt_path) {
            abort(404, 'No receipt available for this transaction.');
        }

        // Check if receipt file exists on disk
        if (!Storage::disk('public')->exists($distribution->receipt_path)) {
            abort(404, 'Receipt PDF file not found.');
        }

        // Get the full path to the file
        $filePath = storage_path('app/public/' . $distribution->receipt_path);
        
        // Return the PDF file for viewing in browser
        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($distribution->receipt_path) . '"'
        ]);
    }
}