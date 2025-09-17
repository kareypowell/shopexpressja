<?php

namespace App\Console\Commands;

use App\Models\Backup;
use App\Services\RestoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:restore 
                            {backup? : Backup file path or backup ID}
                            {--database : Restore database only}
                            {--files : Restore files only}
                            {--force : Skip confirmation prompts}
                            {--list : List available backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore database and/or files from a backup';

    /**
     * The restore service instance.
     *
     * @var RestoreService
     */
    protected $restoreService;

    /**
     * Create a new command instance.
     *
     * @param RestoreService $restoreService
     */
    public function __construct(RestoreService $restoreService)
    {
        parent::__construct();
        $this->restoreService = $restoreService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Handle list option
        if ($this->option('list')) {
            return $this->listAvailableBackups();
        }

        // Validate options
        if ($this->option('database') && $this->option('files')) {
            $this->error('Cannot specify both --database and --files options. Use neither for full restore.');
            return Command::FAILURE;
        }

        // Get backup file
        $backupInfo = $this->getBackupFile();
        if (!$backupInfo) {
            return Command::FAILURE;
        }

        // Validate backup file
        if (!$this->validateBackupFile($backupInfo)) {
            return Command::FAILURE;
        }

        // Get confirmation unless force flag is used
        if (!$this->option('force') && !$this->confirmRestore($backupInfo)) {
            $this->info('Restore operation cancelled.');
            return Command::SUCCESS;
        }

        // Perform restoration
        return $this->performRestore($backupInfo);
    }

    /**
     * List available backups.
     *
     * @return int
     */
    protected function listAvailableBackups(): int
    {
        $this->info('Available backups:');
        $this->newLine();

        $backups = Backup::completed()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        if ($backups->isEmpty()) {
            $this->warn('No completed backups found.');
            return Command::SUCCESS;
        }

        $tableData = [];
        foreach ($backups as $backup) {
            $tableData[] = [
                $backup->id,
                $backup->name,
                $backup->type,
                $backup->formatted_file_size,
                $backup->created_at->format('Y-m-d H:i:s'),
                file_exists($backup->file_path) ? 'Available' : 'Missing'
            ];
        }

        $this->table(
            ['ID', 'Name', 'Type', 'Size', 'Created', 'Status'],
            $tableData
        );

        $this->newLine();
        $this->info('Use: php artisan backup:restore <ID> or php artisan backup:restore <file-path>');

        return Command::SUCCESS;
    }

    /**
     * Get backup file from argument or user selection.
     *
     * @return array|null
     */
    protected function getBackupFile(): ?array
    {
        $backupArg = $this->argument('backup');

        if ($backupArg) {
            // Check if it's a backup ID (numeric)
            if (is_numeric($backupArg)) {
                $backup = Backup::find($backupArg);
                if (!$backup) {
                    $this->error("Backup with ID {$backupArg} not found.");
                    return null;
                }

                return [
                    'backup' => $backup,
                    'file_path' => $backup->file_path,
                    'type' => $backup->type
                ];
            }

            // Treat as file path
            if (!file_exists($backupArg)) {
                $this->error("Backup file not found: {$backupArg}");
                return null;
            }

            // Try to find backup record by file path
            $backup = Backup::where('file_path', $backupArg)->first();

            return [
                'backup' => $backup,
                'file_path' => $backupArg,
                'type' => $backup ? $backup->type : $this->detectBackupType($backupArg)
            ];
        }

        // No argument provided, show interactive selection
        return $this->selectBackupInteractively();
    }

    /**
     * Interactive backup selection.
     *
     * @return array|null
     */
    protected function selectBackupInteractively(): ?array
    {
        $backups = Backup::completed()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($backups->isEmpty()) {
            $this->error('No completed backups available for restoration.');
            return null;
        }

        $this->info('Available backups:');
        $choices = [];
        foreach ($backups as $index => $backup) {
            $status = file_exists($backup->file_path) ? '✓' : '✗';
            $choice = sprintf(
                '%s [%s] %s (%s) - %s',
                $status,
                $backup->type,
                $backup->name,
                $backup->formatted_file_size,
                $backup->created_at->format('Y-m-d H:i:s')
            );
            $choices[$index] = $choice;
        }

        $selectedIndex = $this->choice('Select a backup to restore:', $choices);
        $selectedBackup = $backups->values()[$selectedIndex];

        if (!file_exists($selectedBackup->file_path)) {
            $this->error('Selected backup file is missing: ' . $selectedBackup->file_path);
            return null;
        }

        return [
            'backup' => $selectedBackup,
            'file_path' => $selectedBackup->file_path,
            'type' => $selectedBackup->type
        ];
    }

    /**
     * Detect backup type from file extension.
     *
     * @param string $filePath
     * @return string
     */
    protected function detectBackupType(string $filePath): string
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        
        switch (strtolower($extension)) {
            case 'sql':
                return 'database';
            case 'zip':
            case 'tar':
            case 'gz':
                return 'files';
            default:
                return 'unknown';
        }
    }

    /**
     * Validate backup file.
     *
     * @param array $backupInfo
     * @return bool
     */
    protected function validateBackupFile(array $backupInfo): bool
    {
        $filePath = $backupInfo['file_path'];
        $type = $backupInfo['type'];

        // Check file exists and is readable
        if (!file_exists($filePath)) {
            $this->error("Backup file not found: {$filePath}");
            return false;
        }

        if (!is_readable($filePath)) {
            $this->error("Backup file is not readable: {$filePath}");
            return false;
        }

        // Validate file size
        $fileSize = filesize($filePath);
        if ($fileSize === 0) {
            $this->error("Backup file is empty: {$filePath}");
            return false;
        }

        $this->info("Backup file validation passed:");
        $this->line("  File: {$filePath}");
        $this->line("  Type: {$type}");
        $this->line("  Size: " . $this->formatBytes($fileSize));

        return true;
    }

    /**
     * Get confirmation for restore operation.
     *
     * @param array $backupInfo
     * @return bool
     */
    protected function confirmRestore(array $backupInfo): bool
    {
        $this->newLine();
        $this->warn('⚠️  WARNING: This operation will overwrite existing data!');
        $this->newLine();

        $restoreType = $this->getRestoreType($backupInfo['type']);
        
        $this->line("Restore operation details:");
        $this->line("  Backup file: {$backupInfo['file_path']}");
        $this->line("  Restore type: {$restoreType}");
        
        if ($backupInfo['backup']) {
            $this->line("  Backup created: {$backupInfo['backup']->created_at->format('Y-m-d H:i:s')}");
        }

        $this->newLine();

        if (in_array($restoreType, ['database', 'full'])) {
            $this->warn('• Database will be completely replaced');
            $this->warn('• Application will be put in maintenance mode during restoration');
            $this->warn('• A pre-restore backup will be created automatically');
        }

        if (in_array($restoreType, ['files', 'full'])) {
            $this->warn('• File directories will be completely replaced');
            $this->warn('• Existing files will be backed up before restoration');
        }

        $this->newLine();

        return $this->confirm('Do you want to proceed with the restoration?', false);
    }

    /**
     * Get restore type based on options and backup type.
     *
     * @param string $backupType
     * @return string
     */
    protected function getRestoreType(string $backupType): string
    {
        if ($this->option('database')) {
            return 'database';
        }

        if ($this->option('files')) {
            return 'files';
        }

        // Default to backup type or full restore
        return $backupType === 'full' ? 'full' : $backupType;
    }

    /**
     * Perform the restoration.
     *
     * @param array $backupInfo
     * @return int
     */
    protected function performRestore(array $backupInfo): int
    {
        $filePath = $backupInfo['file_path'];
        $restoreType = $this->getRestoreType($backupInfo['type']);

        $this->info("Starting {$restoreType} restoration...");
        $this->newLine();

        try {
            $results = [];

            // Perform database restoration
            if (in_array($restoreType, ['database', 'full'])) {
                $this->line('Restoring database...');
                $result = $this->restoreService->restoreDatabase($filePath);
                $results['database'] = $result;

                if ($result->isFailed()) {
                    $this->error('Database restoration failed: ' . $result->getMessage());
                    return Command::FAILURE;
                }

                $this->info('✓ Database restored successfully');
            }

            // Perform file restoration
            if (in_array($restoreType, ['files', 'full'])) {
                $this->line('Restoring files...');
                
                // Get directories to restore from config
                $directories = config('backup.files.directories', [
                    'storage/app/public/pre-alerts',
                    'storage/app/public/receipts',
                ]);

                $result = $this->restoreService->restoreFiles($filePath, $directories);
                $results['files'] = $result;

                if ($result->isFailed()) {
                    $this->error('File restoration failed: ' . $result->getMessage());
                    return Command::FAILURE;
                }

                $this->info('✓ Files restored successfully');
            }

            $this->newLine();
            $this->info('🎉 Restoration completed successfully!');
            
            // Display restoration details
            $this->displayRestorationDetails($results);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Restoration failed with exception: ' . $e->getMessage());
            $this->newLine();
            $this->warn('Check the application logs for more details.');
            
            return Command::FAILURE;
        }
    }

    /**
     * Display restoration details.
     *
     * @param array $results
     */
    protected function displayRestorationDetails(array $results): void
    {
        $this->newLine();
        $this->info('Restoration Summary:');

        $tableData = [];

        foreach ($results as $type => $result) {
            $tableData[] = [
                ucfirst($type),
                $result->isSuccessful() ? '✓ Success' : '✗ Failed',
                $result->getMessage()
            ];

            // Add pre-restore backup info if available
            $preRestoreBackup = $result->get('pre_restore_backup');
            if ($preRestoreBackup) {
                $tableData[] = [
                    "Pre-restore backup",
                    '📁 Created',
                    basename($preRestoreBackup)
                ];
            }
        }

        $this->table(['Component', 'Status', 'Details'], $tableData);

        $this->newLine();
        $this->info('💡 Pre-restore backups have been created and can be used for rollback if needed.');
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