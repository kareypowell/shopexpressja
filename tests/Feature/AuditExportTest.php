<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Role;
use App\Services\AuditExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Carbon\Carbon;

class AuditExportTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;
    protected $admin;
    protected $customer;
    protected $exportService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create or get roles
        $superAdminRole = Role::firstOrCreate(['name' => 'superadmin'], ['description' => 'Super Administrator']);
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);
        $customerRole = Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer']);

        // Create users
        $this->superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->customer = User::factory()->create(['role_id' => $customerRole->id]);

        $this->exportService = new AuditExportService();

        // Create test audit logs
        AuditLog::factory()->count(10)->create([
            'user_id' => $this->admin->id,
            'event_type' => 'authentication',
            'action' => 'login',
            'created_at' => Carbon::now()->subDays(1),
        ]);

        AuditLog::factory()->count(5)->create([
            'user_id' => $this->customer->id,
            'event_type' => 'model_updated',
            'action' => 'update',
            'auditable_type' => 'App\Models\Package',
            'created_at' => Carbon::now()->subHours(2),
        ]);

        Storage::fake('public');
    }

    /** @test */
    public function it_can_export_audit_logs_to_csv()
    {
        $auditLogs = AuditLog::with('user')->get();
        $filters = ['event_type' => 'authentication'];

        $csvContent = $this->exportService->exportToCsv($auditLogs, $filters);

        $this->assertStringContainsString('"ID","Date/Time","Event Type","Action","User"', $csvContent);
        $this->assertStringContainsString('authentication', $csvContent);
        $this->assertStringContainsString('login', $csvContent);
        $this->assertStringContainsString($this->admin->name, $csvContent);
    }

    /** @test */
    public function it_can_export_audit_logs_to_pdf()
    {
        $auditLogs = AuditLog::with('user')->get();
        $filters = ['event_type' => 'authentication'];
        $options = ['title' => 'Test Audit Report'];

        $filePath = $this->exportService->exportToPdf($auditLogs, $filters, $options);

        $this->assertNotEmpty($filePath);
        Storage::disk('public')->assertExists($filePath);
        
        $fileContent = Storage::disk('public')->get($filePath);
        $this->assertStringContainsString('%PDF', $fileContent); // PDF header
    }

    /** @test */
    public function it_can_generate_compliance_report()
    {
        $filters = [
            'date_from' => Carbon::now()->subDays(7)->format('Y-m-d'),
            'date_to' => Carbon::now()->format('Y-m-d'),
        ];

        $reportData = $this->exportService->generateComplianceReport($filters);

        $this->assertArrayHasKey('report_metadata', $reportData);
        $this->assertArrayHasKey('statistics', $reportData);
        $this->assertArrayHasKey('event_type_breakdown', $reportData);
        $this->assertArrayHasKey('security_analysis', $reportData);
        $this->assertArrayHasKey('audit_logs', $reportData);

        $this->assertGreaterThanOrEqual(15, $reportData['statistics']['total_events']);
        $this->assertGreaterThan(0, $reportData['statistics']['unique_users']);
    }

    /** @test */
    public function it_can_create_export_template()
    {
        $templateName = 'security_events_template';
        $configuration = [
            'fields' => ['id', 'created_at', 'event_type', 'action', 'user_id'],
            'filters' => ['event_type' => 'security_event'],
            'format' => 'csv',
            'options' => ['include_headers' => true],
        ];

        $result = $this->exportService->createExportTemplate($templateName, $configuration);

        $this->assertTrue($result);

        $templates = $this->exportService->getExportTemplates();
        $this->assertCount(1, $templates);
        $this->assertEquals($templateName, $templates->first()['name']);
    }

    /** @test */
    public function it_can_schedule_report()
    {
        $configuration = [
            'name' => 'daily_security_report',
            'frequency' => 'daily',
            'filters' => ['event_type' => 'security_event'],
            'format' => 'pdf',
            'recipients' => ['admin@example.com'],
        ];

        $result = $this->exportService->scheduleReport($configuration);

        $this->assertTrue($result);

        $scheduledReports = $this->exportService->getScheduledReports();
        $this->assertCount(1, $scheduledReports);
        $this->assertEquals('daily_security_report', $scheduledReports->first()['name']);
    }

    /** @test */
    public function superadmin_can_access_export_functionality()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get('/admin/audit-logs');
        $response->assertStatus(200);

        // Test CSV export
        $response = $this->post('/livewire/message/admin.audit-log-management', [
            'fingerprint' => [
                'id' => 'audit-log-management',
                'name' => 'admin.audit-log-management',
                'locale' => 'en',
                'path' => 'admin/audit-logs',
                'method' => 'GET',
            ],
            'serverMemo' => [
                'children' => [],
                'errors' => [],
                'htmlHash' => 'test',
                'data' => [
                    'exportFormat' => 'csv',
                    'showExportModal' => false,
                ],
                'dataMeta' => [],
                'checksum' => 'test',
            ],
            'updates' => [
                [
                    'type' => 'callMethod',
                    'payload' => [
                        'method' => 'exportAuditLogs',
                        'params' => [],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_cannot_access_export_functionality()
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/audit-logs');
        $response->assertStatus(403);
    }

    /** @test */
    public function customer_cannot_access_export_functionality()
    {
        $this->actingAs($this->customer);

        $response = $this->get('/admin/audit-logs');
        $response->assertStatus(403);
    }

    /** @test */
    public function csv_export_handles_special_characters_correctly()
    {
        // Create audit log with special characters
        AuditLog::create([
            'user_id' => $this->admin->id,
            'event_type' => 'test_event',
            'action' => 'test_action',
            'old_values' => ['field' => 'Value with "quotes" and, commas'],
            'new_values' => ['field' => 'New value with special chars: <>&'],
            'created_at' => Carbon::now(),
        ]);

        $auditLogs = AuditLog::with('user')->get();
        $csvContent = $this->exportService->exportToCsv($auditLogs);

        // Check that quotes are properly escaped
        $this->assertStringContainsString('Value with ""quotes"" and, commas', $csvContent);
        $this->assertStringContainsString('New value with special chars: <>&', $csvContent);
    }

    /** @test */
    public function pdf_export_includes_proper_metadata()
    {
        $auditLogs = AuditLog::with('user')->take(5)->get();
        $filters = [
            'event_type' => 'authentication',
            'date_from' => Carbon::now()->subDays(7)->format('Y-m-d'),
            'date_to' => Carbon::now()->format('Y-m-d'),
        ];
        $options = [
            'title' => 'Authentication Audit Report',
        ];

        $filePath = $this->exportService->exportToPdf($auditLogs, $filters, $options);

        Storage::disk('public')->assertExists($filePath);
        
        // Verify file is not empty
        $fileSize = Storage::disk('public')->size($filePath);
        $this->assertGreaterThan(0, $fileSize);
    }

    /** @test */
    public function export_service_applies_filters_correctly()
    {
        // Create specific audit logs for filtering
        AuditLog::create([
            'user_id' => $this->admin->id,
            'event_type' => 'security_event',
            'action' => 'failed_login',
            'ip_address' => '192.168.1.100',
            'created_at' => Carbon::now()->subDays(1),
        ]);

        $filters = [
            'event_type' => 'security_event',
            'action' => 'failed_login',
            'ip_address' => '192.168.1.100',
        ];

        $reportData = $this->exportService->generateComplianceReport($filters);

        $this->assertEquals(1, $reportData['statistics']['total_events']);
        $this->assertEquals('security_event', $reportData['audit_logs']->first()->event_type);
        $this->assertEquals('failed_login', $reportData['audit_logs']->first()->action);
    }
}