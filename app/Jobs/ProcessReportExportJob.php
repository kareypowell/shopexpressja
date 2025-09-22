<?php

namespace App\Jobs;

use App\Models\ReportExportJob;
use App\Services\ReportExportService;
use App\Services\PdfReportGenerator;
use App\Exports\ReportCsvExport;
use App\Notifications\ReportExportCompletedNotification;
use App\Notifications\ReportExportFailedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Exception;
use Throwable;

class ProcessReportExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $exportJobId;
    protected $exportType;
    protected $reportData;
    protected $options;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(string $exportJobId, string $exportType, array $reportData, array $options = [])
    {
        $this->exportJobId = $exportJobId;
        $this->exportType = strtolower($exportType);
        $this->reportData = $reportData;
        $this->options = $options;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $exportJob = ReportExportJob::find($this->exportJobId);
        
        if (!$exportJob) {
            throw new Exception("Export job {$this->exportJobId} not found");
        }

        try {
            // Update status to processing
            $exportJob->update([
                'status' => 'processing',
                'started_at' => now()
            ]);

            // Process the export based on type
            $filePath = $this->processExport();

            // Update job with success status
            $exportJob->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'completed_at' => now()
            ]);

            // Notify user of completion
            $exportJob->user->notify(new ReportExportCompletedNotification($exportJob));

        } catch (Exception $e) {
            // Update job with failure status
            $exportJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now()
            ]);

            // Notify user of failure
            $exportJob->user->notify(new ReportExportFailedNotification($exportJob, $e->getMessage()));

            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(Throwable $exception)
    {
        $exportJob = ReportExportJob::find($this->exportJobId);
        
        if ($exportJob) {
            $exportJob->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'completed_at' => now()
            ]);

            // Notify user of failure
            $exportJob->user->notify(new ReportExportFailedNotification($exportJob, $exception->getMessage()));
        }
    }

    /**
     * Process the export based on type
     */
    protected function processExport(): string
    {
        switch($this->exportType) {
            case 'pdf':
                return $this->processPdfExport();
            case 'csv':
                return $this->processCsvExport();
            default:
                throw new Exception("Unsupported export type: {$this->exportType}");
        }
    }

    /**
     * Process PDF export
     */
    protected function processPdfExport(): string
    {
        $filename = $this->generateFilename('pdf');
        $filePath = "exports/pdf/{$filename}";
        
        $pdfGenerator = app(PdfReportGenerator::class);
        $template = $this->options['template'] ?? $this->getDefaultTemplate();
        
        $pdfContent = $pdfGenerator->generateReport(
            $this->reportData, 
            $template, 
            $this->options
        );
        
        Storage::disk('local')->put($filePath, $pdfContent);
        
        return $filePath;
    }

    /**
     * Process CSV export
     */
    protected function processCsvExport(): string
    {
        $filename = $this->generateFilename('csv');
        $filePath = "exports/csv/{$filename}";
        
        $headers = $this->options['headers'] ?? $this->generateHeaders();
        $reportType = $this->reportData['report_type'] ?? 'general';
        
        $csvExport = new ReportCsvExport($this->reportData['records'] ?? [], $headers, $reportType);
        $csvExport->store($filePath);
        
        return $filePath;
    }

    /**
     * Generate unique filename
     */
    protected function generateFilename(string $extension): string
    {
        $prefix = $this->options['filename_prefix'] ?? 'report';
        $timestamp = now()->format('Y-m-d_H-i-s');
        $uuid = Str::uuid()->toString();
        
        return "{$prefix}_{$timestamp}_{$uuid}.{$extension}";
    }

    /**
     * Get default template based on report type
     */
    protected function getDefaultTemplate(): string
    {
        $reportType = $this->reportData['report_type'] ?? 'general';
        
        switch($reportType) {
            case 'sales_collections':
                return 'sales-report';
            case 'manifest_performance':
                return 'manifest-report';
            case 'customer_analytics':
                return 'customer-report';
            case 'financial_summary':
                return 'financial-report';
            default:
                return 'general-report';
        }
    }

    /**
     * Generate headers from data if not provided
     */
    protected function generateHeaders(): array
    {
        $records = $this->reportData['records'] ?? [];
        
        if (empty($records)) {
            return [];
        }
        
        $firstRecord = $records[0];
        
        if (is_array($firstRecord)) {
            return array_keys($firstRecord);
        }
        
        if (is_object($firstRecord)) {
            return array_keys(get_object_vars($firstRecord));
        }
        
        return [];
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'report-export',
            "export-type:{$this->exportType}",
            "job-id:{$this->exportJobId}"
        ];
    }
}