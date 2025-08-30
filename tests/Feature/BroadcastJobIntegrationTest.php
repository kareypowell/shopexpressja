<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Jobs\SendBroadcastMessageJob;
use App\Jobs\SendBroadcastEmailJob;
use App\Models\BroadcastMessage;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class BroadcastJobIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_broadcast_message_job_can_be_dispatched()
    {
        Queue::fake();

        // Clear existing data
        \DB::table('users')->delete();
        \DB::table('roles')->delete();

        // Create roles and users
        $customerRole = Role::factory()->create(['name' => 'customer']);
        $adminRole = Role::factory()->create(['name' => 'admin']);
        
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        User::factory()->count(2)->create(['role_id' => $customerRole->id]);

        // Create broadcast message
        $broadcastMessage = BroadcastMessage::factory()->create([
            'sender_id' => $admin->id,
            'recipient_type' => BroadcastMessage::RECIPIENT_TYPE_ALL,
            'status' => BroadcastMessage::STATUS_DRAFT
        ]);

        // Dispatch the job
        SendBroadcastMessageJob::dispatch($broadcastMessage);

        // Assert job was queued
        Queue::assertPushed(SendBroadcastMessageJob::class, function ($job) use ($broadcastMessage) {
            return $job->broadcastMessage->id === $broadcastMessage->id;
        });
    }

    public function test_broadcast_email_job_can_be_dispatched()
    {
        Queue::fake();

        // Clear existing data
        \DB::table('users')->delete();
        \DB::table('roles')->delete();

        // Create roles and users
        $customerRole = Role::factory()->create(['name' => 'customer']);
        $adminRole = Role::factory()->create(['name' => 'admin']);
        
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $customer = User::factory()->create(['role_id' => $customerRole->id]);

        // Create broadcast message and delivery
        $broadcastMessage = BroadcastMessage::factory()->create([
            'sender_id' => $admin->id,
        ]);

        $delivery = $broadcastMessage->deliveries()->create([
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'status' => 'pending'
        ]);

        // Dispatch the job
        SendBroadcastEmailJob::dispatch($delivery);

        // Assert job was queued
        Queue::assertPushed(SendBroadcastEmailJob::class, function ($job) use ($delivery) {
            return $job->broadcastDelivery->id === $delivery->id;
        });
    }
}