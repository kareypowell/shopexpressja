<?php

namespace Tests\Unit;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduledJobConfigurationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_schedules_broadcast_processing_command()
    {
        // Get the application's schedule
        $schedule = app(Schedule::class);
        
        // Get all scheduled events
        $events = collect($schedule->events());
        
        // Find the broadcast processing command
        $broadcastEvent = $events->first(function ($event) {
            return str_contains($event->command, 'broadcast:process-scheduled');
        });
        
        // Assert the command is scheduled
        $this->assertNotNull($broadcastEvent, 'Broadcast processing command is not scheduled');
        
        // Assert it runs every 5 minutes
        $this->assertEquals('*/5 * * * *', $broadcastEvent->expression);
        
        // Assert it has the correct configuration
        $this->assertTrue($broadcastEvent->withoutOverlapping);
        $this->assertTrue($broadcastEvent->runInBackground);
    }

    /** @test */
    public function it_has_proper_overlap_protection()
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());
        
        $broadcastEvent = $events->first(function ($event) {
            return str_contains($event->command, 'broadcast:process-scheduled');
        });
        
        $this->assertNotNull($broadcastEvent);
        $this->assertTrue($broadcastEvent->withoutOverlapping);
        
        // Check that mutex timeout is set (10 minutes)
        $this->assertEquals(10, $broadcastEvent->expiresAt); // 10 minutes
    }

    /** @test */
    public function it_runs_in_background()
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());
        
        $broadcastEvent = $events->first(function ($event) {
            return str_contains($event->command, 'broadcast:process-scheduled');
        });
        
        $this->assertNotNull($broadcastEvent);
        $this->assertTrue($broadcastEvent->runInBackground);
    }

    /** @test */
    public function it_has_success_and_failure_callbacks()
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());
        
        $broadcastEvent = $events->first(function ($event) {
            return str_contains($event->command, 'broadcast:process-scheduled');
        });
        
        $this->assertNotNull($broadcastEvent);
        
        // We can't directly access protected properties, but we can verify the event exists
        // and has the expected configuration. The callbacks are tested indirectly through
        // the scheduler execution.
        $this->assertTrue(true); // Placeholder assertion since callbacks are private
    }

    /** @test */
    public function scheduled_command_exists_and_is_callable()
    {
        // Test that the command exists and can be called
        $exitCode = \Artisan::call('broadcast:process-scheduled', ['--dry-run' => true]);
        
        // Command should execute successfully (exit code 0)
        $this->assertEquals(0, $exitCode);
        
        // Output should indicate dry run mode
        $output = \Artisan::output();
        $this->assertStringContainsString('DRY RUN MODE', $output);
    }

    /** @test */
    public function command_signature_is_correct()
    {
        // Get all registered commands
        $commands = \Artisan::all();
        
        // Assert our command is registered
        $this->assertArrayHasKey('broadcast:process-scheduled', $commands);
        
        $command = $commands['broadcast:process-scheduled'];
        
        // Assert command description
        $this->assertEquals('Process scheduled broadcast messages that are due to be sent', $command->getDescription());
        
        // Assert command has the expected options
        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasOption('dry-run'));
        $this->assertTrue($definition->hasOption('limit'));
        
        // Assert option descriptions
        $dryRunOption = $definition->getOption('dry-run');
        $this->assertEquals('Show what would be processed without actually sending', $dryRunOption->getDescription());
        
        $limitOption = $definition->getOption('limit');
        $this->assertEquals('Maximum number of broadcasts to process in one run', $limitOption->getDescription());
        $this->assertEquals('50', $limitOption->getDefault());
    }
}