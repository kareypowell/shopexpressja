<?php

namespace App\Services;

use App\Models\User;
use App\Mail\WelcomeUser;
use App\Mail\CustomerWelcomeEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Exception;

class CustomerEmailService
{
    /**
     * Send welcome email to a newly created customer.
     *
     * @param User $customer
     * @param string|null $temporaryPassword
     * @param bool $queueEmail
     * @return array
     */
    public function sendWelcomeEmail(User $customer, ?string $temporaryPassword = null, bool $queueEmail = true): array
    {
        try {
            $deliveryId = $this->generateDeliveryId();
            
            $emailData = [
                'customer' => $customer,
                'temporaryPassword' => $temporaryPassword,
                'accountNumber' => $customer->profile->account_number ?? null,
                'queueEmail' => $queueEmail,
                'deliveryId' => $deliveryId,
            ];

            $welcomeEmail = new CustomerWelcomeEmail($emailData);

            if ($queueEmail) {
                Mail::to($customer->email)->queue($welcomeEmail);
                $status = 'queued';
                $message = 'Welcome email has been queued for delivery';
            } else {
                Mail::to($customer->email)->send($welcomeEmail);
                $status = 'sent';
                $message = 'Welcome email sent successfully';
            }

            Log::info('Customer welcome email processed', [
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'status' => $status,
                'queued' => $queueEmail,
                'delivery_id' => $deliveryId,
            ]);

            return [
                'success' => true,
                'status' => $status,
                'message' => $message,
                'delivery_id' => $deliveryId,
            ];

        } catch (Exception $e) {
            Log::error('Failed to send customer welcome email', [
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Failed to send welcome email: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send account update notification email.
     *
     * @param User $customer
     * @param array $updatedFields
     * @param bool $queueEmail
     * @return array
     */
    public function sendAccountUpdateNotification(User $customer, array $updatedFields = [], bool $queueEmail = true): array
    {
        try {
            // This can be extended for account update notifications
            Log::info('Account update notification requested', [
                'customer_id' => $customer->id,
                'updated_fields' => $updatedFields,
            ]);

            return [
                'success' => true,
                'status' => 'not_implemented',
                'message' => 'Account update notifications not yet implemented',
            ];

        } catch (Exception $e) {
            Log::error('Failed to send account update notification', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Failed to send account update notification',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retry failed email delivery.
     *
     * @param User $customer
     * @param string $emailType
     * @param array $emailData
     * @return array
     */
    public function retryFailedEmail(User $customer, string $emailType, array $emailData = []): array
    {
        try {
            $retryCount = $emailData['retry_count'] ?? 1;
            $previousDeliveryId = $emailData['previous_delivery_id'] ?? null;
            
            Log::info('Retrying email delivery', [
                'customer_id' => $customer->id,
                'email_type' => $emailType,
                'retry_count' => $retryCount,
                'previous_delivery_id' => $previousDeliveryId,
            ]);

            switch ($emailType) {
                case 'welcome':
                    $result = $this->sendWelcomeEmail(
                        $customer, 
                        $emailData['temporaryPassword'] ?? null, 
                        $emailData['queue'] ?? true
                    );
                    
                    // Add retry information to result
                    $result['retry_count'] = $retryCount;
                    $result['previous_delivery_id'] = $previousDeliveryId;
                    
                    return $result;
                
                case 'account_update':
                    return $this->sendAccountUpdateNotification(
                        $customer, 
                        $emailData['updated_fields'] ?? [], 
                        $emailData['queue'] ?? true
                    );
                
                default:
                    throw new Exception("Unknown email type: {$emailType}");
            }

        } catch (Exception $e) {
            Log::error('Failed to retry email delivery', [
                'customer_id' => $customer->id,
                'email_type' => $emailType,
                'retry_count' => $emailData['retry_count'] ?? 1,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'retry_failed',
                'message' => 'Failed to retry email delivery',
                'error' => $e->getMessage(),
                'retry_count' => $emailData['retry_count'] ?? 1,
            ];
        }
    }

    /**
     * Check if email delivery is properly configured.
     *
     * @return array
     */
    public function checkEmailConfiguration(): array
    {
        try {
            $mailConfig = config('mail');
            $queueConfig = config('queue');

            $mailDefault = $mailConfig['default'] ?? null;
            $queueDefault = $queueConfig['default'] ?? null;

            $checks = [
                'mail_driver' => !empty($mailDefault),
                'mail_host' => !empty($mailConfig['mailers'][$mailDefault]['host'] ?? null),
                'queue_driver' => !empty($queueDefault),
                'queue_connection' => $queueDefault !== 'sync',
            ];

            $allPassed = array_reduce($checks, function ($carry, $check) {
                return $carry && $check;
            }, true);

            return [
                'configured' => $allPassed,
                'checks' => $checks,
                'recommendations' => $this->getConfigurationRecommendations($checks),
            ];

        } catch (Exception $e) {
            return [
                'configured' => false,
                'error' => $e->getMessage(),
                'checks' => [],
                'recommendations' => ['Fix configuration errors before sending emails'],
            ];
        }
    }

    /**
     * Check email delivery status.
     *
     * @param string $deliveryId
     * @return array
     */
    public function checkDeliveryStatus(string $deliveryId): array
    {
        try {
            // Check if email is still in queue
            $queuedJob = \DB::table('jobs')
                ->where('payload', 'like', "%{$deliveryId}%")
                ->first();
                
            if ($queuedJob) {
                return [
                    'found' => true,
                    'status' => 'queued',
                    'message' => 'Email is still queued for delivery',
                    'attempts' => $queuedJob->attempts ?? 0,
                ];
            }
            
            // Check if email failed
            $failedJob = \DB::table('failed_jobs')
                ->where('payload', 'like', "%{$deliveryId}%")
                ->first();
                
            if ($failedJob) {
                return [
                    'found' => true,
                    'status' => 'failed',
                    'message' => 'Email delivery failed',
                    'failed_at' => $failedJob->failed_at,
                    'exception' => substr($failedJob->exception, 0, 200) . '...',
                ];
            }
            
            // If not in queue or failed jobs, assume it was processed successfully
            return [
                'found' => true,
                'status' => 'processed',
                'message' => 'Email has been processed (likely delivered successfully)',
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to check email delivery status', [
                'delivery_id' => $deliveryId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'found' => false,
                'status' => 'unknown',
                'message' => 'Unable to check delivery status',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate a unique delivery ID for tracking.
     *
     * @return string
     */
    private function generateDeliveryId(): string
    {
        return 'email_' . uniqid() . '_' . time();
    }

    /**
     * Get configuration recommendations based on checks.
     *
     * @param array $checks
     * @return array
     */
    private function getConfigurationRecommendations(array $checks): array
    {
        $recommendations = [];

        if (!$checks['mail_driver']) {
            $recommendations[] = 'Configure mail driver in config/mail.php';
        }

        if (!$checks['mail_host']) {
            $recommendations[] = 'Set mail host configuration';
        }

        if (!$checks['queue_driver']) {
            $recommendations[] = 'Configure queue driver in config/queue.php';
        }

        if (!$checks['queue_connection']) {
            $recommendations[] = 'Use database or redis queue driver for better performance';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Email configuration looks good';
        }

        return $recommendations;
    }
}