<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CustomerTransaction;
use App\Models\Package;
use App\Models\User;
use App\Models\Manifest;

class LinkAllTransactionsToManifests extends Command
{
    protected $signature = 'transactions:link-all-to-manifests 
                            {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Link all unlinked transactions to manifests based on customer packages';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('Finding transactions without manifest links...');
        
        $unlinked = CustomerTransaction::whereNull('manifest_id')->get();
        
        if ($unlinked->isEmpty()) {
            $this->info('All transactions are already linked to manifests.');
            return 0;
        }
        
        $this->info("Found {$unlinked->count()} unlinked transactions.");
        $this->newLine();
        
        $updated = 0;
        $skipped = 0;
        
        foreach ($unlinked as $txn) {
            $user = User::find($txn->user_id);
            if (!$user) {
                $this->warn("Transaction #{$txn->id}: User not found");
                $skipped++;
                continue;
            }
            
            // Find the most recent package for this user
            $package = Package::where('user_id', $user->id)
                ->whereNotNull('manifest_id')
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($package && $package->manifest_id) {
                $manifest = Manifest::find($package->manifest_id);
                
                $this->line("Transaction #{$txn->id}:");
                $this->line("  User: {$user->full_name}");
                $this->line("  Type: {$txn->type}");
                $this->line("  Amount: \${$txn->amount}");
                $this->line("  Will link to: {$manifest->name} (ID: {$manifest->id})");
                
                if (!$dryRun) {
                    $txn->update(['manifest_id' => $package->manifest_id]);
                    $this->info("  ✓ Linked");
                    $updated++;
                } else {
                    $this->comment("  (dry-run - not updated)");
                    $updated++;
                }
            } else {
                $this->warn("Transaction #{$txn->id} ({$user->full_name}): No packages with manifest found");
                $skipped++;
            }
            
            $this->newLine();
        }
        
        $this->newLine();
        if ($dryRun) {
            $this->info("DRY RUN COMPLETE:");
            $this->info("- Would link: {$updated} transactions");
            $this->info("- Would skip: {$skipped} transactions");
        } else {
            $this->info("LINKING COMPLETE:");
            $this->info("- Linked: {$updated} transactions");
            $this->info("- Skipped: {$skipped} transactions");
        }
        
        // Clear cache after linking
        if (!$dryRun && $updated > 0) {
            $this->call('cache:clear');
            $this->info('Cache cleared.');
        }
        
        return 0;
    }
}
