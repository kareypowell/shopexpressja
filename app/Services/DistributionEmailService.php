<?php

namespace App\Services;

use App\Mail\PackageReceiptEmail;
use App\Models\PackageDistribution;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Exception;

class DistributionEmailService
{
    /**
     * Send receipt email with PDF attachment
     *
     * @param PackageDistribution $distribution
     * @param User $customer
     * @return array
     */
    public function sendReceiptEmail(PackageDistribution $distribution, User $customer): array
    {
        try {
            Log::info('Attempting to send receipt email', [
                'distribution_id' => $distribution->id,
                'customer_id' => $customer->id,
                'customer_email' => $customer->email,
                'receipt_number' => $distribution->receipt_number
            ]);

            // Validate email address
            if (!filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address: {$customer->email}");
            }

            // Check if receipt file exists on public disk
            if (!Storage::disk('public')->exists($distribution->receipt_path)) {
                throw new Exception("Receipt file not found: {$distribution->receipt_path}");
            }

            // Send email
            Mail::to($customer->email)->queue(new PackageReceiptEmail($distribution, $customer));

            // Update distribution record
            $distribution->update([
                'email_sent' => true,
                'email_sent_at' => now()
            ]);

            Log::info('Receipt email queued successfully', [
                'distribution_id' => $distribution->id,
                'customer_email' => $customer->email
            ]);

            return [
                'success' => true,
                'message' => 'Receipt email queued for delivery',
                'distribution_id' => $distribution->id,
                'email' => $customer->email
            ];

        } catch (Exception $e) {
            Log::error('Failed to send receipt email', [
                'distribution_id' => $distribution->id,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send receipt email: ' . $e->getMessage(),
                'distribution_id' => $distribution->id,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Retry failed receipt email delivery
     *
     * @param string $distributionId
     * @return array
     */
    public function retryFailedReceipt(string $distributionId): array
    {
        try {
            $distribution = PackageDistribution::findOrFail($distributionId);
            $customer = $distribution->customer;

            Log::info('Retrying failed receipt email', [
                'distribution_id' => $distributionId,
                'customer_email' => $customer->email,
                'previous_attempt' => $distribution->email_sent_at
            ]);

            // Reset email status for retry
            $distribution->update([
                'email_sent' => false,
                'email_sent_at' => null
            ]);

            // Attempt to send email again
            return $this->sendReceiptEmail($distribution, $customer);

        } catch (Exception $e) {
            Log::error('Failed to retry receipt email', [
                'distribution_id' => $distributionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retry receipt email: ' . $e->getMessage(),
                'distribution_id' => $distributionId,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check receipt delivery status
     *
     * @param string $distributionId
     * @return array
     */
    public function checkReceiptDeliveryStatus(string $distributionId): array
    {
        try {
            $distribution = PackageDistribution::with('customer')->findOrFail($distributionId);

            $status = [
                'distribution_id' => $distributionId,
                'receipt_number' => $distribution->receipt_number,
                'customer_email' => $distribution->customer->email,
                'email_sent' => $distribution->email_sent,
                'email_sent_at' => $distribution->email_sent_at->format('Y-m-d H:i:s'),
                'distributed_at' => $distribution->distributed_at->format('Y-m-d H:i:s')
            ];

            // Determine delivery status
            if ($distribution->email_sent && $distribution->email_sent_at) {
                $status['delivery_status'] = 'sent';
                $status['message'] = 'Receipt email has been sent successfully';
            } elseif ($distribution->email_sent === false) {
                $status['delivery_status'] = 'failed';
                $status['message'] = 'Receipt email delivery failed';
            } else {
                $status['delivery_status'] = 'pending';
                $status['message'] = 'Receipt email is queued for delivery';
            }

            Log::info('Receipt delivery status checked', $status);

            return [
                'success' => true,
                'status' => $status
            ];

        } catch (Exception $e) {
            Log::error('Failed to check receipt delivery status', [
                'distribution_id' => $distributionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to check delivery status: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get failed receipt deliveries for monitoring
     *
     * @param int $limit
     * @return array
     */
    public function getFailedDeliveries(int $limit = 50): array
    {
        try {
            $failedDeliveries = PackageDistribution::with('customer')
                ->where('email_sent', false)
                ->whereNotNull('distributed_at')
                ->orderBy('distributed_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($distribution) {
                    return [
                        'distribution_id' => $distribution->id,
                        'receipt_number' => $distribution->receipt_number,
                        'customer_name' => $distribution->customer->name,
                        'customer_email' => $distribution->customer->email,
                        'distributed_at' => $distribution->distributed_at->format('Y-m-d H:i:s'),
                        'total_amount' => $distribution->total_amount,
                        'amount_collected' => $distribution->amount_collected
                    ];
                });

            return [
                'success' => true,
                'failed_deliveries' => $failedDeliveries->toArray(),
                'count' => $failedDeliveries->count()
            ];

        } catch (Exception $e) {
            Log::error('Failed to get failed deliveries', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve failed deliveries: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }
}