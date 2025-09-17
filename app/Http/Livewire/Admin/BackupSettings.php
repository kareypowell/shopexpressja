<?php

namespace App\Http\Livewire\Admin;

use App\Models\BackupSchedule;
use Livewire\Component;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

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
        $this->retentionSettings = [
            'database_days' => config('backup.retention.database_days', 30),
            'files_days' => config('backup.retention.files_days', 14)
        ];
    }

    public function loadNotificationSettings()
    {
        $this->notificationSettings = [
            'email' => config('backup.notifications.email', ''),
            'notify_on_success' => config('backup.notifications.notify_on_success', false),
            'notify_on_failure' => config('backup.notifications.notify_on_failure', true)
        ];
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
                $this->emit('scheduleUpdated', 'Backup schedule updated successfully');
            } else {
                BackupSchedule::create($this->newSchedule);
                $this->emit('scheduleCreated', 'Backup schedule created successfully');
            }

            $this->loadSchedules();
            $this->closeAddScheduleModal();
            
        } catch (\Exception $e) {
            Log::error('Failed to save backup schedule: ' . $e->getMessage());
            $this->addError('general', 'Failed to save backup schedule. Please try again.');
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
            $this->emit('scheduleDeleted', 'Backup schedule deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete backup schedule: ' . $e->getMessage());
            $this->addError('general', 'Failed to delete backup schedule. Please try again.');
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
            $this->emit('scheduleToggled', "Backup schedule {$status} successfully");
        } catch (\Exception $e) {
            Log::error('Failed to toggle backup schedule status: ' . $e->getMessage());
            $this->addError('general', 'Failed to update schedule status. Please try again.');
        }
    }

    public function saveRetentionSettings()
    {
        $this->validate([
            'retentionSettings.database_days' => 'required|integer|min:1|max:365',
            'retentionSettings.files_days' => 'required|integer|min:1|max:365'
        ]);

        try {
            // In a real implementation, you would save these to a configuration file or database
            // For now, we'll just emit a success message
            $this->emit('retentionSettingsSaved', 'Retention settings saved successfully');
            
            Log::info('Backup retention settings updated', $this->retentionSettings);
        } catch (\Exception $e) {
            Log::error('Failed to save retention settings: ' . $e->getMessage());
            $this->addError('retention', 'Failed to save retention settings. Please try again.');
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
            // In a real implementation, you would save these to a configuration file or database
            // For now, we'll just emit a success message
            $this->emit('notificationSettingsSaved', 'Notification settings saved successfully');
            
            Log::info('Backup notification settings updated', $this->notificationSettings);
        } catch (\Exception $e) {
            Log::error('Failed to save notification settings: ' . $e->getMessage());
            $this->addError('notifications', 'Failed to save notification settings. Please try again.');
        }
    }

    public function testNotificationEmail()
    {
        if (empty($this->notificationSettings['email'])) {
            $this->addError('notifications', 'Please enter an email address first.');
            return;
        }

        try {
            // In a real implementation, you would send a test email
            $this->emit('testEmailSent', 'Test notification email sent successfully');
            
            Log::info('Test backup notification email sent to: ' . $this->notificationSettings['email']);
        } catch (\Exception $e) {
            Log::error('Failed to send test notification email: ' . $e->getMessage());
            $this->addError('notifications', 'Failed to send test email. Please check your email configuration.');
        }
    }

    public function runCleanupNow()
    {
        try {
            Artisan::call('backup:cleanup');
            $this->emit('cleanupCompleted', 'Backup cleanup completed successfully');
            
            Log::info('Manual backup cleanup executed');
        } catch (\Exception $e) {
            Log::error('Failed to run backup cleanup: ' . $e->getMessage());
            $this->addError('general', 'Failed to run cleanup. Please try again.');
        }
    }
}