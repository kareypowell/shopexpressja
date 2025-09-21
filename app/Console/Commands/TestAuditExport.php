<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Services\AuditExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TestAuditExport extends Command
{
    protected $signature = 'audit:test-export {--format=csv : Export format (csv or pdf)}';
    protected $description = 'Generate test audit export files';

    public function handle()
    {
        $format = $this->option('format');
        $exportService = new AuditExportService();

        // Get some audit logs
        $auditLogs = AuditLog::with('user')->take(50)->get();
        
        if ($auditLogs->isEmpty()) {
            $this->error('No audit logs found. Please ensure you have some audit data first.');
            return 1;
        }

        $this->info("Generating {$format} export with {$auditLogs->count()} audit logs...");

        try {
            if ($format === 'csv') {
                $content = $exportService->exportToCsv($auditLogs);
                $filename = 'test_audit_export_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';
                
                // Save to public storage
                Storage::disk('public')->put('exports/' . $filename, $content);
                $fullPath = Storage::disk('public')->path('exports/' . $filename);
                
                $this->info("CSV export saved to: {$fullPath}");
                $this->info("Download URL: " . url('/admin/audit-logs/download/' . $filename));
                
            } elseif ($format === 'pdf') {
                $filePath = $exportService->exportToPdf($auditLogs, [], [
                    'title' => 'Test Audit Export - ' . Carbon::now()->format('F j, Y')
                ]);
                
                $fullPath = Storage::disk('public')->path($filePath);
                $this->info("PDF export saved to: {$fullPath}");
                $this->info("Download URL: " . url('/admin/audit-logs/download/' . basename($filePath)));
                
            } else {
                $this->error('Invalid format. Use --format=csv or --format=pdf');
                return 1;
            }

            $this->info('Export completed successfully!');
            $this->info('You can find the file at: ' . $fullPath);
            
        } catch (\Exception $e) {
            $this->error('Export failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}