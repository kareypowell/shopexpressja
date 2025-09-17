<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\BackupSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BackupSettingsFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::firstOrCreate(['name' => 'superadmin'], ['description' => 'Super Administrator']);
        Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);
        Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer']);
    }

    /** @test */
    public function superadmin_can_access_backup_settings_page()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        $response = $this->actingAs($user)->get('/admin/backup-settings');

        $response->assertStatus(200);
        $response->assertSee('Backup Settings');
        $response->assertSee('Configure automated backup schedules, retention policies, and notifications');
    }

    /** @test */
    public function admin_cannot_access_backup_settings_page()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $user = User::factory()->create(['role_id' => $adminRole->id]);

        $response = $this->actingAs($user)->get('/admin/backup-settings');

        $response->assertStatus(403);
    }

    /** @test */
    public function customer_cannot_access_backup_settings_page()
    {
        $customerRole = Role::where('name', 'customer')->first();
        $user = User::factory()->create(['role_id' => $customerRole->id]);

        $response = $this->actingAs($user)->get('/admin/backup-settings');

        $response->assertStatus(403);
    }

    /** @test */
    public function can_create_new_backup_schedule()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Admin\BackupSettings::class)
            ->set('newSchedule.name', 'Daily Database Backup')
            ->set('newSchedule.type', 'database')
            ->set('newSchedule.frequency', 'daily')
            ->set('newSchedule.time', '02:00')
            ->set('newSchedule.retention_days', 30)
            ->set('newSchedule.is_active', true)
            ->call('saveSchedule')
            ->assertEmitted('scheduleCreated');

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

        Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Admin\BackupSettings::class)
            ->call('editSchedule', $schedule->id)
            ->set('newSchedule.name', 'Updated Schedule')
            ->set('newSchedule.type', 'full')
            ->set('newSchedule.frequency', 'weekly')
            ->set('newSchedule.time', '03:00')
            ->set('newSchedule.retention_days', 60)
            ->call('saveSchedule')
            ->assertEmitted('scheduleUpdated');

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

        Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Admin\BackupSettings::class)
            ->call('confirmDeleteSchedule', $schedule->id)
            ->call('deleteSchedule')
            ->assertEmitted('scheduleDeleted');

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

        Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Admin\BackupSettings::class)
            ->call('toggleScheduleStatus', $schedule->id)
            ->assertEmitted('scheduleToggled');

        $this->assertDatabaseHas('backup_schedules', [
            'id' => $schedule->id,
            'is_active' => false
        ]);
    }

    /** @test */
    public function can_save_retention_settings()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Admin\BackupSettings::class)
            ->set('retentionSettings.database_days', 45)
            ->set('retentionSettings.files_days', 21)
            ->call('saveRetentionSettings')
            ->assertEmitted('retentionSettingsSaved');
    }

    /** @test */
    public function can_save_notification_settings()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Admin\BackupSettings::class)
            ->set('notificationSettings.email', 'admin@example.com')
            ->set('notificationSettings.notify_on_success', true)
            ->set('notificationSettings.notify_on_failure', false)
            ->call('saveNotificationSettings')
            ->assertEmitted('notificationSettingsSaved');
    }

    /** @test */
    public function can_send_test_notification_email()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Admin\BackupSettings::class)
            ->set('notificationSettings.email', 'test@example.com')
            ->call('testNotificationEmail')
            ->assertEmitted('testEmailSent');
    }

    /** @test */
    public function can_run_cleanup_now()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Admin\BackupSettings::class)
            ->call('runCleanupNow')
            ->assertEmitted('cleanupCompleted');
    }

    /** @test */
    public function validates_schedule_form_fields()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Admin\BackupSettings::class)
            ->set('newSchedule.name', '')
            ->call('saveSchedule')
            ->assertHasErrors(['newSchedule.name' => 'required']);

        Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Admin\BackupSettings::class)
            ->set('newSchedule.name', 'Test Schedule')
            ->set('newSchedule.retention_days', 0)
            ->call('saveSchedule')
            ->assertHasErrors(['newSchedule.retention_days' => 'min']);
    }

    /** @test */
    public function validates_retention_settings_form()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Admin\BackupSettings::class)
            ->set('retentionSettings.database_days', 0)
            ->call('saveRetentionSettings')
            ->assertHasErrors(['retentionSettings.database_days' => 'min']);
    }

    /** @test */
    public function validates_notification_email_format()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Admin\BackupSettings::class)
            ->set('notificationSettings.email', 'invalid-email')
            ->call('saveNotificationSettings')
            ->assertHasErrors(['notificationSettings.email' => 'email']);
    }

    /** @test */
    public function loads_schedules_on_mount()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);
        
        $schedule1 = BackupSchedule::create([
            'name' => 'Schedule 1',
            'type' => 'database',
            'frequency' => 'daily',
            'time' => '02:00',
            'retention_days' => 30,
            'is_active' => true
        ]);

        $schedule2 = BackupSchedule::create([
            'name' => 'Schedule 2',
            'type' => 'files',
            'frequency' => 'weekly',
            'time' => '03:00',
            'retention_days' => 14,
            'is_active' => false
        ]);

        $component = Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Admin\BackupSettings::class);

        $this->assertCount(2, $component->get('schedules'));
        $this->assertEquals('Schedule 1', $component->get('schedules')[0]['name']);
        $this->assertEquals('Schedule 2', $component->get('schedules')[1]['name']);
    }

    /** @test */
    public function opens_and_closes_add_schedule_modal()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Admin\BackupSettings::class)
            ->assertSet('showAddScheduleModal', false)
            ->call('openAddScheduleModal')
            ->assertSet('showAddScheduleModal', true)
            ->call('closeAddScheduleModal')
            ->assertSet('showAddScheduleModal', false);
    }

    /** @test */
    public function resets_form_when_opening_add_schedule_modal()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);

        Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Admin\BackupSettings::class)
            ->set('newSchedule.name', 'Some Name')
            ->call('openAddScheduleModal')
            ->assertSet('newSchedule.name', '')
            ->assertSet('newSchedule.type', 'full')
            ->assertSet('newSchedule.frequency', 'daily')
            ->assertSet('newSchedule.time', '02:00')
            ->assertSet('newSchedule.is_active', true)
            ->assertSet('newSchedule.retention_days', 30);
    }

    /** @test */
    public function shows_delete_confirmation_modal()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create(['role_id' => $superadminRole->id]);
        
        $schedule = BackupSchedule::create([
            'name' => 'Test Schedule',
            'type' => 'database',
            'frequency' => 'daily',
            'time' => '02:00',
            'retention_days' => 30,
            'is_active' => true
        ]);

        Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Admin\BackupSettings::class)
            ->assertSet('showDeleteConfirm', null)
            ->call('confirmDeleteSchedule', $schedule->id)
            ->assertSet('showDeleteConfirm', $schedule->id)
            ->call('cancelDelete')
            ->assertSet('showDeleteConfirm', null);
    }
}