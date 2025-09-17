<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Backup;
use App\Models\Role;
use App\Services\BackupService;
use App\Services\BackupResult;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Mockery;

class BackupDashboardBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'superadmin', 'description' => 'Super Administrator']);
        Role::create(['name' => 'admin', 'description' => 'Administrator']);
        Role::create(['name' => 'customer', 'description' => 'Customer']);
    }

    /**
     * Test that backup dashboard loads correctly for superadmin
     */
    public function test_backup_dashboard_loads_for_superadmin()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-dashboard')
                    ->assertSee('Backup Management')
                    ->assertSee('Monitor and manage system backups')
                    ->assertSee('System Health')
                    ->assertSee('Success Rate')
                    ->assertSee('Storage Usage')
                    ->assertSee('Last Backup')
                    ->assertSee('Recent Backups')
                    ->assertSee('Create Backup');
        });
    }

    /**
     * Test backup dashboard status cards display correctly
     */
    public function test_backup_dashboard_status_cards_display()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        // Create some test backup data
        Backup::factory()->create([
            'status' => 'completed',
            'type' => 'full',
            'created_by' => $user->id,
            'created_at' => now()->subHours(2),
        ]);

        Backup::factory()->create([
            'status' => 'failed',
            'type' => 'database',
            'created_by' => $user->id,
            'created_at' => now()->subHours(4),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-dashboard')
                    ->assertSee('System Health')
                    ->assertSee('Success Rate (7 days)')
                    ->assertSee('Storage Usage')
                    ->assertSee('Last Backup')
                    ->assertSee('2 hours ago'); // Should show time since last backup
        });
    }

    /**
     * Test create backup modal opens and closes correctly
     */
    public function test_create_backup_modal_functionality()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-dashboard')
                    ->click('@create-backup-button')
                    ->waitFor('.fixed.inset-0.z-50') // Wait for modal
                    ->assertSee('Create New Backup')
                    ->assertSee('Backup Type')
                    ->assertSee('Custom Name')
                    ->assertSee('Full Backup (Database + Files)')
                    ->click('button:contains("Cancel")')
                    ->waitUntilMissing('.fixed.inset-0.z-50'); // Wait for modal to close
        });
    }

    /**
     * Test backup type selection in modal
     */
    public function test_backup_type_selection()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-dashboard')
                    ->click('@create-backup-button')
                    ->waitFor('.fixed.inset-0.z-50')
                    ->select('backupType', 'database')
                    ->assertSee('Database Only: Creates a backup of the MySQL database only.')
                    ->select('backupType', 'files')
                    ->assertSee('Files Only: Creates backups of file storage directories only.')
                    ->select('backupType', 'full')
                    ->assertSee('Full Backup: Creates backups of both the database and file storage directories.');
        });
    }

    /**
     * Test manual backup creation with mocked service
     */
    public function test_manual_backup_creation()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        // Mock the BackupService
        $mockBackupService = Mockery::mock(BackupService::class);
        $mockBackup = Backup::factory()->make([
            'id' => 1,
            'name' => 'test_backup',
            'status' => 'completed',
        ]);
        
        $mockResult = new BackupResult(true, 'Backup created successfully', $mockBackup);
        $mockBackupService->shouldReceive('createManualBackup')
                         ->once()
                         ->andReturn($mockResult);

        $this->app->instance(BackupService::class, $mockBackupService);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-dashboard')
                    ->click('@create-backup-button')
                    ->waitFor('.fixed.inset-0.z-50')
                    ->type('customName', 'test-backup')
                    ->select('backupType', 'database')
                    ->click('button[type="submit"]')
                    ->waitFor('.bg-green-50') // Wait for success message
                    ->assertSee('Backup created successfully');
        });
    }

    /**
     * Test recent backups table displays correctly
     */
    public function test_recent_backups_table()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        // Create test backups
        $backup1 = Backup::factory()->create([
            'name' => 'test_backup_1',
            'status' => 'completed',
            'type' => 'full',
            'file_size' => 1024 * 1024, // 1MB
            'created_by' => $user->id,
            'created_at' => now()->subHours(1),
        ]);

        $backup2 = Backup::factory()->create([
            'name' => 'test_backup_2',
            'status' => 'failed',
            'type' => 'database',
            'file_size' => 512 * 1024, // 512KB
            'created_by' => $user->id,
            'created_at' => now()->subHours(3),
        ]);

        $this->browse(function (Browser $browser) use ($user, $backup1, $backup2) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-dashboard')
                    ->assertSee('Recent Backups')
                    ->assertSee('test_backup_1')
                    ->assertSee('test_backup_2')
                    ->assertSee('Completed')
                    ->assertSee('Failed')
                    ->assertSee('Full')
                    ->assertSee('Database')
                    ->assertSee('1 MB')
                    ->assertSee('512 KB');
        });
    }

    /**
     * Test empty state when no backups exist
     */
    public function test_empty_state_display()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-dashboard')
                    ->assertSee('No backups found')
                    ->assertSee('Get started by creating your first backup.')
                    ->assertSee('Create First Backup');
        });
    }

    /**
     * Test auto-refresh functionality
     */
    public function test_auto_refresh_toggle()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-dashboard')
                    ->assertSee('Auto-refresh')
                    ->check('#auto-refresh')
                    ->pause(1000) // Wait for JavaScript to process
                    ->uncheck('#auto-refresh');
        });
    }

    /**
     * Test manual refresh button
     */
    public function test_manual_refresh_button()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-dashboard')
                    ->click('button:contains("Refresh")')
                    ->waitFor('.bg-green-50') // Wait for success message
                    ->assertSee('Dashboard data refreshed.');
        });
    }

    /**
     * Test storage usage warning display
     */
    public function test_storage_usage_warning()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        // Create large backup to trigger storage warning
        Backup::factory()->create([
            'status' => 'completed',
            'type' => 'full',
            'file_size' => 2 * 1024 * 1024 * 1024, // 2GB
            'created_by' => $user->id,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-dashboard')
                    ->assertSee('Storage Usage')
                    ->assertSee('2 GB'); // Should show the large file size
        });
    }

    /**
     * Test access control - customer cannot access backup dashboard
     */
    public function test_customer_cannot_access_backup_dashboard()
    {
        $customerRole = Role::where('name', 'customer')->first();
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($customer) {
            $browser->loginAs($customer)
                    ->visit('/admin/backup-dashboard')
                    ->assertPathIs('/') // Should redirect to home
                    ->assertDontSee('Backup Management');
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}