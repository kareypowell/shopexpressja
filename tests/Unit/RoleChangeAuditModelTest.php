<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\RoleChangeAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleChangeAuditModelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;
    private Role $customerRole;
    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $this->customerRole = Role::factory()->create(['name' => 'customer']);
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        
        // Create users
        $this->user = User::factory()->create(['role_id' => $this->customerRole->id]);
        $this->admin = User::factory()->create(['role_id' => $this->adminRole->id]);
    }

    public function test_can_create_role_change_audit()
    {
        $audit = RoleChangeAudit::create([
            'user_id' => $this->user->id,
            'changed_by_user_id' => $this->admin->id,
            'old_role_id' => $this->customerRole->id,
            'new_role_id' => $this->adminRole->id,
            'reason' => 'Test role change',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
        ]);

        $this->assertInstanceOf(RoleChangeAudit::class, $audit);
        $this->assertEquals($this->user->id, $audit->user_id);
        $this->assertEquals($this->admin->id, $audit->changed_by_user_id);
        $this->assertEquals($this->customerRole->id, $audit->old_role_id);
        $this->assertEquals($this->adminRole->id, $audit->new_role_id);
        $this->assertEquals('Test role change', $audit->reason);
        $this->assertEquals('192.168.1.1', $audit->ip_address);
        $this->assertEquals('Test Browser', $audit->user_agent);
    }

    public function test_can_create_audit_with_null_old_role()
    {
        $audit = RoleChangeAudit::create([
            'user_id' => $this->user->id,
            'changed_by_user_id' => $this->admin->id,
            'old_role_id' => null, // New user assignment
            'new_role_id' => $this->customerRole->id,
            'reason' => 'Initial role assignment',
        ]);

        $this->assertNull($audit->old_role_id);
        $this->assertEquals($this->customerRole->id, $audit->new_role_id);
    }

    public function test_user_relationship()
    {
        $audit = RoleChangeAudit::create([
            'user_id' => $this->user->id,
            'changed_by_user_id' => $this->admin->id,
            'old_role_id' => $this->customerRole->id,
            'new_role_id' => $this->adminRole->id,
        ]);

        $this->assertInstanceOf(User::class, $audit->user);
        $this->assertEquals($this->user->id, $audit->user->id);
        $this->assertEquals($this->user->email, $audit->user->email);
    }

    public function test_changed_by_relationship()
    {
        $audit = RoleChangeAudit::create([
            'user_id' => $this->user->id,
            'changed_by_user_id' => $this->admin->id,
            'old_role_id' => $this->customerRole->id,
            'new_role_id' => $this->adminRole->id,
        ]);

        $this->assertInstanceOf(User::class, $audit->changedBy);
        $this->assertEquals($this->admin->id, $audit->changedBy->id);
        $this->assertEquals($this->admin->email, $audit->changedBy->email);
    }

    public function test_old_role_relationship()
    {
        $audit = RoleChangeAudit::create([
            'user_id' => $this->user->id,
            'changed_by_user_id' => $this->admin->id,
            'old_role_id' => $this->customerRole->id,
            'new_role_id' => $this->adminRole->id,
        ]);

        $this->assertInstanceOf(Role::class, $audit->oldRole);
        $this->assertEquals($this->customerRole->id, $audit->oldRole->id);
        $this->assertEquals($this->customerRole->name, $audit->oldRole->name);
    }

    public function test_new_role_relationship()
    {
        $audit = RoleChangeAudit::create([
            'user_id' => $this->user->id,
            'changed_by_user_id' => $this->admin->id,
            'old_role_id' => $this->customerRole->id,
            'new_role_id' => $this->adminRole->id,
        ]);

        $this->assertInstanceOf(Role::class, $audit->newRole);
        $this->assertEquals($this->adminRole->id, $audit->newRole->id);
        $this->assertEquals($this->adminRole->name, $audit->newRole->name);
    }

    public function test_old_role_relationship_with_null()
    {
        $audit = RoleChangeAudit::create([
            'user_id' => $this->user->id,
            'changed_by_user_id' => $this->admin->id,
            'old_role_id' => null,
            'new_role_id' => $this->customerRole->id,
        ]);

        $this->assertNull($audit->oldRole);
    }

    public function test_for_user_scope()
    {
        $otherUser = User::factory()->create(['role_id' => $this->customerRole->id]);

        // Create audits for different users
        RoleChangeAudit::create([
            'user_id' => $this->user->id,
            'changed_by_user_id' => $this->admin->id,
            'new_role_id' => $this->adminRole->id,
        ]);

        RoleChangeAudit::create([
            'user_id' => $otherUser->id,
            'changed_by_user_id' => $this->admin->id,
            'new_role_id' => $this->adminRole->id,
        ]);

        $userAudits = RoleChangeAudit::forUser($this->user->id)->get();
        $this->assertCount(1, $userAudits);
        $this->assertEquals($this->user->id, $userAudits->first()->user_id);
    }

    public function test_by_user_scope()
    {
        $otherAdmin = User::factory()->create(['role_id' => $this->adminRole->id]);

        // Create audits by different admins
        RoleChangeAudit::create([
            'user_id' => $this->user->id,
            'changed_by_user_id' => $this->admin->id,
            'new_role_id' => $this->adminRole->id,
        ]);

        RoleChangeAudit::create([
            'user_id' => $this->user->id,
            'changed_by_user_id' => $otherAdmin->id,
            'new_role_id' => $this->customerRole->id,
        ]);

        $adminAudits = RoleChangeAudit::byUser($this->admin->id)->get();
        $this->assertCount(1, $adminAudits);
        $this->assertEquals($this->admin->id, $adminAudits->first()->changed_by_user_id);
    }

    public function test_recent_scope()
    {
        // Create an old audit using raw SQL to bypass model timestamps
        $oldDate = now()->subDays(45);
        $oldAudit = RoleChangeAudit::create([
            'user_id' => $this->user->id,
            'changed_by_user_id' => $this->admin->id,
            'new_role_id' => $this->adminRole->id,
        ]);
        
        // Force update the created_at timestamp using raw SQL
        \DB::table('role_change_audits')
            ->where('id', $oldAudit->id)
            ->update(['created_at' => $oldDate, 'updated_at' => $oldDate]);

        // Create a recent audit
        $recentAudit = RoleChangeAudit::create([
            'user_id' => $this->user->id,
            'changed_by_user_id' => $this->admin->id,
            'new_role_id' => $this->customerRole->id,
        ]);

        // Test with 30 days - should only get the recent one
        $recentAudits = RoleChangeAudit::recent(30)->get();
        
        // Should only include audits from the last 30 days
        $this->assertTrue($recentAudits->contains('id', $recentAudit->id));
        $this->assertFalse($recentAudits->contains('id', $oldAudit->id));
    }

    public function test_casts_timestamps_to_datetime()
    {
        $audit = RoleChangeAudit::create([
            'user_id' => $this->user->id,
            'changed_by_user_id' => $this->admin->id,
            'new_role_id' => $this->adminRole->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $audit->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $audit->updated_at);
    }
}