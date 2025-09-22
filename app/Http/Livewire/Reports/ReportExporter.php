<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use App\Services\ReportExportService;
use App\Models\ReportExportJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReportExporter extends Component
{
    // Export configuration
    public string $reportType = 'sales_collections';
    public array $reportData = [];
    public array $filters = [];
    
    // Export options
    public string $exportFormat = 'pdf';
    public array $availableFormats = [];
    public bool $includeCharts = true;
    public bool $includeRawData = true;
    public string $paperSize = 'A4';
    public string $orientation = 'portrait';
    
    // Export job tracking
    public array $activeExports = [];
    public bool $showExportDialog = false;
    public ?string $currentJobId = null;
    
    // UI state
    public bool $isExporting = false;
    public ?string $error = null;
    public ?string $successMessage = null;
    
    // Services
    protected ReportExportService $exportService;

    protected $listeners = [
        'exportReport' => 'handleExportRequest',
        'refreshExportStatus' => 'refreshActiveExports',
    ];

    public function boot(ReportExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    public function mount(string $reportType = 'sales_collections', array $reportData = [], array $filters = [])
    {
        $this->reportType = $reportType;
        $this->reportData = $reportData;
        $this->filters = $filters;
        
        $this->initializeExportOptions();
        $this->loadActiveExports();
    }

    /**
     * Initialize available export options
     */
    protected function initializeExportOptions(): void
    {
        $this->availableFormats = [
            'pdf' => [
                'name' => 'PDF Document',
                'description' => 'Formatted report with charts and styling',
                'icon' => 'document-text',
                'supports_charts' => true,
                'supports_options' => true,
            ],
            'csv' => [
                'name' => 'CSV Spreadsheet',
                'description' => 'Raw data in comma-separated format',
                'icon' => 'table',
                'supports_charts' => false,
                'supports_options' => false,
            ],
            'excel' => [
                'name' => 'Excel Workbook',
                'description' => 'Formatted spreadsheet with multiple sheets',
                'icon' => 'table',
                'supports_charts' => true,
                'supports_options' => true,
            ],
        ];
    }

    /**
     * Load active export jobs for current user
     */
    protected function loadActiveExports(): void
    {
        $user = Auth::user();
        if (!$user) return;

        $this->activeExports = ReportExportJob::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'report_type' => $job->report_type,
                    'export_format' => $job->export_format,
                    'status' => $job->status,
                    'progress' => $this->calculateProgress($job),
                    'created_at' => $job->created_at->format('M j, Y g:i A'),
                    'estimated_completion' => $this->estimateCompletion($job),
                ];
            })
            ->toArray();
    }

    /**
     * Handle export format change
     */
    public function updatedExportFormat($format): void
    {
        // Reset options that don't apply to selected format
        if (!$this->availableFormats[$format]['supports_charts']) {
            $this->includeCharts = false;
        }
        
        if (!$this->availableFormats[$format]['supports_options']) {
            $this->paperSize = 'A4';
            $this->orientation = 'portrait';
        }
    }

    /**
     * Start export process
     */
    public function startExport(): void
    {
        try {
            $this->isExporting = true;
            $this->error = null;
            
            // Validate export data
            if (empty($this->reportData)) {
                throw new \Exception('No report data available for export');
            }
            
            // Prepare export configuration
            $exportConfig = [
                'format' => $this->exportFormat,
                'include_charts' => $this->includeCharts,
                'include_raw_data' => $this->includeRawData,
                'paper_size' => $this->paperSize,
                'orientation' => $this->orientation,
                'report_type' => $this->reportType,
                'filters' => $this->filters,
            ];
            
            // Queue export job
            $jobId = $this->exportService->queueExport(
                $this->exportFormat,
                $this->reportData,
                Auth::user(),
                $exportConfig
            );
            
            $this->currentJobId = $jobId;
            $this->showExportDialog = false;
            $this->loadActiveExports();
            
            $this->successMessage = 'Export started successfully. You will be notified when it\'s ready.';
            $this->isExporting = false;
            
            // Start polling for status updates
            $this->dispatchBrowserEvent('startExportPolling', [
                'jobId' => $jobId,
                'interval' => 5000 // 5 seconds
            ]);
            
        } catch (\Exception $e) {
            Log::error('Export start error: ' . $e->getMessage());
            $this->error = $e->getMessage();
            $this->isExporting = false;
        }
    }

    /**
     * Handle export request from parent component
     */
    public function handleExportRequest(array $exportData): void
    {
        $this->reportType = $exportData['report_type'] ?? $this->reportType;
        $this->reportData = $exportData['data'] ?? [];
        $this->filters = $exportData['filters'] ?? [];
        
        // Apply any configuration from the request
        if (isset($exportData['config'])) {
            $config = $exportData['config'];
            $this->exportFormat = $config['format'] ?? $this->exportFormat;
            $this->includeCharts = $config['include_charts'] ?? $this->includeCharts;
            $this->includeRawData = $config['include_raw_data'] ?? $this->includeRawData;
        }
        
        $this->showExportDialog = true;
    }

    /**
     * Cancel export job
     */
    public function cancelExport(string $jobId): void
    {
        try {
            $job = ReportExportJob::where('id', $jobId)
                ->where('user_id', Auth::id())
                ->whereIn('status', ['pending', 'processing'])
                ->first();
                
            if ($job) {
                $job->update([
                    'status' => 'cancelled',
                    'error_message' => 'Cancelled by user',
                    'completed_at' => now(),
                ]);
                
                $this->loadActiveExports();
                
                $this->dispatchBrowserEvent('toastr:info', [
                    'message' => 'Export cancelled successfully'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Export cancel error: ' . $e->getMessage());
            $this->error = 'Failed to cancel export';
        }
    }

    /**
     * Download completed export
     */
    public function downloadExport(string $jobId): void
    {
        try {
            $job = ReportExportJob::where('id', $jobId)
                ->where('user_id', Auth::id())
                ->where('status', 'completed')
                ->first();
                
            if (!$job || !$job->file_path) {
                throw new \Exception('Export file not found');
            }
            
            // Generate secure download URL
            $downloadUrl = route('reports.download', [
                'job' => $job->id,
                'token' => $this->generateDownloadToken($job)
            ]);
            
            $this->dispatchBrowserEvent('downloadFile', [
                'url' => $downloadUrl,
                'filename' => $this->generateFilename($job)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Export download error: ' . $e->getMessage());
            $this->error = 'Failed to download export file';
        }
    }

    /**
     * Refresh active exports status
     */
    public function refreshActiveExports(): void
    {
        $this->loadActiveExports();
        
        // Check if any exports completed
        $completedExports = collect($this->activeExports)
            ->where('status', 'completed')
            ->count();
            
        if ($completedExports > 0) {
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => "{$completedExports} export(s) completed and ready for download"
            ]);
        }
    }

    /**
     * Get export status for a specific job
     */
    public function getExportStatus(string $jobId): array
    {
        try {
            return $this->exportService->getExportStatus($jobId);
        } catch (\Exception $e) {
            Log::error('Get export status error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to get status'];
        }
    }

    /**
     * Clear completed exports from the list
     */
    public function clearCompletedExports(): void
    {
        try {
            ReportExportJob::where('user_id', Auth::id())
                ->where('status', 'completed')
                ->where('created_at', '<', now()->subDays(7))
                ->delete();
                
            $this->loadActiveExports();
            
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Completed exports cleared'
            ]);
        } catch (\Exception $e) {
            Log::error('Clear exports error: ' . $e->getMessage());
            $this->error = 'Failed to clear completed exports';
        }
    }

    /**
     * Calculate export progress
     */
    protected function calculateProgress(ReportExportJob $job): int
    {
        switch ($job->status) {
            case 'pending':
                return 0;
            case 'processing':
                // Estimate based on time elapsed
                $elapsed = $job->started_at ? $job->started_at->diffInSeconds(now()) : 0;
                $estimated = $this->getEstimatedDuration($job);
                return min(90, ($elapsed / $estimated) * 100);
            case 'completed':
                return 100;
            default:
                return 0;
        }
    }

    /**
     * Estimate completion time
     */
    protected function estimateCompletion(ReportExportJob $job): string
    {
        if ($job->status === 'completed') {
            return 'Completed';
        }
        
        if ($job->status === 'pending') {
            return 'Waiting in queue';
        }
        
        $elapsed = $job->started_at ? $job->started_at->diffInSeconds(now()) : 0;
        $estimated = $this->getEstimatedDuration($job);
        $remaining = max(0, $estimated - $elapsed);
        
        if ($remaining < 60) {
            return 'Less than 1 minute';
        } elseif ($remaining < 3600) {
            return ceil($remaining / 60) . ' minutes';
        } else {
            return ceil($remaining / 3600) . ' hours';
        }
    }

    /**
     * Get estimated duration for export type
     */
    protected function getEstimatedDuration(ReportExportJob $job): int
    {
        $baseDuration = [
            'csv' => 30,    // 30 seconds
            'pdf' => 120,   // 2 minutes
            'excel' => 90,  // 1.5 minutes
        ];
        
        return $baseDuration[$job->export_format] ?? 60;
    }

    /**
     * Generate download token for security
     */
    protected function generateDownloadToken(ReportExportJob $job): string
    {
        return hash('sha256', $job->id . $job->user_id . $job->created_at->timestamp . config('app.key'));
    }

    /**
     * Generate filename for download
     */
    protected function generateFilename(ReportExportJob $job): string
    {
        $reportName = str_replace('_', '-', $job->report_type);
        $timestamp = $job->created_at->format('Y-m-d-H-i');
        $extension = $job->export_format === 'excel' ? 'xlsx' : $job->export_format;
        
        return "{$reportName}-{$timestamp}.{$extension}";
    }

    /**
     * Get available paper sizes for PDF export
     */
    public function getPaperSizes(): array
    {
        return [
            'A4' => 'A4 (210 × 297 mm)',
            'A3' => 'A3 (297 × 420 mm)',
            'Letter' => 'Letter (8.5 × 11 in)',
            'Legal' => 'Legal (8.5 × 14 in)',
        ];
    }

    /**
     * Get available orientations
     */
    public function getOrientations(): array
    {
        return [
            'portrait' => 'Portrait',
            'landscape' => 'Landscape',
        ];
    }

    /**
     * Check if user can export reports
     */
    public function canExport(): bool
    {
        return Auth::user()->can('exportReports', Auth::user());
    }

    /**
     * Get export format configuration
     */
    public function getFormatConfig(string $format): array
    {
        return $this->availableFormats[$format] ?? [];
    }

    public function render()
    {
        return view('livewire.reports.report-exporter', [
            'paperSizes' => $this->getPaperSizes(),
            'orientations' => $this->getOrientations(),
            'canExport' => $this->canExport(),
        ]);
    }
}