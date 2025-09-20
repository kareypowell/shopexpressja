<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AuditService;
use App\Models\User;
use App\Models\Role;

class TestAuditServiceCommand extends Command
{
    protected $signature = 'audit:test';
    protected $description = 'Test the AuditService functionality';

    public function handle(AuditService $auditService)
    {
        $this->info('Testing AuditService functionality...');

        // Test system event logging
        $this->info('1. Testing system event logging...');
        $systemLog = $auditService->logSystemEvent('test_command_executed', [
            'command' => 'audit:test',
            'timestamp' => now()->toISOString()
        ]);
        $this->info("   ✓ System event logged with ID: {$systemLog->id}");

        // Test authentication event logging
        $this->info('2. Testing authentication event logging...');
        $authLog = $auditService->logAuthentication('test_login', null, [
            'test_mode' => true,
            'ip_address' => '127.0.0.1'
        ]);
        $this->info("   ✓ Authentication event logged with ID: {$authLog->id}");

        // Test security event logging
        $this->info('3. Testing security event logging...');
        $securityLog = $auditService->logSecurityEvent('test_security_check', [
            'severity' => 'low',
            'description' => 'Test security event from command'
        ]);
        $this->info("   ✓ Security event logged with ID: {$securityLog->id}");

        // Test financial transaction logging
        $this->info('4. Testing financial transaction logging...');
        $finLog = $auditService->logFinancialTransaction('test_transaction', [
            'amount' => 50.00,
            'currency' => 'USD',
            'type' => 'test_payment',
            'description' => 'Test transaction from command'
        ]);
        $this->info("   ✓ Financial transaction logged with ID: {$finLog->id}");

        // Test business action logging
        $this->info('5. Testing business action logging...');
        $businessLog = $auditService->logBusinessAction('test_business_action', null, [
            'action_type' => 'test',
            'description' => 'Test business action from command'
        ]);
        $this->info("   ✓ Business action logged with ID: {$businessLog->id}");

        // Test batch logging
        $this->info('6. Testing batch audit logging...');
        $batchData = [
            [
                'event_type' => 'batch_test_1',
                'action' => 'batch_action_1',
                'additional_data' => ['batch_item' => 1]
            ],
            [
                'event_type' => 'batch_test_2',
                'action' => 'batch_action_2',
                'additional_data' => ['batch_item' => 2]
            ]
        ];
        $batchResults = $auditService->logBatch($batchData);
        $this->info("   ✓ Batch audit logged " . count($batchResults) . " entries");

        // Test async logging
        $this->info('7. Testing async audit logging...');
        $auditService->logAsync([
            'event_type' => 'async_test',
            'action' => 'async_action',
            'additional_data' => ['async' => true]
        ]);
        $this->info("   ✓ Async audit job dispatched");

        $this->info('');
        $this->info('AuditService test completed successfully!');
        $this->info('Check the audit_logs table to see the created entries.');
        
        return 0;
    }
}