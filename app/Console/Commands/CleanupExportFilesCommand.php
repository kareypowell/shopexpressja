<?php

namespace App\Console\Commands;

use App\Services\ReportExportService;
use Illuminate\Console\Command;

class CleanupExportFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reports:cleanup-exports 
                            {--days=7 : Number of days to keep export files}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old report export files to free up storage space';

    protected $exportService;

    /**
     * Create a new command instance.
     */
    public function __construct(ReportExportService $exportService)
    {
        parent::__construct();
        $this->exportService = $exportService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("Cleaning up export files older than {$days} days...");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will actually be deleted');
        }

        try {
            if ($dryRun) {
                $count = $this->simulateCleanup($days);
                $this->info("Would delete {$count} export files");
            } else {
                $count = $this->exportService->cleanupOldExports($days);
                $this->info("Successfully deleted {$count} export files");
            }

            // Show storage statistics
            $this->showStorageStats();

        } catch (\Exception $e) {
            $this->error("Failed to cleanup export files: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Simulate cleanup without actually deleting files
     */
    protected function simulateCleanup(int $days): int
    {
        $cutoffDate = now()->subDays($days);
        
        return \App\Models\ReportExportJob::where('created_at', '<', $cutoffDate)
            ->whereNotNull('file_path')
            ->count();
    }

    /**
     * Show storage statistics
     */
    protected function showStorageStats(): void
    {
        $exportPath = storage_path('app/exports');
        
        if (!is_dir($exportPath)) {
            $this->warn('Export directory does not exist');
            return;
        }

        $totalSize = 0;
        $fileCount = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($exportPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $totalSize += $file->getSize();
                $fileCount++;
            }
        }

        $this->info("\nStorage Statistics:");
        $this->line("Export files: {$fileCount}");
        $this->line("Total size: " . $this->formatBytes($totalSize));
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}