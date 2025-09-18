<?php

namespace App\Http\Livewire\Admin;

use App\Models\BackupSchedule;
use App\Models\BackupSetting;
use App\Services\BackupSettingsService;
use App\Mail\BackupTestNotification;
use Livewire\Component;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BackupSettings extends Component
{
    public $schedules = [];
    public $newSchedule = [
        'name' => '',
        'type' => 'full',
        'frequency' => 'daily',
        'time' => '02:00',
        'is_active' => true,
        'retention_days' => 30
    ];
    
    public $retentionSettings = [
        'database_days' => 30,
        'files_days' => 14
    ];
    
    public $notificationSettings = [
        'email' => '',
        'notify_on_success' => false,
        'notify_on_failure' => true
    ];
    
    public $showAddScheduleModal = false;
    public $editingScheduleId = null;
    public $showDeleteConfirm = null;
    public $isLoading = false;

    protected $rules = [
        'newSchedule.name' => 'required|string|max:255',
        'newSchedule.type' => 'required|in:database,files,full',
        'newSchedule.frequency' => 'required|in:daily,weekly,monthly',
        'newSchedule.time' => 'required|date_format:H:i',
        'newSchedule.is_active' => 'boolean',
        'newSchedule.retention_days' => 'required|integer|min:1|max:365',
        'retentionSettings.database_days' => 'required|integer|min:1|max:365',
        'retentionSettings.files_days' => 'required|integer|min:1|max:365',
        'notificationSettings.email' => 'nullable|email',
        'notificationSettings.notify_on_success' => 'boolean',
        'notificationSettings.notify_on_failure' => 'boolean'
    ];

    public function mount()
    {
        $this->loadSchedules();
        $this->loadRetentionSettings();
        $this->loadNotificationSettings();
    }

    public function render()
    {
        return view('livewire.admin.backup-settings');
    }

    public function loadSchedules()
    {
        $this->schedules = BackupSchedule::orderBy('created_at', 'desc')->get()->toArray();
    }

    public function loadRetentionSettings()
    {
        $this->retentionSettings = BackupSettingsService::getRetentionSettings();
    }

    public function loadNotificationSettings()
    {
        $this->notificationSettings = BackupSettingsService::getNotificationSettings();
    }

    public function getBackupDirectories()
    {
        return config('backup.files.directories', []);
    }

    public function getExcludePatterns()
    {
        return config('backup.files.exclude_patterns', []);
    }

    public function openAddScheduleModal()
    {
        $this->resetNewSchedule();
        $this->showAddScheduleModal = true;
    }

    public function closeAddScheduleModal()
    {
        $this->showAddScheduleModal = false;
        $this->editingScheduleId = null;
        $this->resetErrorBag();
    }

    public function resetNewSchedule()
    {
        $this->newSchedule = [
            'name' => '',
            'type' => 'full',
            'frequency' => 'daily',
            'time' => '02:00',
            'is_active' => true,
            'retention_days' => 30
        ];
    }

    public function saveSchedule()
    {
        $this->validate([
            'newSchedule.name' => 'required|string|max:255',
            'newSchedule.type' => 'required|in:database,files,full',
            'newSchedule.frequency' => 'required|in:daily,weekly,monthly',
            'newSchedule.time' => 'required|date_format:H:i',
            'newSchedule.is_active' => 'boolean',
            'newSchedule.retention_days' => 'required|integer|min:1|max:365'
        ]);

        try {
            if ($this->editingScheduleId) {
                $schedule = BackupSchedule::findOrFail($this->editingScheduleId);
                $schedule->update($this->newSchedule);
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => 'Backup schedule updated successfully'
                ]);
            } else {
                BackupSchedule::create($this->newSchedule);
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => 'Backup schedule created successfully'
                ]);
            }

            $this->loadSchedules();
            $this->closeAddScheduleModal();
            
        } catch (\Exception $e) {
            Log::error('Failed to save backup schedule: ' . $e->getMessage());
            $this->addError('general', 'Failed to save backup schedule. Please try again.');
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to save backup schedule. Please try again.'
            ]);
        }
    }

    public function editSchedule($scheduleId)
    {
        $schedule = BackupSchedule::findOrFail($scheduleId);
        $this->newSchedule = $schedule->toArray();
        $this->editingScheduleId = $scheduleId;
        $this->showAddScheduleModal = true;
    }

    public function confirmDeleteSchedule($scheduleId)
    {
        $this->showDeleteConfirm = $scheduleId;
    }

    public function deleteSchedule()
    {
        try {
            BackupSchedule::findOrFail($this->showDeleteConfirm)->delete();
            $this->loadSchedules();
            $this->showDeleteConfirm = null;
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Backup schedule deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete backup schedule: ' . $e->getMessage());
            $this->addError('general', 'Failed to delete backup schedule. Please try again.');
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to delete backup schedule. Please try again.'
            ]);
        }
    }

    public function cancelDelete()
    {
        $this->showDeleteConfirm = null;
    }

    public function toggleScheduleStatus($scheduleId)
    {
        try {
            $schedule = BackupSchedule::findOrFail($scheduleId);
            $schedule->update(['is_active' => !$schedule->is_active]);
            $this->loadSchedules();
            
            $status = $schedule->is_active ? 'activated' : 'deactivated';
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => "Backup schedule {$status} successfully"
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to toggle backup schedule status: ' . $e->getMessage());
            $this->addError('general', 'Failed to update schedule status. Please try again.');
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to update schedule status. Please try again.'
            ]);
        }
    }

    public function saveRetentionSettings()
    {
        $this->isLoading = true;
        
        $this->validate([
            'retentionSettings.database_days' => 'required|integer|min:1|max:365',
            'retentionSettings.files_days' => 'required|integer|min:1|max:365'
        ]);

        try {
            // Save retention settings to database
            BackupSetting::set(
                'retention.database_days', 
                $this->retentionSettings['database_days'], 
                'integer',
                'Number of days to retain database backups'
            );
            
            BackupSetting::set(
                'retention.files_days', 
                $this->retentionSettings['files_days'], 
                'integer',
                'Number of days to retain file backups'
            );
            
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Retention settings saved successfully'
            ]);
            
            Log::info('Backup retention settings updated', $this->retentionSettings);
            
            // Reload settings to ensure UI reflects saved values
            $this->loadRetentionSettings();
        } catch (\Exception $e) {
            Log::error('Failed to save retention settings: ' . $e->getMessage());
            $this->addError('retention', 'Failed to save retention settings. Please try again.');
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to save retention settings. Please try again.'
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    public function saveNotificationSettings()
    {
        $this->validate([
            'notificationSettings.email' => 'nullable|email',
            'notificationSettings.notify_on_success' => 'boolean',
            'notificationSettings.notify_on_failure' => 'boolean'
        ]);

        try {
            // Save notification settings to database
            BackupSetting::set(
                'notifications.email', 
                $this->notificationSettings['email'], 
                'string',
                'Email address for backup notifications'
            );
            
            BackupSetting::set(
                'notifications.notify_on_success', 
                $this->notificationSettings['notify_on_success'], 
                'boolean',
                'Send notifications for successful backups'
            );
            
            BackupSetting::set(
                'notifications.notify_on_failure', 
                $this->notificationSettings['notify_on_failure'], 
                'boolean',
                'Send notifications for failed backups'
            );
            
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Notification settings saved successfully'
            ]);
            
            Log::info('Backup notification settings updated', $this->notificationSettings);
            
            // Reload settings to ensure UI reflects saved values
            $this->loadNotificationSettings();
        } catch (\Exception $e) {
            Log::error('Failed to save notification settings: ' . $e->getMessage());
            $this->addError('notifications', 'Failed to save notification settings. Please try again.');
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to save notification settings. Please try again.'
            ]);
        }
    }

    public function testNotificationEmail()
    {
        if (empty($this->notificationSettings['email'])) {
            $this->addError('notifications', 'Please enter an email address first.');
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Please enter an email address first.'
            ]);
            return;
        }

        try {
            // Send actual test email
            Mail::to($this->notificationSettings['email'])
                ->send(new BackupTestNotification());
            
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Test notification email sent successfully'
            ]);
            
            Log::info('Test backup notification email sent to: ' . $this->notificationSettings['email']);
        } catch (\Exception $e) {
            Log::error('Failed to send test notification email: ' . $e->getMessage());
            $this->addError('notifications', 'Failed to send test email. Please check your email configuration.');
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to send test email. Please check your email configuration.'
            ]);
        }
    }

    public function runCleanupNow()
    {
        try {
            Artisan::call('backup:cleanup');
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Backup cleanup completed successfully'
            ]);
            
            Log::info('Manual backup cleanup executed');
        } catch (\Exception $e) {
            Log::error('Failed to run backup cleanup: ' . $e->getMessage());
            $this->addError('general', 'Failed to run cleanup. Please try again.');
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to run cleanup. Please try again.'
            ]);
        }
    }
}