<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:create 
                            {--database : Create database backup only}
                            {--files : Create files backup only}
                            {--name= : Custom name for the backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a manual backup of database and/or files';

    /**
     * The backup service instance.
     *
     * @var BackupService
     */
    protected $backupService;

    /**
     * Create a new command instance.
     *
     * @param BackupService $backupService
     */
    public function __construct(BackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting backup process...');

        // Determine backup type based on options
        $options = $this->getBackupOptions();

        try {
            $result = $this->backupService->createManualBackup($options);

            if ($result->isSuccess()) {
                $this->info('Backup completed successfully!');
                $this->displayBackupDetails($result);
                return Command::SUCCESS;
            } else {
                $this->error('Backup failed: ' . $result->getErrorMessage());
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('Backup failed with exception: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Get backup options from command arguments.
     *
     * @return array
     */
    protected function getBackupOptions(): array
    {
        $options = [];

        // Determine backup type
        if ($this->option('database') && $this->option('files')) {
            $this->error('Cannot specify both --database and --files options. Use neither for full backup.');
            exit(Command::FAILURE);
        }

        if ($this->option('database')) {
            $options['type'] = 'database';
        } elseif ($this->option('files')) {
            $options['type'] = 'files';
        } else {
            $options['type'] = 'full';
        }

        // Add custom name if provided
        if ($this->option('name')) {
            $options['name'] = $this->option('name');
        }

        return $options;
    }

    /**
     * Display backup details after successful completion.
     *
     * @param \App\Services\BackupResult $result
     */
    protected function displayBackupDetails($result): void
    {
        $this->table(
            ['Property', 'Value'],
            [
                ['Backup Type', $result->getType()],
                ['File Path', $result->getFilePath()],
                ['File Size', $this->formatBytes($result->getFileSize())],
                ['Duration', $result->getDuration() . ' seconds'],
                ['Created At', $result->getCreatedAt()->format('Y-m-d H:i:s')],
            ]
        );
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