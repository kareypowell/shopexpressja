<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixCacheDirectories extends Command
{
    protected $signature = 'cache:fix-directories';
    protected $description = 'Create missing cache directories and set proper permissions';

    public function handle()
    {
        $this->info('Fixing Laravel cache directories...');

        $directories = [
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($directories as $directory) {
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0775, true);
                $this->info("Created directory: {$directory}");
            } else {
                $this->comment("Directory already exists: {$directory}");
            }
        }

        // Set permissions
        $this->info('Setting permissions...');
        
        try {
            chmod(storage_path(), 0775);
            chmod(storage_path('framework'), 0775);
            chmod(storage_path('framework/cache'), 0775);
            chmod(storage_path('framework/cache/data'), 0775);
            chmod(storage_path('framework/sessions'), 0775);
            chmod(storage_path('framework/views'), 0775);
            chmod(storage_path('logs'), 0775);
            chmod(base_path('bootstrap/cache'), 0775);
            
            $this->info('Permissions set successfully!');
        } catch (\Exception $e) {
            $this->warn('Could not set permissions automatically. Please run:');
            $this->warn('chmod -R 775 storage');
            $this->warn('chmod -R 775 bootstrap/cache');
        }

        $this->info('Cache directories fixed!');
        
        $this->comment('Recommended next steps:');
        $this->comment('1. php artisan config:cache');
        $this->comment('2. php artisan route:cache');
        $this->comment('3. php artisan view:cache');

        return 0;
    }
}