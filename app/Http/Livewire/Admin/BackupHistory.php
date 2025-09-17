<?php

namespace App\Http\Livewire\Admin;

use App\Models\Backup;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BackupHistory extends Component
{
    use WithPagination;

    public $search = '';
    public $typeFilter = '';
    public $statusFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $selectedBackups = [];
    public $showFilters = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount()
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingTypeFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
        $this->resetPage();
    }

    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->typeFilter = '';
        $this->statusFilter = '';
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function generateDownloadLink($backupId)
    {
        try {
            $backup = Backup::findOrFail($backupId);
            
            // Parse file path (could be JSON or single path)
            $filePaths = $this->parseBackupFilePaths($backup->file_path);
            
            if (empty($filePaths)) {
                $this->addError('download', 'No backup files found.');
                return;
            }

            // Verify at least one file exists
            $existingFiles = [];
            foreach ($filePaths as $path) {
                if (file_exists($path)) {
                    $existingFiles[] = $path;
                }
            }

            if (empty($existingFiles)) {
                $this->addError('download', 'Backup file not found.');
                return;
            }

            // Generate secure, time-limited download URL (valid for 1 hour)
            $downloadUrl = URL::temporarySignedRoute(
                'backup.download',
                now()->addHour(),
                ['backup' => $backup->id]
            );

            // Log download access for security auditing
            Log::info('Backup download link generated', [
                'backup_id' => $backup->id,
                'backup_name' => $backup->name,
                'file_count' => count($existingFiles),
                'user_id' => auth()->id(),
                'user_email' => auth()->user()->email,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Redirect to download URL
            return redirect($downloadUrl);

        } catch (\Exception $e) {
            Log::error('Failed to generate backup download link', [
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            $this->addError('download', 'Failed to generate download link. Please try again.');
        }
    }

    public function generateBatchDownloadLinks()
    {
        if (empty($this->selectedBackups)) {
            $this->addError('batch_download', 'Please select at least one backup to download.');
            return;
        }

        try {
            $backups = Backup::whereIn('id', $this->selectedBackups)
                ->where('status', 'completed')
                ->get();

            if ($backups->isEmpty()) {
                $this->addError('batch_download', 'No valid backups selected for download.');
                return;
            }

            $downloadLinks = [];
            foreach ($backups as $backup) {
                $filePaths = $this->parseBackupFilePaths($backup->file_path);
                $hasValidFiles = false;
                
                foreach ($filePaths as $path) {
                    if (file_exists($path)) {
                        $hasValidFiles = true;
                        break;
                    }
                }
                
                if ($hasValidFiles) {
                    $downloadLinks[] = [
                        'name' => $backup->name,
                        'url' => URL::temporarySignedRoute(
                            'backup.download',
                            now()->addHour(),
                            ['backup' => $backup->id]
                        )
                    ];
                }
            }

            // Log batch download access
            Log::info('Batch backup download links generated', [
                'backup_ids' => $this->selectedBackups,
                'backup_count' => count($downloadLinks),
                'user_id' => auth()->id(),
                'user_email' => auth()->user()->email,
                'ip_address' => request()->ip(),
            ]);

            // Emit event to frontend to handle multiple downloads
            $this->emit('batchDownloadReady', $downloadLinks);
            $this->selectedBackups = [];

        } catch (\Exception $e) {
            Log::error('Failed to generate batch download links', [
                'backup_ids' => $this->selectedBackups,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            $this->addError('batch_download', 'Failed to generate download links. Please try again.');
        }
    }

    public function deleteBackup($backupId)
    {
        try {
            $backup = Backup::findOrFail($backupId);
            
            // Delete physical files
            $filePaths = $this->parseBackupFilePaths($backup->file_path);
            $deletedFiles = [];
            
            foreach ($filePaths as $path) {
                if (file_exists($path)) {
                    unlink($path);
                    $deletedFiles[] = $path;
                }
            }

            // Delete database record
            $backup->delete();

            // Log deletion
            Log::info('Backup deleted', [
                'backup_id' => $backup->id,
                'backup_name' => $backup->name,
                'deleted_files' => $deletedFiles,
                'deleted_by' => auth()->id(),
            ]);

            session()->flash('message', 'Backup deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete backup', [
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            $this->addError('delete', 'Failed to delete backup. Please try again.');
        }
    }

    public function getBackupsProperty()
    {
        $query = Backup::query();

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('file_path', 'like', '%' . $this->search . '%');
            });
        }

        // Apply type filter
        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }

        // Apply status filter
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // Apply date range filter
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate(20);
    }

    public function render()
    {
        return view('livewire.admin.backup-history', [
            'backups' => $this->backups,
            'totalBackups' => Backup::count(),
            'completedBackups' => Backup::where('status', 'completed')->count(),
            'failedBackups' => Backup::where('status', 'failed')->count(),
            'totalSize' => $this->formatBytes(
                Backup::where('status', 'completed')->sum('file_size')
            ),
        ]);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function parseBackupFilePaths($filePath)
    {
        // Try to decode as JSON first
        $paths = json_decode($filePath, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($paths)) {
            // Handle JSON format: {"database": "path", "files": ["path1", "path2"]}
            $allPaths = [];
            
            foreach ($paths as $type => $typePaths) {
                if (is_array($typePaths)) {
                    $allPaths = array_merge($allPaths, $typePaths);
                } else {
                    $allPaths[] = $typePaths;
                }
            }
            
            return $allPaths;
        }
        
        // Handle single file path (legacy format)
        return [$filePath];
    }
}