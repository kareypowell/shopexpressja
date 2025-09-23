<?php

namespace App\Services;

use App\Jobs\ProcessReportExportJob;
use App\Models\ReportExportJob;
use App\Models\User;
use App\Traits\HandlesReportErrors;
use App\Exceptions\ReportException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

class ReportExportService
{
    use HandlesReportErrors;
    protected $pdfGenerator;
    protected ReportPrivacyService $privacyService;
    protected ReportAuditService $auditService;
    
    public function __construct(
        PdfReportGenerator $pdfGenerator,
        ReportPrivacyService $privacyService,
        ReportAuditService $auditService
    ) {
        $this->pdfGenerator = $pdfGenerator;
        $this->privacyService = $privacyService;
        $this->auditService = $auditService;
    }

    /**
     * Export report to PDF format
     */
    public function exportToPdf(array $reportData, string $template, array $options = [], ?User $user = null): string
    {
        return $this->executeWithErrorHandling(function() use ($reportData, $template, $options, $user) {
            // Validate export permissions
            if ($user && !$this->privacyService->validateExportPermissions($user, $reportData)) {
                throw new ReportException(
                    'User does not have permission to export sensitive data',
                    'export',
                    ['template' => $template, 'user_id' => $user->id],
                    1003
                );
            }
            
            // Apply privacy protection if user is provided
            if ($user) {
                // Apply privacy protection to data
                $reportData = $this->privacyService->applyPrivacyProtection($reportData, $user, 'pdf');
                
                // Add privacy disclaimer
                $options['disclaimer'] = $this->privacyService->getExportDisclaimer($user, $template);
            }
            
            $filename = $this->generateFilename('pdf', $options);
            $filePath = "exports/pdf/{$filename}";
            
            // Generate PDF content using the PdfReportGenerator
            $pdfContent = $this->pdfGenerator->generateReport($reportData, $template, $options);
            
            // Store the PDF file
            Storage::disk('local')->put($filePath, $pdfContent);
            
            return $filePath;
        } catch (Exception $e) {
            throw new \App\Exceptions\ExportException('PDF', $e->getMessage());
        }
    }

    /**
     * Export report to CSV format
     */
    public function exportToCsv(array $reportData, array $headers, array $options = [], ?User $user = null): string
    {
        try {
            // Apply privacy protection if user is provided
            if ($user) {
                // Validate export permissions
                if (!$this->privacyService->validateExportPermissions($user, $reportData)) {
                    throw new Exception('User does not have permission to export sensitive data');
                }
                
                // Apply privacy protection to data
                $reportData = $this->privacyService->applyPrivacyProtection($reportData, $user, 'csv');
                
                // Sanitize data for CSV export
                $reportData = $this->privacyService->sanitizeForExport($reportData, 'csv');
                
                // Add privacy notice to headers
                $privacyNotice = $this->privacyService->getPrivacyNotice('csv', $user);
                $headers = array_merge($privacyNotice, [''], $headers); // Add empty line separator
            }
            
            $filename = $this->generateFilename('csv', $options);
            $filePath = "exports/csv/{$filename}";
            
            // Create CSV export using native implementation
            $csvExport = new ReportCsvExport($reportData, $headers, $options['report_type'] ?? 'general');
            $csvExport->store($filePath);
            
            return $filePath;
        } catch (Exception $e) {
            throw new \App\Exceptions\ExportException('CSV', $e->getMessage());
        }
    }

    /**
     * Queue export job for background processing
     */
    public function queueExport(string $type, array $data, User $user, array $options = []): string
    {
        // Validate export permissions
        if (isset($data['report_data']) && !$this->privacyService->validateExportPermissions($user, $data['report_data'])) {
            throw new Exception('User does not have permission to export sensitive data');
        }
        
        // Create export job record
        $exportJob = ReportExportJob::create([
            'user_id' => $user->id,
            'report_type' => $data['report_type'] ?? 'unknown',
            'export_format' => strtoupper($type),
            'filters' => $data['filters'] ?? [],
            'status' => 'queued',
            'started_at' => now(),
        ]);

        // Log export request for audit
        if (isset($options['request'])) {
            $containsSensitive = isset($data['report_data']) ? 
                $this->privacyService->containsSensitiveData($data['report_data']) : false;
                
            $this->auditService->logReportExport(
                $data['report_type'] ?? 'unknown',
                $type,
                $user,
                $options['request'],
                $data['filters'] ?? [],
                $containsSensitive
            );
        }

        // Dispatch background job
        ProcessReportExportJob::dispatch($exportJob->id, $type, $data, $options);

        return $exportJob->id;
    }

    /**
     * Get export job status and details
     */
    public function getExportStatus(string $jobId): array
    {
        $job = ReportExportJob::find($jobId);
        
        if (!$job) {
            return [
                'status' => 'not_found',
                'message' => 'Export job not found'
            ];
        }

        $response = [
            'status' => $job->status,
            'progress' => $this->calculateProgress($job),
            'created_at' => $job->created_at,
            'started_at' => $job->started_at,
            'completed_at' => $job->completed_at,
        ];

        if ($job->status === 'completed' && $job->file_path) {
            $response['download_url'] = $this->generateDownloadUrl($job);
            $response['file_size'] = $this->getFileSize($job->file_path);
        }

        if ($job->status === 'failed') {
            $response['error_message'] = $job->error_message;
        }

        return $response;
    }

    /**
     * Process export synchronously (for smaller datasets)
     */
    public function processExportSync(string $type, array $data, User $user, array $options = []): array
    {
        try {
            switch(strtolower($type)) {
                case 'pdf':
                    $filePath = $this->exportToPdf($data, $options['template'] ?? 'default', $options);
                    break;
                case 'csv':
                    $filePath = $this->exportToCsv($data, $options['headers'] ?? [], $options);
                    break;
                default:
                    throw new Exception("Unsupported export type: {$type}");
            }

            return [
                'success' => true,
                'file_path' => $filePath,
                'download_url' => $this->generateDirectDownloadUrl($filePath),
                'file_size' => $this->getFileSize($filePath)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clean up old export files
     */
    public function cleanupOldExports(int $daysOld = 7): int
    {
        $cutoffDate = now()->subDays($daysOld);
        $deletedCount = 0;

        // Get old export jobs
        $oldJobs = ReportExportJob::where('created_at', '<', $cutoffDate)
            ->whereNotNull('file_path')
            ->get();

        foreach ($oldJobs as $job) {
            if (Storage::disk('local')->exists($job->file_path)) {
                Storage::disk('local')->delete($job->file_path);
                $deletedCount++;
            }
            
            // Clear file path from job record
            $job->update(['file_path' => null]);
        }

        return $deletedCount;
    }

    /**
     * Generate unique filename for export
     */
    protected function generateFilename(string $extension, array $options = []): string
    {
        $prefix = $options['filename_prefix'] ?? 'report';
        $timestamp = now()->format('Y-m-d_H-i-s');
        $uuid = Str::uuid()->toString();
        
        return "{$prefix}_{$timestamp}_{$uuid}.{$extension}";
    }

    /**
     * Calculate export progress percentage
     */
    protected function calculateProgress(ReportExportJob $job): int
    {
        switch($job->status) {
            case 'queued':
                return 0;
            case 'processing':
                return 50;
            case 'completed':
                return 100;
            case 'failed':
                return 0;
            default:
                return 0;
        }
    }

    /**
     * Generate secure download URL for completed export
     */
    protected function generateDownloadUrl(ReportExportJob $job): string
    {
        return route('reports.download', [
            'job' => $job->id,
            'token' => $this->generateDownloadToken($job)
        ]);
    }

    /**
     * Generate direct download URL for sync exports
     */
    protected function generateDirectDownloadUrl(string $filePath): string
    {
        return route('reports.download.direct', [
            'path' => encrypt($filePath),
            'expires' => now()->addHours(24)->timestamp
        ]);
    }

    /**
     * Generate secure download token
     */
    protected function generateDownloadToken(ReportExportJob $job): string
    {
        return hash_hmac('sha256', $job->id . $job->file_path, config('app.key'));
    }

    /**
     * Get file size in human readable format
     */
    protected function getFileSize(string $filePath): string
    {
        if (!Storage::disk('local')->exists($filePath)) {
            return 'Unknown';
        }

        $bytes = Storage::disk('local')->size($filePath);
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Validate export request
     */
    public function validateExportRequest(array $data, string $type): array
    {
        $errors = [];

        // Validate export type
        if (!in_array(strtolower($type), ['pdf', 'csv'])) {
            $errors[] = 'Invalid export type. Must be PDF or CSV.';
        }

        // Validate report data
        if (empty($data)) {
            $errors[] = 'Report data cannot be empty.';
        }

        // Validate data size for sync processing
        if (isset($data['records']) && count($data['records']) > 10000) {
            $errors[] = 'Dataset too large for synchronous processing. Use queue export instead.';
        }

        return $errors;
    }
}