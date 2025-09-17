<?php

namespace App\Http\Livewire\Admin;

use Livewire\Component;
use App\Services\BackupService;
use App\Services\BackupStatus;
use App\Services\BackupResult;
use App\Models\Backup;
use Illuminate\Support\Collection;
use Exception;

class BackupDashboard extends Component
{
    public $backupType = 'full';
    public $customName = '';
    public $showCreateModal = false;
    public $isCreatingBackup = false;
    public $refreshInterval = 30; // seconds
    
    protected $lastBackupResult = null;

    protected $listeners = ['refreshDashboard' => '$refresh'];

    protected $rules = [
        'backupType' => 'required|in:database,files,full',
        'customName' => 'nullable|string|max:100|regex:/^[a-zA-Z0-9_-]+$/',
    ];

    protected $messages = [
        'customName.regex' => 'Custom name can only contain letters, numbers, underscores, and hyphens.',
    ];

    public function mount()
    {
        // Initialize component
    }

    /**
     * Get current backup status and statistics
     */
    public function getBackupStatusProperty(): BackupStatus
    {
        return app(BackupService::class)->getBackupStatus();
    }

    /**
     * Get recent backup history
     */
    public function getRecentBackupsProperty(): Collection
    {
        return app(BackupService::class)->getBackupHistory(10);
    }

    /**
     * Get backup type options
     */
    public function getBackupTypesProperty()
    {
        return [
            'full' => 'Full Backup (Database + Files)',
            'database' => 'Database Only',
            'files' => 'Files Only',
        ];
    }

    /**
     * Open the create backup modal
     */
    public function openCreateModal()
    {
        $this->resetValidation();
        $this->backupType = 'full';
        $this->customName = '';
        $this->showCreateModal = true;
    }

    /**
     * Close the create backup modal
     */
    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->resetValidation();
        $this->backupType = 'full';
        $this->customName = '';
    }

    /**
     * Create a manual backup
     */
    public function createBackup()
    {
        $this->validate();

        $this->isCreatingBackup = true;
        $this->lastBackupResult = null;

        try {
            $backupService = app(BackupService::class);
            
            $options = [
                'type' => $this->backupType,
                'name' => $this->customName ?: null,
            ];

            $result = $backupService->createManualBackup($options);
            $this->lastBackupResult = $result;

            if ($result->isSuccessful()) {
                session()->flash('message', 'Backup created successfully: ' . $result->getMessage());
                $this->closeCreateModal();
            } else {
                session()->flash('error', 'Backup failed: ' . $result->getMessage());
            }

        } catch (Exception $e) {
            session()->flash('error', 'Backup creation failed: ' . $e->getMessage());
        } finally {
            $this->isCreatingBackup = false;
        }

        // Refresh the dashboard data
        $this->emit('refreshDashboard');
    }

    /**
     * Get status badge class for backup status
     */
    public function getStatusBadgeClass(string $status)
    {
        switch ($status) {
            case 'completed':
                return 'bg-green-100 text-green-800';
            case 'failed':
                return 'bg-red-100 text-red-800';
            case 'pending':
                return 'bg-yellow-100 text-yellow-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    /**
     * Get status icon for backup status
     */
    public function getStatusIcon(string $status)
    {
        switch ($status) {
            case 'completed':
                return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
            case 'failed':
                return 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z';
            case 'pending':
                return 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z';
            default:
                return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
        }
    }

    /**
     * Get health status badge class
     */
    public function getHealthBadgeClass(bool $isHealthy)
    {
        return $isHealthy 
            ? 'bg-green-100 text-green-800' 
            : 'bg-red-100 text-red-800';
    }

    /**
     * Get storage usage warning class
     */
    public function getStorageWarningClass()
    {
        $status = $this->backupStatus;
        
        if ($status->isStorageUsageHigh(2048)) { // 2GB threshold
            return 'text-red-600';
        } elseif ($status->isStorageUsageHigh(1024)) { // 1GB threshold
            return 'text-yellow-600';
        }
        
        return 'text-gray-600';
    }

    /**
     * Format file size for display
     */
    public function formatFileSize(?int $bytes)
    {
        if (!$bytes) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get backup type display name
     */
    public function getBackupTypeDisplay(string $type)
    {
        switch ($type) {
            case 'database':
                return 'Database';
            case 'files':
                return 'Files';
            case 'full':
                return 'Full';
            default:
                return ucfirst($type);
        }
    }

    /**
     * Refresh dashboard data
     */
    public function refreshData()
    {
        // This will trigger property re-computation
        $this->emit('refreshDashboard');
        session()->flash('message', 'Dashboard data refreshed.');
    }

    public function render()
    {
        return view('livewire.admin.backup-dashboard', [
            'backupStatus' => $this->backupStatus,
            'recentBackups' => $this->recentBackups,
            'backupTypes' => $this->backupTypes,
        ])->layout('layouts.app');
    }
}