<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CustomerTransaction;
use App\Models\Package;
use App\Models\User;

class LinkDiscountsToManifests extends Command
{
    protected $signature = 'discounts:link-to-manifests 
                            {--dry-run : Show what would be updated without making changes}
                            {--user-id= : Only process discounts for a specific user}';

    protected $description = 'Link discount credit transactions to manifests based on customer packages';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $userId = $this->option('user-id');

        $this->info('Finding discount credits without manifest links...');
        
        // Find discount credits without manifest_id
        $query = CustomerTransaction::where('type', 'credit')
            ->whereNull('manifest_id')
            ->where(function($q) {
                $q->where('description', 'like', '%discount%')
                  ->orWhere('description', 'like', '%write%')
                  ->orWhere('description', 'like', '%forgiv%')
                  ->orWhere('description', 'like', '%waive%');
            });
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        $discounts = $query->get();
        
        if ($discounts->isEmpty()) {
            $this->info('No unlinked discount credits found.');
            return 0;
        }
        
        $this->info("Found {$discounts->count()} discount credits to process.");
        $this->newLine();
        
        $updated = 0;
        $skipped = 0;
        
        foreach ($discounts as $discount) {
            $user = User::find($discount->user_id);
            if (!$user) {
                $this->warn("User #{$discount->user_id} not found for transaction #{$discount->id}");
                $skipped++;
                continue;
            }
            
            // Get customer's packages around the time of the discount
            $discountDate = $discount->created_at;
            $packages = Package::where('user_id', $user->id)
                ->whereBetween('created_at', [
                    $discountDate->copy()->subDays(30),
                    $discountDate->copy()->addDays(30)
                ])
                ->orderBy('created_at', 'desc')
                ->get();
            
            if ($packages->isEmpty()) {
                $this->warn("No packages found for {$user->full_name} around discount date");
                $skipped++;
                continue;
            }
            
            // Get the most recent manifest
            $manifestId = $packages->first()->manifest_id;
            
            if (!$manifestId) {
                $this->warn("Package has no manifest for {$user->full_name}");
                $skipped++;
                continue;
            }
            
            $this->line("Transaction #{$discount->id}:");
            $this->line("  Customer: {$user->full_name}");
            $this->line("  Amount: \${$discount->amount}");
            $this->line("  Description: {$discount->description}");
            $this->line("  Will link to manifest ID: {$manifestId}");
            
            if (!$dryRun) {
                $discount->update(['manifest_id' => $manifestId]);
                $this->info("  ✓ Updated");
                $updated++;
            } else {
                $this->comment("  (dry-run - not updated)");
                $updated++;
            }
            
            $this->newLine();
        }
        
        $this->newLine();
        if ($dryRun) {
            $this->info("DRY RUN COMPLETE:");
            $this->info("- Would update: {$updated} transactions");
            $this->info("- Would skip: {$skipped} transactions");
        } else {
            $this->info("LINKING COMPLETE:");
            $this->info("- Updated: {$updated} transactions");
            $this->info("- Skipped: {$skipped} transactions");
        }
        
        return 0;
    }
}
