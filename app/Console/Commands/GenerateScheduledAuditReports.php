<?php

namespace App\Console\Commands;

use App\Models\AuditSetting;
use App\Services\AuditExportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class GenerateScheduledAuditReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:generate-scheduled-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and send scheduled audit reports';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting scheduled audit report generation...');

        $exportService = new AuditExportService();
        $scheduledReports = $exportService->getScheduledReports();

        if ($scheduledReports->isEmpty()) {
            $this->info('No scheduled reports found.');
            return 0;
        }

        $processedCount = 0;
        $errorCount = 0;

        foreach ($scheduledReports as $report) {
            try {
                if ($this->shouldRunReport($report)) {
                    $this->info("Processing report: {$report['name']}");
                    
                    $this->generateAndSendReport($exportService, $report);
                    $this->updateNextRunTime($report);
                    
                    $processedCount++;
                    $this->info("✓ Report '{$report['name']}' generated and sent successfully");
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("✗ Failed to generate report '{$report['name']}': " . $e->getMessage());
            }
        }

        $this->info("Scheduled report generation completed.");
        $this->info("Reports processed: {$processedCount}");
        
        if ($errorCount > 0) {
            $this->warn("Reports with errors: {$errorCount}");
        }

        return 0;
    }

    /**
     * Check if a report should be run now
     *
     * @param array $report
     * @return bool
     */
    protected function shouldRunReport(array $report): bool
    {
        if (!$report['active']) {
            return false;
        }

        $nextRun = Carbon::parse($report['next_run']);
        return $nextRun->isPast();
    }

    /**
     * Generate and send the report
     *
     * @param AuditExportService $exportService
     * @param array $report
     * @return void
     */
    protected function generateAndSendReport(AuditExportService $exportService, array $report): void
    {
        // Generate compliance report
        $reportData = $exportService->generateComplianceReport($report['filters']);
        
        $options = [
            'title' => $report['name'] . ' - ' . Carbon::now()->format('F j, Y'),
        ];

        if ($report['format'] === 'pdf') {
            $filePath = $exportService->exportToPdf($reportData['audit_logs'], $report['filters'], $options);
            $this->sendReportEmail($report, $filePath, 'pdf');
        } elseif ($report['format'] === 'csv') {
            $csvContent = $exportService->exportToCsv($reportData['audit_logs'], $report['filters']);
            $filename = 'scheduled-reports/' . $report['name'] . '_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';
            Storage::disk('public')->put($filename, $csvContent);
            $this->sendReportEmail($report, $filename, 'csv');
        }
    }

    /**
     * Send report via email
     *
     * @param array $report
     * @param string $filePath
     * @param string $format
     * @return void
     */
    protected function sendReportEmail(array $report, string $filePath, string $format): void
    {
        if (empty($report['recipients'])) {
            return;
        }

        $fullPath = Storage::disk('public')->path($filePath);
        $filename = basename($filePath);

        foreach ($report['recipients'] as $recipient) {
            try {
                Mail::send('emails.scheduled-audit-report', [
                    'report_name' => $report['name'],
                    'generated_at' => Carbon::now(),
                    'format' => strtoupper($format),
                ], function ($message) use ($recipient, $report, $fullPath, $filename) {
                    $message->to($recipient)
                           ->subject('Scheduled Audit Report: ' . $report['name'])
                           ->attach($fullPath, [
                               'as' => $filename,
                               'mime' => $format === 'pdf' ? 'application/pdf' : 'text/csv',
                           ]);
                });

                $this->info("  ✓ Report sent to: {$recipient}");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to send report to {$recipient}: " . $e->getMessage());
            }
        }
    }

    /**
     * Update the next run time for the report
     *
     * @param array $report
     * @return void
     */
    protected function updateNextRunTime(array $report): void
    {
        $nextRun = $this->calculateNextRun($report['frequency']);
        
        $report['next_run'] = $nextRun->toISOString();
        
        AuditSetting::updateOrCreate(
            ['setting_key' => "scheduled_report_{$report['name']}"],
            ['setting_value' => $report]
        );
    }

    /**
     * Calculate next run time based on frequency
     *
     * @param string $frequency
     * @return Carbon
     */
    protected function calculateNextRun(string $frequency): Carbon
    {
        switch ($frequency) {
            case 'daily':
                return Carbon::now()->addDay();
            case 'weekly':
                return Carbon::now()->addWeek();
            case 'monthly':
                return Carbon::now()->addMonth();
            default:
                return Carbon::now()->addDay();
        }
    }
}