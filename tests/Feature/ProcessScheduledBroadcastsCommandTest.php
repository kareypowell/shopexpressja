<?php

namespace Tests\Feature;

use App\Models\BroadcastMessage;
use App\Models\BroadcastRecipient;
use App\Models\BroadcastDelivery;
use App\Models\User;
use App\Services\BroadcastMessageService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProcessScheduledBroadcastsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->admin = User::factory()->create([
            'role_id' => 2, // Admin role
            'email' => 'admin@test.com'
        ]);
        
        // Create test customers
        $this->customers = User::factory()->count(5)->create([
            'role_id' => 3, // Customer role
            'deleted_at' => null
        ]);
        
        Mail::fake();
    }

    /** @test */
    public function it_processes_due_scheduled_broadcasts()
    {
        // Create a scheduled broadcast that is due
        $dueBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Due Broadcast',
            'content' => 'This broadcast is due to be sent',
            'recipient_type' => 'all',
            'recipient_count' => $this->customers->count(),
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->subMinutes(5), // 5 minutes ago
        ]);

        // Create a scheduled broadcast that is not yet due
        $futureBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Future Broadcast',
            'content' => 'This broadcast is not yet due',
            'recipient_type' => 'all',
            'recipient_count' => $this->customers->count(),
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->addHours(1), // 1 hour from now
        ]);

        // Run the command
        $exitCode = Artisan::call('broadcast:process-scheduled');

        // Assert command succeeded
        $this->assertEquals(0, $exitCode);

        // Assert the due broadcast was processed
        $dueBroadcast->refresh();
        $this->assertEquals(BroadcastMessage::STATUS_SENT, $dueBroadcast->status);
        $this->assertNotNull($dueBroadcast->sent_at);

        // Assert the future broadcast was not processed
        $futureBroadcast->refresh();
        $this->assertEquals(BroadcastMessage::STATUS_SCHEDULED, $futureBroadcast->status);
        $this->assertNull($futureBroadcast->sent_at);

        // Assert delivery records were created for the due broadcast
        $deliveryCount = BroadcastDelivery::where('broadcast_message_id', $dueBroadcast->id)->count();
        $this->assertEquals($this->customers->count(), $deliveryCount);
    }

    /** @test */
    public function it_handles_selected_recipients_correctly()
    {
        // Select only 2 customers
        $selectedCustomers = $this->customers->take(2);
        
        $broadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Selected Recipients Broadcast',
            'content' => 'This broadcast goes to selected recipients',
            'recipient_type' => 'selected',
            'recipient_count' => $selectedCustomers->count(),
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->subMinutes(5),
        ]);

        // Create recipient records
        foreach ($selectedCustomers as $customer) {
            BroadcastRecipient::create([
                'broadcast_message_id' => $broadcast->id,
                'customer_id' => $customer->id,
            ]);
        }

        // Run the command
        $exitCode = Artisan::call('broadcast:process-scheduled');

        // Assert command succeeded
        $this->assertEquals(0, $exitCode);

        // Assert broadcast was processed
        $broadcast->refresh();
        $this->assertEquals(BroadcastMessage::STATUS_SENT, $broadcast->status);

        // Assert delivery records were created only for selected recipients
        $deliveryCount = BroadcastDelivery::where('broadcast_message_id', $broadcast->id)->count();
        $this->assertEquals($selectedCustomers->count(), $deliveryCount);

        // Assert delivery records are for the correct customers
        $deliveredCustomerIds = BroadcastDelivery::where('broadcast_message_id', $broadcast->id)
            ->pluck('customer_id')
            ->toArray();
        
        $expectedCustomerIds = $selectedCustomers->pluck('id')->toArray();
        $this->assertEquals(sort($expectedCustomerIds), sort($deliveredCustomerIds));
    }

    /** @test */
    public function it_handles_broadcast_processing_failures_gracefully()
    {
        // Create a broadcast with invalid data to trigger failure
        $broadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Failing Broadcast',
            'content' => 'This broadcast will fail',
            'recipient_type' => 'selected', // Selected but no recipients
            'recipient_count' => 0,
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->subMinutes(5),
        ]);

        // Mock the service to throw an exception
        $this->mock(BroadcastMessageService::class, function ($mock) {
            $mock->shouldReceive('processScheduledBroadcasts')
                ->once()
                ->andReturn([
                    'success' => true,
                    'processed_count' => 0,
                    'failed_count' => 1,
                    'errors' => ['Broadcast 1: No recipients found']
                ]);
        });

        // Run the command
        $exitCode = Artisan::call('broadcast:process-scheduled');

        // Assert command still succeeds even with failures
        $this->assertEquals(0, $exitCode);

        // Assert output contains failure information
        $output = Artisan::output();
        $this->assertStringContainsString('Failed: 1 broadcasts', $output);
        $this->assertStringContainsString('Errors encountered:', $output);
    }

    /** @test */
    public function it_supports_dry_run_mode()
    {
        // Create a scheduled broadcast that is due
        $broadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Dry Run Test',
            'content' => 'This is a dry run test',
            'recipient_type' => 'all',
            'recipient_count' => $this->customers->count(),
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->subMinutes(5),
        ]);

        // Run the command in dry-run mode
        $exitCode = Artisan::call('broadcast:process-scheduled', ['--dry-run' => true]);

        // Assert command succeeded
        $this->assertEquals(0, $exitCode);

        // Assert the broadcast was NOT actually processed
        $broadcast->refresh();
        $this->assertEquals(BroadcastMessage::STATUS_SCHEDULED, $broadcast->status);
        $this->assertNull($broadcast->sent_at);

        // Assert no delivery records were created
        $deliveryCount = BroadcastDelivery::where('broadcast_message_id', $broadcast->id)->count();
        $this->assertEquals(0, $deliveryCount);

        // Assert output indicates dry run
        $output = Artisan::output();
        $this->assertStringContainsString('DRY RUN MODE', $output);
        $this->assertStringContainsString('Found 1 scheduled broadcasts', $output);
        $this->assertStringContainsString('Would be sent', $output);
    }

    /** @test */
    public function it_respects_limit_option()
    {
        // Create multiple scheduled broadcasts
        $broadcasts = collect();
        for ($i = 0; $i < 5; $i++) {
            $broadcasts->push(BroadcastMessage::factory()->create([
                'sender_id' => $this->admin->id,
                'subject' => "Broadcast {$i}",
                'content' => "Content for broadcast {$i}",
                'recipient_type' => 'all',
                'recipient_count' => $this->customers->count(),
                'status' => BroadcastMessage::STATUS_SCHEDULED,
                'scheduled_at' => Carbon::now()->subMinutes(5),
            ]));
        }

        // Run the command with a limit of 2 in dry-run mode
        $exitCode = Artisan::call('broadcast:process-scheduled', [
            '--dry-run' => true,
            '--limit' => 2
        ]);

        // Assert command succeeded
        $this->assertEquals(0, $exitCode);

        // Assert output shows only 2 broadcasts were considered
        $output = Artisan::output();
        $this->assertStringContainsString('Found 2 scheduled broadcasts', $output);
    }

    /** @test */
    public function it_logs_processing_activities()
    {
        Log::spy();

        // Create a scheduled broadcast
        $broadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Logging Test',
            'content' => 'This tests logging',
            'recipient_type' => 'all',
            'recipient_count' => $this->customers->count(),
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->subMinutes(5),
        ]);

        // Run the command
        Artisan::call('broadcast:process-scheduled');

        // Assert logging occurred
        Log::shouldHaveReceived('info')
            ->with('Scheduled broadcast processing started', \Mockery::type('array'))
            ->once();

        Log::shouldHaveReceived('info')
            ->with('Scheduled broadcast processing completed', \Mockery::type('array'))
            ->once();
    }

    /** @test */
    public function it_handles_service_exceptions_gracefully()
    {
        Log::spy();

        // Mock the service to throw an exception
        $this->mock(BroadcastMessageService::class, function ($mock) {
            $mock->shouldReceive('processScheduledBroadcasts')
                ->once()
                ->andThrow(new \Exception('Service unavailable'));
        });

        // Run the command
        $exitCode = Artisan::call('broadcast:process-scheduled');

        // Assert command failed
        $this->assertEquals(1, $exitCode);

        // Assert error was logged
        Log::shouldHaveReceived('error')
            ->with('Scheduled broadcast processing command failed', \Mockery::type('array'))
            ->once();

        // Assert output contains error message
        $output = Artisan::output();
        $this->assertStringContainsString('Command failed with exception', $output);
        $this->assertStringContainsString('Service unavailable', $output);
    }

    /** @test */
    public function it_shows_execution_time_in_output()
    {
        // Create a scheduled broadcast
        BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Timing Test',
            'content' => 'This tests execution timing',
            'recipient_type' => 'all',
            'recipient_count' => $this->customers->count(),
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->subMinutes(5),
        ]);

        // Run the command
        $exitCode = Artisan::call('broadcast:process-scheduled');

        // Assert command succeeded
        $this->assertEquals(0, $exitCode);

        // Assert output contains execution time
        $output = Artisan::output();
        $this->assertStringContainsString('completed successfully in', $output);
        $this->assertStringContainsString('s', $output); // seconds indicator
    }

    /** @test */
    public function it_prevents_processing_non_scheduled_broadcasts()
    {
        // Create broadcasts in various states
        $draftBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_DRAFT,
            'scheduled_at' => Carbon::now()->subMinutes(5),
        ]);

        $sentBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_SENT,
            'scheduled_at' => Carbon::now()->subMinutes(5),
        ]);

        $sendingBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_SENDING,
            'scheduled_at' => Carbon::now()->subMinutes(5),
        ]);

        // Run the command
        $exitCode = Artisan::call('broadcast:process-scheduled');

        // Assert command succeeded
        $this->assertEquals(0, $exitCode);

        // Assert no broadcasts were processed (all wrong status)
        $output = Artisan::output();
        $this->assertStringContainsString('Processed: 0 broadcasts', $output);

        // Assert statuses remain unchanged
        $this->assertEquals(BroadcastMessage::STATUS_DRAFT, $draftBroadcast->fresh()->status);
        $this->assertEquals(BroadcastMessage::STATUS_SENT, $sentBroadcast->fresh()->status);
        $this->assertEquals(BroadcastMessage::STATUS_SENDING, $sendingBroadcast->fresh()->status);
    }
}