<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Role;
use App\Models\BackupSchedule;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class BackupSettingsBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'superadmin']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'customer']);
    }

    /** @test */
    public function superadmin_can_access_backup_settings_page()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-settings')
                    ->assertSee('Backup Settings')
                    ->assertSee('Configure automated backup schedules, retention policies, and notifications')
                    ->assertSee('Automated Backup Schedules')
                    ->assertSee('Retention Policies')
                    ->assertSee('Notification Settings');
        });
    }

    /** @test */
    public function admin_cannot_access_backup_settings_page()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $user = User::factory()->create(['role_id' => $adminRole->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-settings')
                    ->assertStatus(403);
        });
    }

    /** @test */
    public function can_create_new_backup_schedule()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-settings')
                    ->click('@add-schedule-button')
                    ->waitFor('@schedule-modal')
                    ->type('@schedule-name', 'Daily Database Backup')
                    ->select('@schedule-type', 'database')
                    ->select('@schedule-frequency', 'daily')
                    ->type('@schedule-time', '02:00')
                    ->type('@schedule-retention', '30')
                    ->check('@schedule-active')
                    ->click('@save-schedule-button')
                    ->waitUntilMissing('@schedule-modal')
                    ->assertSee('Daily Database Backup')
                    ->assertSee('Database')
                    ->assertSee('Daily')
                    ->assertSee('02:00')
                    ->assertSee('Active');
        });

        $this->assertDatabaseHas('backup_schedules', [
            'name' => 'Daily Database Backup',
            'type' => 'database',
            'frequency' => 'daily',
            'time' => '02:00',
            'retention_days' => 30,
            'is_active' => true
        ]);
    }

    /** @test */
    public function can_edit_existing_backup_schedule()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);
        
        $schedule = BackupSchedule::create([
            'name' => 'Original Schedule',
            'type' => 'database',
            'frequency' => 'daily',
            'time' => '02:00',
            'retention_days' => 30,
            'is_active' => true
        ]);

        $this->browse(function (Browser $browser) use ($user, $schedule) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-settings')
                    ->click("@edit-schedule-{$schedule->id}")
                    ->waitFor('@schedule-modal')
                    ->clear('@schedule-name')
                    ->type('@schedule-name', 'Updated Schedule')
                    ->select('@schedule-type', 'full')
                    ->select('@schedule-frequency', 'weekly')
                    ->clear('@schedule-time')
                    ->type('@schedule-time', '03:00')
                    ->clear('@schedule-retention')
                    ->type('@schedule-retention', '60')
                    ->click('@save-schedule-button')
                    ->waitUntilMissing('@schedule-modal')
                    ->assertSee('Updated Schedule')
                    ->assertSee('Full')
                    ->assertSee('Weekly')
                    ->assertSee('03:00');
        });

        $this->assertDatabaseHas('backup_schedules', [
            'id' => $schedule->id,
            'name' => 'Updated Schedule',
            'type' => 'full',
            'frequency' => 'weekly',
            'time' => '03:00',
            'retention_days' => 60
        ]);
    }

    /** @test */
    public function can_delete_backup_schedule()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);
        
        $schedule = BackupSchedule::create([
            'name' => 'Schedule to Delete',
            'type' => 'database',
            'frequency' => 'daily',
            'time' => '02:00',
            'retention_days' => 30,
            'is_active' => true
        ]);

        $this->browse(function (Browser $browser) use ($user, $schedule) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-settings')
                    ->click("@delete-schedule-{$schedule->id}")
                    ->waitFor('@delete-confirmation-modal')
                    ->assertSee('Delete Backup Schedule')
                    ->assertSee('Are you sure you want to delete this backup schedule?')
                    ->click('@confirm-delete-button')
                    ->waitUntilMissing('@delete-confirmation-modal')
                    ->assertDontSee('Schedule to Delete');
        });

        $this->assertDatabaseMissing('backup_schedules', [
            'id' => $schedule->id
        ]);
    }

    /** @test */
    public function can_toggle_schedule_status()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);
        
        $schedule = BackupSchedule::create([
            'name' => 'Active Schedule',
            'type' => 'database',
            'frequency' => 'daily',
            'time' => '02:00',
            'retention_days' => 30,
            'is_active' => true
        ]);

        $this->browse(function (Browser $browser) use ($user, $schedule) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-settings')
                    ->assertSee('Active')
                    ->click("@toggle-schedule-{$schedule->id}")
                    ->waitForText('Inactive')
                    ->assertSee('Inactive');
        });

        $this->assertDatabaseHas('backup_schedules', [
            'id' => $schedule->id,
            'is_active' => false
        ]);
    }

    /** @test */
    public function can_update_retention_settings()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-settings')
                    ->clear('@database-retention')
                    ->type('@database-retention', '45')
                    ->clear('@files-retention')
                    ->type('@files-retention', '21')
                    ->click('@save-retention-button')
                    ->waitForText('Retention settings saved successfully');
        });
    }

    /** @test */
    public function can_update_notification_settings()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-settings')
                    ->type('@notification-email', 'admin@example.com')
                    ->check('@notify-success')
                    ->uncheck('@notify-failure')
                    ->click('@save-notification-button')
                    ->waitForText('Notification settings saved successfully');
        });
    }

    /** @test */
    public function can_send_test_notification_email()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-settings')
                    ->type('@notification-email', 'test@example.com')
                    ->click('@test-email-button')
                    ->waitForText('Test notification email sent successfully');
        });
    }

    /** @test */
    public function can_run_cleanup_now()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-settings')
                    ->click('@cleanup-now-button')
                    ->waitForText('Backup cleanup completed successfully');
        });
    }

    /** @test */
    public function validates_schedule_form_fields()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-settings')
                    ->click('@add-schedule-button')
                    ->waitFor('@schedule-modal')
                    ->click('@save-schedule-button')
                    ->waitForText('The name field is required')
                    ->type('@schedule-name', 'Test Schedule')
                    ->clear('@schedule-retention')
                    ->type('@schedule-retention', '0')
                    ->click('@save-schedule-button')
                    ->waitForText('The retention days must be at least 1');
        });
    }

    /** @test */
    public function validates_retention_settings_form()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-settings')
                    ->clear('@database-retention')
                    ->type('@database-retention', '0')
                    ->click('@save-retention-button')
                    ->waitForText('The database days must be at least 1');
        });
    }

    /** @test */
    public function validates_notification_email_format()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-settings')
                    ->type('@notification-email', 'invalid-email')
                    ->click('@save-notification-button')
                    ->waitForText('The email must be a valid email address');
        });
    }

    /** @test */
    public function shows_empty_state_when_no_schedules_exist()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-settings')
                    ->assertSee('No backup schedules')
                    ->assertSee('Get started by creating your first automated backup schedule');
        });
    }

    /** @test */
    public function can_cancel_schedule_creation()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-settings')
                    ->click('@add-schedule-button')
                    ->waitFor('@schedule-modal')
                    ->type('@schedule-name', 'Test Schedule')
                    ->click('@cancel-schedule-button')
                    ->waitUntilMissing('@schedule-modal')
                    ->assertDontSee('Test Schedule');
        });

        $this->assertDatabaseMissing('backup_schedules', [
            'name' => 'Test Schedule'
        ]);
    }

    /** @test */
    public function can_cancel_schedule_deletion()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);
        
        $schedule = BackupSchedule::create([
            'name' => 'Schedule to Keep',
            'type' => 'database',
            'frequency' => 'daily',
            'time' => '02:00',
            'retention_days' => 30,
            'is_active' => true
        ]);

        $this->browse(function (Browser $browser) use ($user, $schedule) {
            $browser->loginAs($user)
                    ->visit('/admin/backup-settings')
                    ->click("@delete-schedule-{$schedule->id}")
                    ->waitFor('@delete-confirmation-modal')
                    ->click('@cancel-delete-button')
                    ->waitUntilMissing('@delete-confirmation-modal')
                    ->assertSee('Schedule to Keep');
        });

        $this->assertDatabaseHas('backup_schedules', [
            'id' => $schedule->id,
            'name' => 'Schedule to Keep'
        ]);
    }
}