<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles if they don't exist
        Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer role']);
        Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin role']);
        Role::firstOrCreate(['name' => 'superadmin'], ['description' => 'Super Admin role']);
    }

    /** @test */
    public function superadmin_can_view_any_audit_logs()
    {
        $superadmin = User::factory()->create([
            'role_id' => Role::where('name', 'superadmin')->first()->id
        ]);

        $this->assertTrue($superadmin->can('viewAny', AuditLog::class));
    }

    /** @test */
    public function admin_cannot_view_audit_logs()
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->first()->id
        ]);

        $this->assertFalse($admin->can('viewAny', AuditLog::class));
    }

    /** @test */
    public function customer_cannot_view_audit_logs()
    {
        $customer = User::factory()->create([
            'role_id' => Role::where('name', 'customer')->first()->id
        ]);

        $this->assertFalse($customer->can('viewAny', AuditLog::class));
    }

    /** @test */
    public function superadmin_can_view_specific_audit_log()
    {
        $superadmin = User::factory()->create([
            'role_id' => Role::where('name', 'superadmin')->first()->id
        ]);

        // Create audit log manually to avoid factory issues
        $auditLog = AuditLog::create([
            'user_id' => $superadmin->id,
            'event_type' => 'authentication',
            'action' => 'login',
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->assertTrue($superadmin->can('view', $auditLog));
    }

    /** @test */
    public function superadmin_can_export_audit_logs()
    {
        $superadmin = User::factory()->create([
            'role_id' => Role::where('name', 'superadmin')->first()->id
        ]);

        $this->assertTrue($superadmin->can('export', AuditLog::class));
    }

    /** @test */
    public function superadmin_can_generate_compliance_reports()
    {
        $superadmin = User::factory()->create([
            'role_id' => Role::where('name', 'superadmin')->first()->id
        ]);

        $this->assertTrue($superadmin->can('generateComplianceReport', AuditLog::class));
    }

    /** @test */
    public function superadmin_can_manage_audit_settings()
    {
        $superadmin = User::factory()->create([
            'role_id' => Role::where('name', 'superadmin')->first()->id
        ]);

        $this->assertTrue($superadmin->can('manageSettings', AuditLog::class));
    }

    /** @test */
    public function admin_cannot_export_audit_logs()
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->first()->id
        ]);

        $this->assertFalse($admin->can('export', AuditLog::class));
    }

    /** @test */
    public function admin_cannot_manage_audit_settings()
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->first()->id
        ]);

        $this->assertFalse($admin->can('manageSettings', AuditLog::class));
    }

    /** @test */
    public function audit_gates_are_properly_registered()
    {
        $superadmin = User::factory()->create([
            'role_id' => Role::where('name', 'superadmin')->first()->id
        ]);

        $this->actingAs($superadmin);

        // Test that all audit gates are registered and working
        $this->assertTrue(auth()->user()->can('audit.viewAny'));
        $this->assertTrue(auth()->user()->can('audit.export'));
        $this->assertTrue(auth()->user()->can('audit.generateComplianceReport'));
        $this->assertTrue(auth()->user()->can('audit.manageSettings'));
        $this->assertTrue(auth()->user()->can('audit.createExportTemplate'));
        $this->assertTrue(auth()->user()->can('audit.scheduleReports'));
    }

    /** @test */
    public function admin_cannot_access_audit_gates()
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->first()->id
        ]);

        $this->actingAs($admin);

        // Test that admin cannot access audit gates
        $this->assertFalse(auth()->user()->can('audit.viewAny'));
        $this->assertFalse(auth()->user()->can('audit.export'));
        $this->assertFalse(auth()->user()->can('audit.generateComplianceReport'));
        $this->assertFalse(auth()->user()->can('audit.manageSettings'));
        $this->assertFalse(auth()->user()->can('audit.createExportTemplate'));
        $this->assertFalse(auth()->user()->can('audit.scheduleReports'));
    }
}