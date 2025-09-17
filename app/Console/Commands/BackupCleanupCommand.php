<?php

namespace App\Console\Commands;

use App\Services\BackupStorageManager;
use Illuminate\Console\Command;

class BackupCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:cleanup 
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old backup files according to retention policy';

    /**
     * The backup storage manager instance.
     *
     * @var BackupStorageManager
     */
    protected $storageManager;

    /**
     * Create a new command instance.
     *
     * @param BackupStorageManager $storageManager
     */
    public function __construct(BackupStorageManager $storageManager)
    {
        parent::__construct();
        $this->storageManager = $storageManager;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('Running in dry-run mode - no files will be deleted');
        }

        $this->info('Starting backup cleanup process...');

        try {
            $result = $this->storageManager->cleanupOldBackups($isDryRun);

            if ($result['success']) {
                $this->displayCleanupResults($result, $isDryRun);
                return Command::SUCCESS;
            } else {
                $this->error('Cleanup failed: ' . $result['error']);
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('Cleanup failed with exception: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Display cleanup results.
     *
     * @param array $result
     * @param bool $isDryRun
     */
    protected function displayCleanupResults(array $result, bool $isDryRun): void
    {
        $action = $isDryRun ? 'Would delete' : 'Deleted';
        
        $this->info("Cleanup completed successfully!");
        
        if (!empty($result['deleted_files'])) {
            $this->info("\n{$action} files:");
            
            $tableData = [];
            $totalSize = 0;
            
            foreach ($result['deleted_files'] as $file) {
                $tableData[] = [
                    $file['name'],
                    $file['type'],
                    $this->formatBytes($file['size']),
                    $file['age_days'] . ' days old'
                ];
                $totalSize += $file['size'];
            }
            
            $this->table(
                ['File Name', 'Type', 'Size', 'Age'],
                $tableData
            );
            
            $this->info("Total space " . ($isDryRun ? 'that would be' : '') . " freed: " . $this->formatBytes($totalSize));
        } else {
            $this->info('No files found for cleanup.');
        }

        if (!empty($result['errors'])) {
            $this->warn("\nErrors encountered:");
            foreach ($result['errors'] as $error) {
                $this->error("- {$error}");
            }
        }

        // Display retention policy info
        $this->info("\nCurrent retention policy:");
        $this->info("- Database backups: {$result['retention']['database']} days");
        $this->info("- File backups: {$result['retention']['files']} days");
    }

    /**
     * Format bytes to human readable format.
     *
     * @param int $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}