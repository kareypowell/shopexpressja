<?php

namespace Tests\Feature;

use App\Events\RoleChanged;
use App\Listeners\AuthenticationAuditListener;
use App\Listeners\RoleChangeAuditListener;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Services\FailedAuthenticationTracker;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AuthenticationAuditTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles if they don't exist
        Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer role']);
        Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator role']);
        Role::firstOrCreate(['name' => 'superadmin'], ['description' => 'Super administrator role']);
    }

    /** @test */
    public function it_logs_successful_login_events()
    {
        $user = User::factory()->create([
            'role_id' => Role::where('name', 'customer')->first()->id
        ]);

        // Simulate login event
        event(new Login('web', $user, false));

        // Check audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event_type' => 'authentication',
            'action' => 'login'
        ]);

        $auditLog = AuditLog::where('user_id', $user->id)
            ->where('action', 'login')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals('web', $auditLog->additional_data['guard']);
        $this->assertEquals($user->name, $auditLog->additional_data['user_name']);
        $this->assertEquals($user->email, $auditLog->additional_data['user_email']);
    }

    /** @test */
    public function it_logs_logout_events()
    {
        $user = User::factory()->create([
            'role_id' => Role::where('name', 'customer')->first()->id
        ]);

        // Simulate logout event
        event(new Logout('web', $user));

        // Check audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event_type' => 'authentication',
            'action' => 'logout'
        ]);
    }

    /** @test */
    public function it_tracks_failed_authentication_attempts()
    {
        $credentials = ['email' => 'test@example.com', 'password' => 'wrong'];

        // Simulate failed login event
        event(new Failed('web', null, $credentials));

        // Check security event was logged
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'security_event',
            'action' => 'failed_authentication'
        ]);

        $auditLog = AuditLog::where('action', 'failed_authentication')->first();
        $this->assertEquals('test@example.com', $auditLog->additional_data['attempted_email']);
    }

    /** @test */
    public function it_blocks_ip_after_excessive_failed_attempts()
    {
        $tracker = app(FailedAuthenticationTracker::class);
        $ip = '192.168.1.100';
        
        // Simulate 10 failed attempts with the specific IP
        for ($i = 0; $i < 10; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->post('/test', []); // Trigger a request with the IP
            
            $tracker->trackFailedAttempt(['email' => 'test@example.com']);
        }

        // IP should now be blocked
        $this->assertTrue($tracker->isBlocked($ip, 'ip'));

        // Check that a blocking event was logged
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'security_event',
            'action' => 'authentication_blocked'
        ]);
    }

    /** @test */
    public function it_logs_role_changes()
    {
        $user = User::factory()->create([
            'role_id' => Role::where('name', 'customer')->first()->id
        ]);
        
        $adminUser = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->first()->id
        ]);
        
        // Authenticate as admin user to perform the role change
        $this->actingAs($adminUser);
        
        $adminRole = Role::where('name', 'admin')->first();
        $oldRoleId = $user->role_id;

        // Simulate role change event
        $roleChangeEvent = new RoleChanged($user, $oldRoleId, $adminRole->id, 'Promoted to admin');
        
        // Manually call the listener for testing
        $listener = app(RoleChangeAuditListener::class);
        $listener->handleRoleChange($roleChangeEvent);

        // Check audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event_type' => 'authorization',
            'action' => 'role_change'
        ]);

        $auditLog = AuditLog::where('user_id', $user->id)
            ->where('action', 'role_change')
            ->first();

        $this->assertEquals($oldRoleId, $auditLog->old_values['old_role_id']);
        $this->assertEquals($adminRole->id, $auditLog->new_values['new_role_id']);
        $this->assertEquals('Promoted to admin', $auditLog->new_values['reason']);
    }

    /** @test */
    public function it_detects_privilege_escalation()
    {
        $user = User::factory()->create([
            'role_id' => Role::where('name', 'customer')->first()->id
        ]);
        
        $adminUser = User::factory()->create([
            'role_id' => Role::where('name', 'superadmin')->first()->id
        ]);
        
        // Authenticate as superadmin user to perform the role change
        $this->actingAs($adminUser);
        
        $superadminRole = Role::where('name', 'superadmin')->first();
        $oldRoleId = $user->role_id;

        // Simulate privilege escalation
        $roleChangeEvent = new RoleChanged($user, $oldRoleId, $superadminRole->id);
        
        // Manually call the listener for testing
        $listener = app(RoleChangeAuditListener::class);
        $listener->handleRoleChange($roleChangeEvent);

        // Check that privilege escalation was logged as security event
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'security_event',
            'action' => 'privilege_escalation'
        ]);

        $securityLog = AuditLog::where('action', 'privilege_escalation')->first();
        $this->assertEquals('high', $securityLog->additional_data['severity']);
        $this->assertEquals($user->id, $securityLog->additional_data['user_id']);
    }

    /** @test */
    public function it_resets_failed_attempts_on_successful_login()
    {
        $tracker = app(FailedAuthenticationTracker::class);
        $email = 'test@example.com';

        // Simulate some failed attempts
        for ($i = 0; $i < 3; $i++) {
            $tracker->trackFailedAttempt(['email' => $email]);
        }

        // Verify attempts were recorded
        $this->assertGreaterThan(0, Cache::get("failed_attempts_email_{$email}", 0));

        // Simulate successful login
        $tracker->trackSuccessfulAttempt($email);

        // Verify attempts were reset
        $this->assertEquals(0, Cache::get("failed_attempts_email_{$email}", 0));
    }

    /** @test */
    public function middleware_blocks_requests_from_blocked_ips()
    {
        $tracker = app(FailedAuthenticationTracker::class);
        
        // Block an IP
        Cache::put('auth_blocked_ip_192.168.1.100', true, now()->addMinutes(30));

        // Simulate request from blocked IP to login page
        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
            ->get('/login');

        // Since we're testing the middleware logic, let's test it directly
        $middleware = new \App\Http\Middleware\BlockFailedAuthenticationMiddleware($tracker);
        $request = \Illuminate\Http\Request::create('/login', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');
        
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(429, $response->getStatusCode());
    }

    /** @test */
    public function it_provides_failed_attempt_statistics()
    {
        $tracker = app(FailedAuthenticationTracker::class);

        // Create some failed attempts
        for ($i = 0; $i < 5; $i++) {
            $tracker->trackFailedAttempt(['email' => "test{$i}@example.com"]);
        }

        $stats = $tracker->getFailedAttemptStats(24);

        $this->assertArrayHasKey('total_attempts', $stats);
        $this->assertArrayHasKey('unique_ips', $stats);
        $this->assertArrayHasKey('unique_emails', $stats);
        $this->assertGreaterThan(0, $stats['total_attempts']);
    }
}