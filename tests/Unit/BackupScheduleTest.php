<?php

namespace Tests\Unit;

use App\Models\BackupSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupScheduleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_calculate_next_run_for_daily_frequency()
    {
        $schedule = BackupSchedule::factory()->create([
            'frequency' => 'daily',
            'time' => '14:30:00',
        ]);

        Carbon::setTestNow('2023-01-15 10:00:00');
        
        $schedule->calculateNextRun();

        // Should be today at 14:30 since the time hasn't passed yet
        $expected = Carbon::parse('2023-01-15 14:30:00');
        $this->assertEquals($expected, $schedule->next_run_at);
    }

    /** @test */
    public function it_can_calculate_next_run_for_weekly_frequency()
    {
        $schedule = BackupSchedule::factory()->create([
            'frequency' => 'weekly',
            'time' => '02:00:00',
        ]);

        Carbon::setTestNow('2023-01-15 10:00:00'); // Sunday
        
        $schedule->calculateNextRun();

        // Should be next week at 02:00 since today's 02:00 has passed
        $expected = Carbon::parse('2023-01-22 02:00:00');
        $this->assertEquals($expected, $schedule->next_run_at);
    }

    /** @test */
    public function it_can_calculate_next_run_for_monthly_frequency()
    {
        $schedule = BackupSchedule::factory()->create([
            'frequency' => 'monthly',
            'time' => '03:00:00',
        ]);

        Carbon::setTestNow('2023-01-15 10:00:00');
        
        $schedule->calculateNextRun();

        // Should be next month at 03:00 since today's 03:00 has passed
        $expected = Carbon::parse('2023-02-15 03:00:00');
        $this->assertEquals($expected, $schedule->next_run_at);
    }

    /** @test */
    public function it_handles_past_time_correctly_for_daily_frequency()
    {
        $schedule = BackupSchedule::factory()->create([
            'frequency' => 'daily',
            'time' => '08:00:00',
        ]);

        Carbon::setTestNow('2023-01-15 10:00:00'); // After 08:00
        
        $schedule->calculateNextRun();

        // Should be tomorrow at 08:00 since today's time has passed
        $expected = Carbon::parse('2023-01-16 08:00:00');
        $this->assertEquals($expected, $schedule->next_run_at);
    }

    /** @test */
    public function it_handles_future_time_correctly_for_daily_frequency()
    {
        $schedule = BackupSchedule::factory()->create([
            'frequency' => 'daily',
            'time' => '16:00:00',
        ]);

        Carbon::setTestNow('2023-01-15 10:00:00'); // Before 16:00
        
        $schedule->calculateNextRun();

        // Should be today at 16:00 since the time hasn't passed yet
        $expected = Carbon::parse('2023-01-15 16:00:00');
        $this->assertEquals($expected, $schedule->next_run_at);
    }

    /** @test */
    public function it_can_mark_schedule_as_run()
    {
        $schedule = BackupSchedule::factory()->create([
            'frequency' => 'daily',
            'time' => '14:30:00',
            'last_run_at' => null,
        ]);

        Carbon::setTestNow('2023-01-15 14:35:00');
        
        $schedule->markAsRun();

        $this->assertEquals(Carbon::now(), $schedule->last_run_at);
        $this->assertNotNull($schedule->next_run_at);
        $this->assertGreaterThan(Carbon::now(), $schedule->next_run_at);
    }

    /** @test */
    public function it_can_determine_if_schedule_is_due()
    {
        // Create a schedule that's due
        $dueSchedule = BackupSchedule::factory()->create([
            'is_active' => true,
            'next_run_at' => Carbon::now()->subMinutes(5),
        ]);

        // Create a schedule that's not due yet
        $notDueSchedule = BackupSchedule::factory()->create([
            'is_active' => true,
            'next_run_at' => Carbon::now()->addHours(1),
        ]);

        // Create an inactive schedule that would be due
        $inactiveSchedule = BackupSchedule::factory()->create([
            'is_active' => false,
            'next_run_at' => Carbon::now()->subMinutes(5),
        ]);

        $this->assertTrue($dueSchedule->isDue());
        $this->assertFalse($notDueSchedule->isDue());
        $this->assertFalse($inactiveSchedule->isDue());
    }

    /** @test */
    public function it_has_active_scope()
    {
        BackupSchedule::factory()->active()->create(['name' => 'Active 1']);
        BackupSchedule::factory()->active()->create(['name' => 'Active 2']);
        BackupSchedule::factory()->inactive()->create(['name' => 'Inactive']);

        $activeSchedules = BackupSchedule::active()->get();

        $this->assertCount(2, $activeSchedules);
        $this->assertTrue($activeSchedules->every(fn($schedule) => $schedule->is_active));
    }

    /** @test */
    public function it_has_due_scope()
    {
        BackupSchedule::factory()->due()->create(['name' => 'Due 1']);
        BackupSchedule::factory()->due()->create(['name' => 'Due 2']);
        BackupSchedule::factory()->notDue()->create(['name' => 'Not Due']);
        BackupSchedule::factory()->inactive()->create([
            'name' => 'Inactive Due',
            'next_run_at' => Carbon::now()->subMinutes(5),
        ]);

        $dueSchedules = BackupSchedule::due()->get();

        $this->assertCount(2, $dueSchedules);
        $this->assertTrue($dueSchedules->every(fn($schedule) => $schedule->isDue()));
    }

    /** @test */
    public function it_has_type_scope()
    {
        BackupSchedule::factory()->databaseOnly()->create();
        BackupSchedule::factory()->filesOnly()->create();
        BackupSchedule::factory()->fullBackup()->create();

        $databaseSchedules = BackupSchedule::ofType('database')->get();
        $fileSchedules = BackupSchedule::ofType('files')->get();
        $fullSchedules = BackupSchedule::ofType('full')->get();

        $this->assertCount(1, $databaseSchedules);
        $this->assertCount(1, $fileSchedules);
        $this->assertCount(1, $fullSchedules);

        $this->assertEquals('database', $databaseSchedules->first()->type);
        $this->assertEquals('files', $fileSchedules->first()->type);
        $this->assertEquals('full', $fullSchedules->first()->type);
    }

    /** @test */
    public function it_has_frequency_scope()
    {
        BackupSchedule::factory()->daily()->create();
        BackupSchedule::factory()->weekly()->create();
        BackupSchedule::factory()->monthly()->create();

        $dailySchedules = BackupSchedule::ofFrequency('daily')->get();
        $weeklySchedules = BackupSchedule::ofFrequency('weekly')->get();
        $monthlySchedules = BackupSchedule::ofFrequency('monthly')->get();

        $this->assertCount(1, $dailySchedules);
        $this->assertCount(1, $weeklySchedules);
        $this->assertCount(1, $monthlySchedules);

        $this->assertEquals('daily', $dailySchedules->first()->frequency);
        $this->assertEquals('weekly', $weeklySchedules->first()->frequency);
        $this->assertEquals('monthly', $monthlySchedules->first()->frequency);
    }

    /** @test */
    public function it_has_frequency_label_attribute()
    {
        $schedule = BackupSchedule::factory()->create(['frequency' => 'daily']);
        $this->assertEquals('Daily', $schedule->frequency_label);

        $schedule = BackupSchedule::factory()->create(['frequency' => 'weekly']);
        $this->assertEquals('Weekly', $schedule->frequency_label);

        $schedule = BackupSchedule::factory()->create(['frequency' => 'monthly']);
        $this->assertEquals('Monthly', $schedule->frequency_label);
    }

    /** @test */
    public function it_has_type_label_attribute()
    {
        $schedule = BackupSchedule::factory()->create(['type' => 'database']);
        $this->assertEquals('Database Only', $schedule->type_label);

        $schedule = BackupSchedule::factory()->create(['type' => 'files']);
        $this->assertEquals('Files Only', $schedule->type_label);

        $schedule = BackupSchedule::factory()->create(['type' => 'full']);
        $this->assertEquals('Full Backup', $schedule->type_label);
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $schedule = BackupSchedule::factory()->create([
            'is_active' => 1,
            'retention_days' => '30',
            'time' => '14:30:00',
        ]);

        $this->assertIsBool($schedule->is_active);
        $this->assertIsInt($schedule->retention_days);
        $this->assertInstanceOf(Carbon::class, $schedule->time);
        $this->assertEquals('14:30:00', $schedule->time->format('H:i:s'));
    }

    /** @test */
    public function it_handles_edge_case_for_monthly_frequency_on_31st()
    {
        $schedule = BackupSchedule::factory()->create([
            'frequency' => 'monthly',
            'time' => '02:00:00',
        ]);

        // Set test time to January 31st
        Carbon::setTestNow('2023-01-31 10:00:00');
        
        $schedule->calculateNextRun();

        // Carbon's addMonth() will overflow to March 3rd when adding a month to Jan 31st
        // since February doesn't have 31 days
        $expected = Carbon::parse('2023-03-03 02:00:00');
        $this->assertEquals($expected, $schedule->next_run_at);
    }



    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset Carbon test time
        parent::tearDown();
    }
}