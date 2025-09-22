<?php

namespace App\Services;

use App\Models\CustomerTransaction;
use App\Mail\TransactionReviewRequest;
use App\Services\AuditService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TransactionReviewService
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }
    /**
     * Flag a transaction for review and notify admin
     *
     * @param CustomerTransaction $transaction
     * @param string $reason
     * @return bool
     */
    public function flagTransactionForReview(CustomerTransaction $transaction, string $reason): bool
    {
        try {
            // Flag the transaction
            $transaction->flagForReview($reason);
            
            // Log to audit system
            $this->auditService->logFinancialTransaction('transaction_flagged_for_review', [
                'transaction_id' => $transaction->id,
                'customer_id' => $transaction->user_id,
                'amount' => $transaction->amount,
                'type' => $transaction->type,
                'reason' => $reason,
                'flagged_by' => auth()->user()->name ?? 'System',
            ]);
            
            // Send email notification to admin
            $this->notifyAdmin($transaction);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to flag transaction for review', [
                'transaction_id' => $transaction->id,
                'reason' => $reason,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Send email notification to admin
     *
     * @param CustomerTransaction $transaction
     * @return void
     */
    protected function notifyAdmin(CustomerTransaction $transaction): void
    {
        try {
            $adminEmail = env('ADMIN_EMAIL');
            
            if (!$adminEmail) {
                Log::warning('Admin email not configured for transaction review notifications');
                return;
            }

            Mail::to($adminEmail)->send(new TransactionReviewRequest($transaction));
            
            // Mark as notified
            $transaction->markAdminNotified();
            
            Log::info('Admin notified of transaction review request', [
                'transaction_id' => $transaction->id,
                'admin_email' => $adminEmail,
                'customer_id' => $transaction->user_id
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send admin notification for transaction review', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Resolve a transaction review
     *
     * @param CustomerTransaction $transaction
     * @param string $adminResponse
     * @param int $resolvedBy
     * @return bool
     */
    public function resolveTransactionReview(CustomerTransaction $transaction, string $adminResponse, int $resolvedBy): bool
    {
        try {
            $transaction->resolveReview($adminResponse, $resolvedBy);
            
            // Log to audit system
            $this->auditService->logFinancialTransaction('transaction_review_resolved', [
                'transaction_id' => $transaction->id,
                'customer_id' => $transaction->user_id,
                'amount' => $transaction->amount,
                'type' => $transaction->type,
                'admin_response' => $adminResponse,
                'resolved_by_id' => $resolvedBy,
                'resolved_by' => ($resolvedByUser = \App\Models\User::find($resolvedBy)) ? ($resolvedByUser->first_name . ' ' . $resolvedByUser->last_name) : 'Unknown',
            ]);
            
            Log::info('Transaction review resolved', [
                'transaction_id' => $transaction->id,
                'resolved_by' => $resolvedBy,
                'customer_id' => $transaction->user_id
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to resolve transaction review', [
                'transaction_id' => $transaction->id,
                'resolved_by' => $resolvedBy,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Get pending review transactions
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingReviews()
    {
        return CustomerTransaction::with(['user', 'createdBy'])
            ->where('flagged_for_review', true)
            ->where('review_resolved', false)
            ->orderBy('flagged_at', 'desc')
            ->get();
    }

    /**
     * Get review statistics
     *
     * @return array
     */
    public function getReviewStatistics(): array
    {
        return [
            'pending_reviews' => CustomerTransaction::where('flagged_for_review', true)
                ->where('review_resolved', false)
                ->count(),
            'resolved_reviews' => CustomerTransaction::where('flagged_for_review', true)
                ->where('review_resolved', true)
                ->count(),
            'total_flagged' => CustomerTransaction::where('flagged_for_review', true)
                ->count(),
        ];
    }
}