<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Backup;
use App\Models\Role;
use App\Services\BackupService;
use App\Services\BackupResult;
use App\Services\BackupStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Tests\TestCase;
use Mockery;

class BackupDashboardFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles if they don't exist
        Role::firstOrCreate(['name' => 'superadmin'], ['description' => 'Super Administrator']);
        Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);
        Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer']);
    }

    /**
     * Test backup dashboard component renders correctly
     */
    public function test_backup_dashboard_renders_correctly()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        // Mock BackupService
        $mockBackupService = Mockery::mock(BackupService::class);
        $mockBackupStatus = Mockery::mock(BackupStatus::class);
        
        $mockBackupStatus->shouldReceive('isHealthy')->andReturn(true);
        $mockBackupStatus->shouldReceive('getSuccessRate')->andReturn(95.0);
        $mockBackupStatus->shouldReceive('getRecentBackups')->andReturn(10);
        $mockBackupStatus->shouldReceive('getSuccessfulBackups')->andReturn(9);
        $mockBackupStatus->shouldReceive('getFormattedStorageUsage')->andReturn('1.2 GB');
        $mockBackupStatus->shouldReceive('getStorageFileCount')->andReturn(15);
        $mockBackupStatus->shouldReceive('getTimeSinceLastBackup')->andReturn('2 hours ago');
        $mockBackupStatus->shouldReceive('getLastBackup')->andReturn(null);
        $mockBackupStatus->shouldReceive('isStorageUsageHigh')->andReturn(false);
        
        $mockBackupService->shouldReceive('getBackupStatus')->andReturn($mockBackupStatus);
        $mockBackupService->shouldReceive('getBackupHistory')->andReturn(collect());
        
        $this->app->instance(BackupService::class, $mockBackupService);

        $this->actingAs($user)
             ->get('/admin/backup-dashboard')
             ->assertStatus(200)
             ->assertSee('Backup Management')
             ->assertSee('Monitor and manage system backups')
             ->assertSee('System Health')
             ->assertSee('Success Rate')
             ->assertSee('Storage Usage')
             ->assertSee('Last Backup');
    }

    /**
     * Test backup dashboard component initialization
     */
    public function test_backup_dashboard_component_initialization()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        // Mock BackupService
        $mockBackupService = Mockery::mock(BackupService::class);
        $mockBackupStatus = Mockery::mock(BackupStatus::class);
        
        $mockBackupStatus->shouldReceive('isHealthy')->andReturn(true);
        $mockBackupStatus->shouldReceive('getSuccessRate')->andReturn(100.0);
        $mockBackupStatus->shouldReceive('getRecentBackups')->andReturn(5);
        $mockBackupStatus->shouldReceive('getSuccessfulBackups')->andReturn(5);
        $mockBackupStatus->shouldReceive('getFormattedStorageUsage')->andReturn('500 MB');
        $mockBackupStatus->shouldReceive('getStorageFileCount')->andReturn(8);
        $mockBackupStatus->shouldReceive('getTimeSinceLastBackup')->andReturn('1 hour ago');
        $mockBackupStatus->shouldReceive('getLastBackup')->andReturn(null);
        $mockBackupStatus->shouldReceive('isStorageUsageHigh')->andReturn(false);
        
        $mockBackupService->shouldReceive('getBackupStatus')->andReturn($mockBackupStatus);
        $mockBackupService->shouldReceive('getBackupHistory')->andReturn(collect());
        
        $this->app->instance(BackupService::class, $mockBackupService);

        Livewire::actingAs($user)
                ->test(\App\Http\Livewire\Admin\BackupDashboard::class)
                ->assertSet('backupType', 'full')
                ->assertSet('customName', '')
                ->assertSet('showCreateModal', false)
                ->assertSet('isCreatingBackup', false)
                ->assertSee('Backup Management');
    }

    /**
     * Test create backup modal functionality
     */
    public function test_create_backup_modal_functionality()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        // Mock BackupService
        $mockBackupService = Mockery::mock(BackupService::class);
        $mockBackupStatus = Mockery::mock(BackupStatus::class);
        
        $mockBackupStatus->shouldReceive('isHealthy')->andReturn(true);
        $mockBackupStatus->shouldReceive('getSuccessRate')->andReturn(100.0);
        $mockBackupStatus->shouldReceive('getRecentBackups')->andReturn(0);
        $mockBackupStatus->shouldReceive('getSuccessfulBackups')->andReturn(0);
        $mockBackupStatus->shouldReceive('getFormattedStorageUsage')->andReturn('0 B');
        $mockBackupStatus->shouldReceive('getStorageFileCount')->andReturn(0);
        $mockBackupStatus->shouldReceive('getTimeSinceLastBackup')->andReturn(null);
        $mockBackupStatus->shouldReceive('getLastBackup')->andReturn(null);
        $mockBackupStatus->shouldReceive('isStorageUsageHigh')->andReturn(false);
        
        $mockBackupService->shouldReceive('getBackupStatus')->andReturn($mockBackupStatus);
        $mockBackupService->shouldReceive('getBackupHistory')->andReturn(collect());
        
        $this->app->instance(BackupService::class, $mockBackupService);

        Livewire::actingAs($user)
                ->test(\App\Http\Livewire\Admin\BackupDashboard::class)
                ->call('openCreateModal')
                ->assertSet('showCreateModal', true)
                ->assertSet('backupType', 'full')
                ->assertSet('customName', '')
                ->call('closeCreateModal')
                ->assertSet('showCreateModal', false);
    }

    /**
     * Test backup creation with valid data
     */
    public function test_backup_creation_with_valid_data()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        // Mock BackupService
        $mockBackupService = Mockery::mock(BackupService::class);
        $mockBackupStatus = Mockery::mock(BackupStatus::class);
        
        $mockBackupStatus->shouldReceive('isHealthy')->andReturn(true);
        $mockBackupStatus->shouldReceive('getSuccessRate')->andReturn(100.0);
        $mockBackupStatus->shouldReceive('getRecentBackups')->andReturn(0);
        $mockBackupStatus->shouldReceive('getSuccessfulBackups')->andReturn(0);
        $mockBackupStatus->shouldReceive('getFormattedStorageUsage')->andReturn('0 B');
        $mockBackupStatus->shouldReceive('getStorageFileCount')->andReturn(0);
        $mockBackupStatus->shouldReceive('getTimeSinceLastBackup')->andReturn(null);
        $mockBackupStatus->shouldReceive('getLastBackup')->andReturn(null);
        $mockBackupStatus->shouldReceive('isStorageUsageHigh')->andReturn(false);
        
        $mockBackup = Backup::factory()->make([
            'id' => 1,
            'name' => 'test_backup',
            'status' => 'completed',
        ]);
        
        $mockResult = new BackupResult(true, 'Backup created successfully', $mockBackup);
        
        $mockBackupService->shouldReceive('getBackupStatus')->andReturn($mockBackupStatus);
        $mockBackupService->shouldReceive('getBackupHistory')->andReturn(collect());
        $mockBackupService->shouldReceive('createManualBackup')
                         ->once()
                         ->with([
                             'type' => 'database',
                             'name' => 'test-backup',
                         ])
                         ->andReturn($mockResult);
        
        $this->app->instance(BackupService::class, $mockBackupService);

        Livewire::actingAs($user)
                ->test(\App\Http\Livewire\Admin\BackupDashboard::class)
                ->set('backupType', 'database')
                ->set('customName', 'test-backup')
                ->call('createBackup')
                ->assertHasNoErrors()
                ->assertSet('showCreateModal', false);
    }

    /**
     * Test backup creation with invalid custom name
     */
    public function test_backup_creation_with_invalid_custom_name()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        // Mock BackupService
        $mockBackupService = Mockery::mock(BackupService::class);
        $mockBackupStatus = Mockery::mock(BackupStatus::class);
        
        $mockBackupStatus->shouldReceive('isHealthy')->andReturn(true);
        $mockBackupStatus->shouldReceive('getSuccessRate')->andReturn(100.0);
        $mockBackupStatus->shouldReceive('getRecentBackups')->andReturn(0);
        $mockBackupStatus->shouldReceive('getSuccessfulBackups')->andReturn(0);
        $mockBackupStatus->shouldReceive('getFormattedStorageUsage')->andReturn('0 B');
        $mockBackupStatus->shouldReceive('getStorageFileCount')->andReturn(0);
        $mockBackupStatus->shouldReceive('getTimeSinceLastBackup')->andReturn(null);
        $mockBackupStatus->shouldReceive('getLastBackup')->andReturn(null);
        $mockBackupStatus->shouldReceive('isStorageUsageHigh')->andReturn(false);
        
        $mockBackupService->shouldReceive('getBackupStatus')->andReturn($mockBackupStatus);
        $mockBackupService->shouldReceive('getBackupHistory')->andReturn(collect());
        
        $this->app->instance(BackupService::class, $mockBackupService);

        Livewire::actingAs($user)
                ->test(\App\Http\Livewire\Admin\BackupDashboard::class)
                ->set('backupType', 'database')
                ->set('customName', 'invalid name with spaces!')
                ->call('createBackup')
                ->assertHasErrors(['customName']);
    }

    /**
     * Test backup creation failure handling
     */
    public function test_backup_creation_failure_handling()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        // Mock BackupService
        $mockBackupService = Mockery::mock(BackupService::class);
        $mockBackupStatus = Mockery::mock(BackupStatus::class);
        
        $mockBackupStatus->shouldReceive('isHealthy')->andReturn(true);
        $mockBackupStatus->shouldReceive('getSuccessRate')->andReturn(100.0);
        $mockBackupStatus->shouldReceive('getRecentBackups')->andReturn(0);
        $mockBackupStatus->shouldReceive('getSuccessfulBackups')->andReturn(0);
        $mockBackupStatus->shouldReceive('getFormattedStorageUsage')->andReturn('0 B');
        $mockBackupStatus->shouldReceive('getStorageFileCount')->andReturn(0);
        $mockBackupStatus->shouldReceive('getTimeSinceLastBackup')->andReturn(null);
        $mockBackupStatus->shouldReceive('getLastBackup')->andReturn(null);
        $mockBackupStatus->shouldReceive('isStorageUsageHigh')->andReturn(false);
        
        $mockResult = new BackupResult(false, 'Backup failed: Database connection error');
        
        $mockBackupService->shouldReceive('getBackupStatus')->andReturn($mockBackupStatus);
        $mockBackupService->shouldReceive('getBackupHistory')->andReturn(collect());
        $mockBackupService->shouldReceive('createManualBackup')
                         ->once()
                         ->andReturn($mockResult);
        
        $this->app->instance(BackupService::class, $mockBackupService);

        Livewire::actingAs($user)
                ->test(\App\Http\Livewire\Admin\BackupDashboard::class)
                ->set('backupType', 'database')
                ->call('createBackup')
                ->assertHasNoErrors();
    }

    /**
     * Test recent backups display
     */
    public function test_recent_backups_display()
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
        ]);

        $backup2 = Backup::factory()->create([
            'name' => 'test_backup_2',
            'status' => 'failed',
            'type' => 'database',
            'file_size' => 512 * 1024, // 512KB
            'created_by' => $user->id,
        ]);

        // Mock BackupService
        $mockBackupService = Mockery::mock(BackupService::class);
        $mockBackupStatus = Mockery::mock(BackupStatus::class);
        
        $mockBackupStatus->shouldReceive('isHealthy')->andReturn(true);
        $mockBackupStatus->shouldReceive('getSuccessRate')->andReturn(50.0);
        $mockBackupStatus->shouldReceive('getRecentBackups')->andReturn(2);
        $mockBackupStatus->shouldReceive('getSuccessfulBackups')->andReturn(1);
        $mockBackupStatus->shouldReceive('getFormattedStorageUsage')->andReturn('1.5 MB');
        $mockBackupStatus->shouldReceive('getStorageFileCount')->andReturn(2);
        $mockBackupStatus->shouldReceive('getTimeSinceLastBackup')->andReturn('1 hour ago');
        $mockBackupStatus->shouldReceive('getLastBackup')->andReturn($backup1);
        $mockBackupStatus->shouldReceive('isStorageUsageHigh')->andReturn(false);
        
        $recentBackups = collect([$backup1, $backup2]);
        
        $mockBackupService->shouldReceive('getBackupStatus')->andReturn($mockBackupStatus);
        $mockBackupService->shouldReceive('getBackupHistory')->andReturn($recentBackups);
        
        $this->app->instance(BackupService::class, $mockBackupService);

        Livewire::actingAs($user)
                ->test(\App\Http\Livewire\Admin\BackupDashboard::class)
                ->assertSee('test_backup_1')
                ->assertSee('test_backup_2')
                ->assertSee('Completed')
                ->assertSee('Failed');
    }

    /**
     * Test refresh functionality
     */
    public function test_refresh_functionality()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        // Mock BackupService
        $mockBackupService = Mockery::mock(BackupService::class);
        $mockBackupStatus = Mockery::mock(BackupStatus::class);
        
        $mockBackupStatus->shouldReceive('isHealthy')->andReturn(true);
        $mockBackupStatus->shouldReceive('getSuccessRate')->andReturn(100.0);
        $mockBackupStatus->shouldReceive('getRecentBackups')->andReturn(0);
        $mockBackupStatus->shouldReceive('getSuccessfulBackups')->andReturn(0);
        $mockBackupStatus->shouldReceive('getFormattedStorageUsage')->andReturn('0 B');
        $mockBackupStatus->shouldReceive('getStorageFileCount')->andReturn(0);
        $mockBackupStatus->shouldReceive('getTimeSinceLastBackup')->andReturn(null);
        $mockBackupStatus->shouldReceive('getLastBackup')->andReturn(null);
        $mockBackupStatus->shouldReceive('isStorageUsageHigh')->andReturn(false);
        
        $mockBackupService->shouldReceive('getBackupStatus')->andReturn($mockBackupStatus);
        $mockBackupService->shouldReceive('getBackupHistory')->andReturn(collect());
        
        $this->app->instance(BackupService::class, $mockBackupService);

        Livewire::actingAs($user)
                ->test(\App\Http\Livewire\Admin\BackupDashboard::class)
                ->call('refreshData')
                ->assertEmitted('refreshDashboard');
    }

    /**
     * Test access control - only superadmin can access
     */
    public function test_access_control_superadmin_only()
    {
        $customerRole = Role::where('name', 'customer')->first();
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($customer)
             ->get('/admin/backup-dashboard')
             ->assertStatus(403); // Should be forbidden
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}