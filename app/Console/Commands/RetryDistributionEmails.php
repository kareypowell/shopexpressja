<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PackageDistribution;
use App\Services\DistributionEmailService;

class RetryDistributionEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'distribution:retry-emails 
                            {--id= : Specific distribution ID to retry}
                            {--failed : Only retry failed emails}
                            {--all : Retry all emails regardless of status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry sending distribution receipt emails';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $emailService = app(DistributionEmailService::class);
        
        if ($this->option('id')) {
            // Retry specific distribution
            return $this->retrySpecificDistribution($this->option('id'), $emailService);
        }
        
        if ($this->option('failed')) {
            // Retry only failed emails
            return $this->retryFailedEmails($emailService);
        }
        
        if ($this->option('all')) {
            // Retry all emails
            return $this->retryAllEmails($emailService);
        }
        
        $this->error('Please specify --id, --failed, or --all option');
        return 1;
    }

    /**
     * Retry specific distribution email
     */
    private function retrySpecificDistribution(string $distributionId, DistributionEmailService $emailService): int
    {
        $this->info("Retrying distribution email for ID: {$distributionId}");
        
        $result = $emailService->retryFailedReceipt($distributionId);
        
        if ($result['success']) {
            $this->info("✅ Email retry successful for distribution {$distributionId}");
            return 0;
        } else {
            $this->error("❌ Email retry failed for distribution {$distributionId}: {$result['message']}");
            return 1;
        }
    }

    /**
     * Retry failed emails
     */
    private function retryFailedEmails(DistributionEmailService $emailService): int
    {
        $failedDistributions = PackageDistribution::where('email_sent', false)
            ->whereNotNull('receipt_path')
            ->get();
        
        if ($failedDistributions->isEmpty()) {
            $this->info('No failed distribution emails found');
            return 0;
        }
        
        $this->info("Found {$failedDistributions->count()} failed distribution emails");
        
        $successCount = 0;
        $failCount = 0;
        
        foreach ($failedDistributions as $distribution) {
            $this->line("Retrying distribution {$distribution->id} ({$distribution->receipt_number})...");
            
            $result = $emailService->sendReceiptEmail($distribution, $distribution->customer);
            
            if ($result['success']) {
                $this->info("  ✅ Success");
                $successCount++;
            } else {
                $this->error("  ❌ Failed: {$result['message']}");
                $failCount++;
            }
        }
        
        $this->info("\nSummary:");
        $this->info("✅ Successful: {$successCount}");
        $this->info("❌ Failed: {$failCount}");
        
        return $failCount > 0 ? 1 : 0;
    }

    /**
     * Retry all emails
     */
    private function retryAllEmails(DistributionEmailService $emailService): int
    {
        if (!$this->confirm('This will retry ALL distribution emails. Are you sure?')) {
            $this->info('Operation cancelled');
            return 0;
        }
        
        $distributions = PackageDistribution::whereNotNull('receipt_path')->get();
        
        $this->info("Retrying {$distributions->count()} distribution emails");
        
        $successCount = 0;
        $failCount = 0;
        
        $progressBar = $this->output->createProgressBar($distributions->count());
        $progressBar->start();
        
        foreach ($distributions as $distribution) {
            // Reset email status
            $distribution->update(['email_sent' => false, 'email_sent_at' => null]);
            
            $result = $emailService->sendReceiptEmail($distribution, $distribution->customer);
            
            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("Summary:");
        $this->info("✅ Successful: {$successCount}");
        $this->info("❌ Failed: {$failCount}");
        
        return $failCount > 0 ? 1 : 0;
    }
}