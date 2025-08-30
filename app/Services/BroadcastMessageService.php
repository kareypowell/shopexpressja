<?php

namespace App\Services;

use App\Models\BroadcastMessage;
use App\Models\BroadcastRecipient;
use App\Models\BroadcastDelivery;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class BroadcastMessageService
{
    /**
     * Create a new broadcast message
     *
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function createBroadcast(array $data): array
    {
        $validator = $this->validateBroadcastData($data);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $broadcastMessage = BroadcastMessage::create([
                'subject' => $data['subject'],
                'content' => $data['content'],
                'sender_id' => Auth::id(),
                'recipient_type' => $data['recipient_type'],
                'recipient_count' => $this->calculateRecipientCount($data),
                'status' => BroadcastMessage::STATUS_DRAFT,
            ]);

            // Handle selected recipients
            if ($data['recipient_type'] === BroadcastMessage::RECIPIENT_TYPE_SELECTED) {
                $this->attachSelectedRecipients($broadcastMessage, $data['selected_customers'] ?? []);
            }

            return [
                'success' => true,
                'message' => 'Broadcast message created successfully',
                'broadcast_message' => $broadcastMessage->fresh(['recipients', 'sender'])
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to create broadcast message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to create broadcast message: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Save broadcast message as draft
     *
     * @param array $data
     * @param int|null $broadcastId
     * @return array
     * @throws ValidationException
     */
    public function saveDraft(array $data, ?int $broadcastId = null): array
    {
        $validator = $this->validateDraftData($data);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            if ($broadcastId) {
                $broadcastMessage = BroadcastMessage::findOrFail($broadcastId);
                
                // Only allow editing drafts
                if ($broadcastMessage->status !== BroadcastMessage::STATUS_DRAFT) {
                    return [
                        'success' => false,
                        'message' => 'Only draft messages can be edited'
                    ];
                }
                
                $broadcastMessage->update([
                    'subject' => $data['subject'] ?? $broadcastMessage->subject,
                    'content' => $data['content'] ?? $broadcastMessage->content,
                    'recipient_type' => $data['recipient_type'] ?? $broadcastMessage->recipient_type,
                    'recipient_count' => $this->calculateRecipientCount($data, $broadcastMessage),
                ]);
                
                // Update selected recipients if provided
                if (isset($data['selected_customers']) && $data['recipient_type'] === BroadcastMessage::RECIPIENT_TYPE_SELECTED) {
                    $broadcastMessage->recipients()->delete();
                    $this->attachSelectedRecipients($broadcastMessage, $data['selected_customers']);
                }
            } else {
                $broadcastMessage = BroadcastMessage::create([
                    'subject' => $data['subject'] ?? '',
                    'content' => $data['content'] ?? '',
                    'sender_id' => Auth::id(),
                    'recipient_type' => $data['recipient_type'] ?? BroadcastMessage::RECIPIENT_TYPE_ALL,
                    'recipient_count' => $this->calculateRecipientCount($data),
                    'status' => BroadcastMessage::STATUS_DRAFT,
                ]);

                if (isset($data['selected_customers']) && $data['recipient_type'] === BroadcastMessage::RECIPIENT_TYPE_SELECTED) {
                    $this->attachSelectedRecipients($broadcastMessage, $data['selected_customers']);
                }
            }

            return [
                'success' => true,
                'message' => 'Draft saved successfully',
                'broadcast_message' => $broadcastMessage->fresh(['recipients', 'sender'])
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to save draft: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get recipients for a broadcast message
     *
     * @param BroadcastMessage $broadcastMessage
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecipients(BroadcastMessage $broadcastMessage)
    {
        if ($broadcastMessage->recipient_type === BroadcastMessage::RECIPIENT_TYPE_ALL) {
            return User::activeCustomers()->get();
        }

        return $broadcastMessage->recipients()
            ->with('customer')
            ->get()
            ->pluck('customer')
            ->filter(); // Remove any null customers
    }

    /**
     * Create delivery records for a broadcast message
     *
     * @param BroadcastMessage $broadcastMessage
     * @param \Illuminate\Database\Eloquent\Collection $recipients
     * @return array
     */
    public function createDeliveryRecords(BroadcastMessage $broadcastMessage, $recipients): array
    {
        try {
            $deliveryRecords = [];
            
            foreach ($recipients as $customer) {
                $deliveryRecords[] = [
                    'broadcast_message_id' => $broadcastMessage->id,
                    'customer_id' => $customer->id,
                    'email' => $customer->email,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            BroadcastDelivery::insert($deliveryRecords);

            return [
                'success' => true,
                'message' => 'Delivery records created successfully',
                'count' => count($deliveryRecords)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create delivery records: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate broadcast data
     *
     * @param array $data
     * @return \Illuminate\Validation\Validator
     */
    protected function validateBroadcastData(array $data)
    {
        return Validator::make($data, [
            'subject' => 'required|string|max:255',
            'content' => 'required|string|min:10',
            'recipient_type' => 'required|in:all,selected',
            'selected_customers' => 'required_if:recipient_type,selected|array|min:1',
            'selected_customers.*' => 'exists:users,id',
        ]);
    }

    /**
     * Validate draft data (more lenient validation)
     *
     * @param array $data
     * @return \Illuminate\Validation\Validator
     */
    protected function validateDraftData(array $data)
    {
        return Validator::make($data, [
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'recipient_type' => 'nullable|in:all,selected',
            'selected_customers' => 'nullable|array',
            'selected_customers.*' => 'exists:users,id',
        ]);
    }

    /**
     * Calculate recipient count based on data
     *
     * @param array $data
     * @param BroadcastMessage|null $existingMessage
     * @return int
     */
    protected function calculateRecipientCount(array $data, ?BroadcastMessage $existingMessage = null): int
    {
        $recipientType = $data['recipient_type'] ?? $existingMessage?->recipient_type ?? BroadcastMessage::RECIPIENT_TYPE_ALL;
        
        if ($recipientType === BroadcastMessage::RECIPIENT_TYPE_ALL) {
            return User::activeCustomers()->count();
        }

        if (isset($data['selected_customers'])) {
            return count($data['selected_customers']);
        }

        return $existingMessage?->recipient_count ?? 0;
    }

    /**
     * Schedule a broadcast message for future sending
     *
     * @param int $broadcastId
     * @param Carbon|string $scheduledAt
     * @return array
     */
    public function scheduleBroadcast(int $broadcastId, $scheduledAt): array
    {
        try {
            $broadcastMessage = BroadcastMessage::findOrFail($broadcastId);
            
            // Only allow scheduling of draft messages
            if ($broadcastMessage->status !== BroadcastMessage::STATUS_DRAFT) {
                return [
                    'success' => false,
                    'message' => 'Only draft messages can be scheduled'
                ];
            }
            
            $scheduledDateTime = $scheduledAt instanceof Carbon ? $scheduledAt : Carbon::parse($scheduledAt);
            
            // Validate that scheduled time is in the future
            if ($scheduledDateTime->isPast()) {
                return [
                    'success' => false,
                    'message' => 'Scheduled time must be in the future'
                ];
            }
            
            // Validate that scheduled time is not too far in the future (e.g., 1 year)
            if ($scheduledDateTime->isAfter(Carbon::now()->addYear())) {
                return [
                    'success' => false,
                    'message' => 'Scheduled time cannot be more than 1 year in the future'
                ];
            }
            
            $broadcastMessage->update([
                'status' => BroadcastMessage::STATUS_SCHEDULED,
                'scheduled_at' => $scheduledDateTime,
            ]);
            
            return [
                'success' => true,
                'message' => 'Broadcast message scheduled successfully',
                'broadcast_message' => $broadcastMessage->fresh(),
                'scheduled_at' => $scheduledDateTime->toDateTimeString()
            ];
            
        } catch (\Exception $e) {
            \Log::error('Failed to schedule broadcast message', [
                'broadcast_id' => $broadcastId,
                'scheduled_at' => $scheduledAt,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to schedule broadcast message: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Process scheduled broadcasts that are due to be sent
     *
     * @return array
     */
    public function processScheduledBroadcasts(): array
    {
        try {
            $dueBroadcasts = BroadcastMessage::where('status', BroadcastMessage::STATUS_SCHEDULED)
                ->where('scheduled_at', '<=', Carbon::now())
                ->get();
                
            $processedCount = 0;
            $failedCount = 0;
            $errors = [];
            
            foreach ($dueBroadcasts as $broadcast) {
                try {
                    // Update status to sending to prevent duplicate processing
                    $broadcast->update(['status' => BroadcastMessage::STATUS_SENDING]);
                    
                    // Get recipients and create delivery records
                    $recipients = $this->getRecipients($broadcast);
                    $deliveryResult = $this->createDeliveryRecords($broadcast, $recipients);
                    
                    if (!$deliveryResult['success']) {
                        throw new \Exception($deliveryResult['message']);
                    }
                    
                    // Here we would typically dispatch a job to send the emails
                    // For now, we'll just mark it as ready to send
                    $broadcast->update([
                        'status' => BroadcastMessage::STATUS_SENDING,
                        'sent_at' => Carbon::now()
                    ]);
                    
                    $processedCount++;
                    
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Broadcast {$broadcast->id}: " . $e->getMessage();
                    
                    // Mark as failed
                    $broadcast->update(['status' => BroadcastMessage::STATUS_FAILED]);
                    
                    \Log::error('Failed to process scheduled broadcast', [
                        'broadcast_id' => $broadcast->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            return [
                'success' => true,
                'message' => "Processed {$processedCount} broadcasts, {$failedCount} failed",
                'processed_count' => $processedCount,
                'failed_count' => $failedCount,
                'errors' => $errors
            ];
            
        } catch (\Exception $e) {
            \Log::error('Failed to process scheduled broadcasts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to process scheduled broadcasts: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Cancel a scheduled broadcast
     *
     * @param int $broadcastId
     * @return array
     */
    public function cancelScheduledBroadcast(int $broadcastId): array
    {
        try {
            $broadcastMessage = BroadcastMessage::findOrFail($broadcastId);
            
            // Only allow canceling scheduled messages
            if ($broadcastMessage->status !== BroadcastMessage::STATUS_SCHEDULED) {
                return [
                    'success' => false,
                    'message' => 'Only scheduled messages can be canceled'
                ];
            }
            
            $broadcastMessage->update([
                'status' => BroadcastMessage::STATUS_DRAFT,
                'scheduled_at' => null,
            ]);
            
            return [
                'success' => true,
                'message' => 'Scheduled broadcast canceled successfully',
                'broadcast_message' => $broadcastMessage->fresh()
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to cancel scheduled broadcast: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send a broadcast message immediately
     *
     * @param int $broadcastId
     * @return array
     */
    public function sendBroadcast(int $broadcastId): array
    {
        try {
            $broadcastMessage = BroadcastMessage::findOrFail($broadcastId);
            
            // Only allow sending draft or scheduled messages
            if (!in_array($broadcastMessage->status, [BroadcastMessage::STATUS_DRAFT, BroadcastMessage::STATUS_SCHEDULED])) {
                return [
                    'success' => false,
                    'message' => 'Only draft or scheduled messages can be sent'
                ];
            }
            
            // Update status to sending
            $broadcastMessage->update([
                'status' => BroadcastMessage::STATUS_SENDING,
                'sent_at' => Carbon::now()
            ]);
            
            // Get recipients and create delivery records
            $recipients = $this->getRecipients($broadcastMessage);
            $deliveryResult = $this->createDeliveryRecords($broadcastMessage, $recipients);
            
            if (!$deliveryResult['success']) {
                $broadcastMessage->update(['status' => BroadcastMessage::STATUS_FAILED]);
                return $deliveryResult;
            }
            
            // Here we would typically dispatch jobs to send the emails
            // For now, we'll just mark it as sent
            $broadcastMessage->update(['status' => BroadcastMessage::STATUS_SENT]);
            
            return [
                'success' => true,
                'message' => 'Broadcast message sent successfully',
                'broadcast_message' => $broadcastMessage->fresh(),
                'recipient_count' => $recipients->count()
            ];
            
        } catch (\Exception $e) {
            \Log::error('Failed to send broadcast message', [
                'broadcast_id' => $broadcastId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to send broadcast message: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Attach selected recipients to broadcast message
     *
     * @param BroadcastMessage $broadcastMessage
     * @param array $customerIds
     * @return void
     */
    protected function attachSelectedRecipients(BroadcastMessage $broadcastMessage, array $customerIds): void
    {
        $recipients = [];
        
        foreach ($customerIds as $customerId) {
            $recipients[] = [
                'broadcast_message_id' => $broadcastMessage->id,
                'customer_id' => $customerId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        BroadcastRecipient::insert($recipients);
    }
}