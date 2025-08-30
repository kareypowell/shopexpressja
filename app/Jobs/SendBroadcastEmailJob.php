<?php

namespace App\Jobs;

use App\Models\BroadcastDelivery;
use App\Mail\CustomerBroadcastEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;

class SendBroadcastEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $broadcastDelivery;
    public $tries = 3;
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(BroadcastDelivery $broadcastDelivery)
    {
        $this->broadcastDelivery = $broadcastDelivery;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info('Sending broadcast email', [
                'broadcast_delivery_id' => $this->broadcastDelivery->id,
                'broadcast_message_id' => $this->broadcastDelivery->broadcast_message_id,
                'customer_id' => $this->broadcastDelivery->customer_id,
                'email' => $this->broadcastDelivery->email
            ]);

            // Load relationships if not already loaded
            $this->broadcastDelivery->load(['broadcastMessage', 'customer']);

            // Send the email
            Mail::to($this->broadcastDelivery->email)
                ->send(new CustomerBroadcastEmail(
                    $this->broadcastDelivery->broadcastMessage,
                    $this->broadcastDelivery->customer
                ));

            // Mark delivery as sent
            $this->broadcastDelivery->markAsSent();

            Log::info('Broadcast email sent successfully', [
                'broadcast_delivery_id' => $this->broadcastDelivery->id,
                'email' => $this->broadcastDelivery->email
            ]);

        } catch (Exception $e) {
            Log::warning('Failed to send broadcast email', [
                'broadcast_delivery_id' => $this->broadcastDelivery->id,
                'email' => $this->broadcastDelivery->email,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            // Mark delivery as failed with error message
            $this->broadcastDelivery->markAsFailed($e->getMessage());

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception)
    {
        Log::error('SendBroadcastEmailJob failed permanently', [
            'broadcast_delivery_id' => $this->broadcastDelivery->id,
            'email' => $this->broadcastDelivery->email,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Ensure delivery is marked as failed
        $this->broadcastDelivery->markAsFailed($exception->getMessage());
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff()
    {
        // Exponential backoff: 30 seconds, 2 minutes, 8 minutes
        return [30, 120, 480];
    }
}