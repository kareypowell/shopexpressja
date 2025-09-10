<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\RoleChangeAudit;
use App\Models\User;
use App\Services\RoleChangeAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RoleChangeAuditServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoleChangeAuditService $auditService;
    private User $user;
    private User $admin;
    private Role $customerRole;
    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->auditService = new RoleChangeAuditService();
        
        // Create roles
        $this->customerRole = Role::factory()->create(['name' => 'customer']);
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        
        // Create users
        $this->user = User::factory()->create(['role_id' => $this->customerRole->id]);
        $this->admin = User::factory()->create(['role_id' => $this->adminRole->id]);
    }

    public function test_logs_role_change_with_authenticated_user()
    {
        Auth::login($this->admin);

        $audit = $this->auditService->logRoleChange(
            $this->user,
            $this->customerRole->id,
            $this->adminRole->id,
            'Promoted to admin'
        );

        $this->assertInstanceOf(RoleChangeAudit::class, $audit);
        $this->assertEquals($this->user->id, $audit->user_id);
        $this->assertEquals($this->admin->id, $audit->changed_by_user_id);
        $this->assertEquals($this->customerRole->id, $audit->old_role_id);
        $this->assertEquals($this->adminRole->id, $audit->new_role_id);
        $this->assertEquals('Promoted to admin', $audit->reason);
    }

    public function test_logs_role_change_with_explicit_changed_by_user()
    {
        $audit = $this->auditService->logRoleChange(
            $this->user,
            $this->customerRole->id,
            $this->adminRole->id,
            'Promoted to admin',
            null,
            $this->admin
        );

        $this->assertEquals($this->admin->id, $audit->changed_by_user_id);
    }

    public function test_logs_role_change_for_new_user_with_null_old_role()
    {
        Auth::login($this->admin);

        $audit = $this->auditService->logRoleChange(
            $this->user,
            null, // New user, no previous role
            $this->customerRole->id,
            'Initial role assignment'
        );

        $this->assertNull($audit->old_role_id);
        $this->assertEquals($this->customerRole->id, $audit->new_role_id);
        $this->assertEquals('Initial role assignment', $audit->reason);
    }

    public function test_logs_role_change_with_request_data()
    {
        Auth::login($this->admin);

        $request = Request::create('/', 'POST', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 Test Browser'
        ]);

        $audit = $this->auditService->logRoleChange(
            $this->user,
            $this->customerRole->id,
            $this->adminRole->id,
            'Role change with request data',
            $request
        );

        $this->assertEquals('192.168.1.1', $audit->ip_address);
        $this->assertEquals('Mozilla/5.0 Test Browser', $audit->user_agent);
    }

    public function test_extracts_ip_from_x_forwarded_for_header()
    {
        Auth::login($this->admin);

        $request = Request::create('/', 'POST', [], [], [], [
            'HTTP_X_FORWARDED_FOR' => '203.0.113.1, 192.168.1.1',
            'HTTP_USER_AGENT' => 'Test Browser'
        ]);

        $audit = $this->auditService->logRoleChange(
            $this->user,
            $this->customerRole->id,
            $this->adminRole->id,
            null,
            $request
        );

        $this->assertEquals('203.0.113.1', $audit->ip_address);
    }

    public function test_throws_exception_when_no_authenticated_user_and_no_changed_by()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No authenticated user found to log role change');

        $this->auditService->logRoleChange(
            $this->user,
            $this->customerRole->id,
            $this->adminRole->id
        );
    }

    public function test_gets_audit_trail_for_user()
    {
        Auth::login($this->admin);

        // Create multiple audit entries
        $this->auditService->logRoleChange($this->user, null, $this->customerRole->id, 'Initial assignment');
        $this->auditService->logRoleChange($this->user, $this->customerRole->id, $this->adminRole->id, 'Promotion');

        $auditTrail = $this->auditService->getAuditTrailForUser($this->user);

        $this->assertCount(2, $auditTrail);
        $this->assertEquals('Promotion', $auditTrail->first()->reason);
        $this->assertEquals('Initial assignment', $auditTrail->last()->reason);
    }

    public function test_gets_recent_role_changes()
    {
        Auth::login($this->admin);

        $this->auditService->logRoleChange($this->user, null, $this->customerRole->id, 'Recent change');

        $recentChanges = $this->auditService->getRecentRoleChanges(30);

        $this->assertCount(1, $recentChanges);
        $this->assertEquals('Recent change', $recentChanges->first()->reason);
    }

    public function test_gets_role_changes_by_user()
    {
        Auth::login($this->admin);

        $this->auditService->logRoleChange($this->user, null, $this->customerRole->id, 'Change by admin');

        $changesByUser = $this->auditService->getRoleChangesByUser($this->admin);

        $this->assertCount(1, $changesByUser);
        $this->assertEquals($this->admin->id, $changesByUser->first()->changed_by_user_id);
    }

    public function test_gets_role_change_statistics()
    {
        Auth::login($this->admin);

        // Create some audit entries
        $this->auditService->logRoleChange($this->user, null, $this->customerRole->id, 'With reason');
        $this->auditService->logRoleChange($this->user, $this->customerRole->id, $this->adminRole->id); // No reason

        $stats = $this->auditService->getRoleChangeStatistics(30);

        $this->assertEquals(2, $stats['total_changes']);
        $this->assertEquals(1, $stats['unique_users_affected']);
        $this->assertEquals(1, $stats['unique_changers']);
        $this->assertEquals(1, $stats['changes_with_reason']);
        $this->assertIsArray($stats['changes_by_day']);
    }

    public function test_audit_model_relationships()
    {
        Auth::login($this->admin);

        $audit = $this->auditService->logRoleChange(
            $this->user,
            $this->customerRole->id,
            $this->adminRole->id,
            'Testing relationships'
        );

        // Test relationships
        $this->assertEquals($this->user->id, $audit->user->id);
        $this->assertEquals($this->admin->id, $audit->changedBy->id);
        $this->assertEquals($this->customerRole->id, $audit->oldRole->id);
        $this->assertEquals($this->adminRole->id, $audit->newRole->id);
    }

    public function test_audit_model_scopes()
    {
        Auth::login($this->admin);

        $audit1 = $this->auditService->logRoleChange($this->user, null, $this->customerRole->id);
        $audit2 = $this->auditService->logRoleChange($this->user, $this->customerRole->id, $this->adminRole->id);

        // Test forUser scope
        $userAudits = RoleChangeAudit::forUser($this->user->id)->get();
        $this->assertCount(2, $userAudits);

        // Test byUser scope
        $adminAudits = RoleChangeAudit::byUser($this->admin->id)->get();
        $this->assertCount(2, $adminAudits);

        // Test recent scope
        $recentAudits = RoleChangeAudit::recent(1)->get();
        $this->assertCount(2, $recentAudits);
    }
}