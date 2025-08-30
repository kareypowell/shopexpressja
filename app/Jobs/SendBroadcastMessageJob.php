<?php

namespace App\Jobs;

use App\Models\BroadcastMessage;
use App\Models\BroadcastDelivery;
use App\Models\User;
use App\Services\BroadcastMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class SendBroadcastMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $broadcastMessage;
    public $tries = 3;
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(BroadcastMessage $broadcastMessage)
    {
        $this->broadcastMessage = $broadcastMessage;
    }

    /**
     * Execute the job.
     */
    public function handle(BroadcastMessageService $broadcastService)
    {
        try {
            Log::info('Starting broadcast message processing', [
                'broadcast_message_id' => $this->broadcastMessage->id,
                'subject' => $this->broadcastMessage->subject,
                'recipient_type' => $this->broadcastMessage->recipient_type
            ]);

            // Mark broadcast as sending
            $this->broadcastMessage->markAsSending();

            // Get recipients based on broadcast type
            $recipients = $broadcastService->getRecipients($this->broadcastMessage);

            if ($recipients->isEmpty()) {
                throw new Exception('No recipients found for broadcast message');
            }

            // Create delivery records for tracking
            $broadcastService->createDeliveryRecords($this->broadcastMessage, $recipients);

            // Dispatch individual email jobs
            foreach ($recipients as $recipient) {
                $delivery = BroadcastDelivery::where('broadcast_message_id', $this->broadcastMessage->id)
                    ->where('customer_id', $recipient->id)
                    ->first();

                if ($delivery) {
                    SendBroadcastEmailJob::dispatch($delivery)
                        ->onQueue('broadcast-emails');
                }
            }

            // Update broadcast status to sent
            $this->broadcastMessage->markAsSent();

            Log::info('Broadcast message processing completed successfully', [
                'broadcast_message_id' => $this->broadcastMessage->id,
                'recipient_count' => $recipients->count()
            ]);

        } catch (Exception $e) {
            Log::error('Failed to process broadcast message', [
                'broadcast_message_id' => $this->broadcastMessage->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Mark broadcast as failed
            $this->broadcastMessage->markAsFailed();

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception)
    {
        Log::error('SendBroadcastMessageJob failed permanently', [
            'broadcast_message_id' => $this->broadcastMessage->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Mark broadcast as failed
        $this->broadcastMessage->markAsFailed();
    }
}