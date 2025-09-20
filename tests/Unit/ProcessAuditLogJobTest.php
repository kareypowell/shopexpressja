<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\ProcessAuditLogJob;
use App\Services\AuditService;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ProcessAuditLogJobTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_processes_single_audit_log_entry()
    {
        $auditData = [
            'event_type' => 'test_event',
            'action' => 'test_action',
            'additional_data' => ['test' => 'data']
        ];

        $job = new ProcessAuditLogJob($auditData, false);
        $job->handle(app(AuditService::class));

        $this->assertEquals(1, AuditLog::count());
        
        $auditLog = AuditLog::first();
        $this->assertEquals('test_event', $auditLog->event_type);
        $this->assertEquals('test_action', $auditLog->action);
    }

    /** @test */
    public function it_processes_batch_audit_log_entries()
    {
        $auditData = [
            [
                'event_type' => 'test_event_1',
                'action' => 'test_action_1'
            ],
            [
                'event_type' => 'test_event_2',
                'action' => 'test_action_2'
            ]
        ];

        $job = new ProcessAuditLogJob($auditData, true);
        $job->handle(app(AuditService::class));

        $this->assertEquals(2, AuditLog::count());
        
        $logs = AuditLog::all();
        $this->assertEquals('test_event_1', $logs[0]->event_type);
        $this->assertEquals('test_event_2', $logs[1]->event_type);
    }

    /** @test */
    public function it_handles_exceptions_gracefully()
    {
        // Mock the AuditService to throw an exception
        $mockService = Mockery::mock(AuditService::class);
        $mockService->shouldReceive('log')
                   ->once()
                   ->andThrow(new \Exception('Test exception'));

        $auditData = [
            'event_type' => 'test_event',
            'action' => 'test_action'
        ];

        $job = new ProcessAuditLogJob($auditData, false);
        
        // The job should not throw an exception, just handle it gracefully
        $job->handle($mockService);
        
        // No audit logs should be created due to the exception
        $this->assertEquals(0, AuditLog::count());
    }
}