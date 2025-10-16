<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class DemonstrateOutstandingCalculationFix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:outstanding-fix {user_id? : The ID of the user to analyze}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demonstrate the difference between old and new outstanding amount calculations';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }
            $this->analyzeUser($user);
        } else {
            // Analyze first few customers
            $users = User::customers()->with('packages')->limit(5)->get();
            
            if ($users->isEmpty()) {
                $this->info('No customers found in the database.');
                return 0;
            }
            
            foreach ($users as $user) {
                $this->analyzeUser($user);
                $this->line(''); // Empty line between users
            }
        }
        
        return 0;
    }
    
    private function analyzeUser(User $user)
    {
        $this->info("Analyzing Customer: {$user->full_name} (ID: {$user->id})");
        $this->line("Email: {$user->email}");
        
        // Get package breakdown
        $packages = $user->packages;
        $deliveredCount = $packages->where('status', 'delivered')->count();
        $nonDeliveredCount = $packages->whereIn('status', ['ready', 'customs', 'pending', 'processing', 'shipped', 'delayed'])->count();
        
        $this->line("Packages: {$packages->count()} total ({$deliveredCount} delivered, {$nonDeliveredCount} non-delivered)");
        
        if ($packages->isEmpty()) {
            $this->warn("No packages found for this customer.");
            return;
        }
        
        // Get comparison
        $comparison = $user->compareOutstandingCalculations();
        
        $this->table(
            ['Calculation Method', 'Outstanding Amount', 'Explanation'],
            [
                [
                    'Old (Incorrect)', 
                    '$' . number_format($comparison['old_calculation']['outstanding_balance'], 2),
                    $comparison['old_calculation']['method']
                ],
                [
                    'New (Correct)', 
                    '$' . number_format($comparison['new_calculation']['outstanding_balance'], 2),
                    $comparison['new_calculation']['method']
                ],
                [
                    'Difference', 
                    '$' . number_format($comparison['difference']['amount'], 2),
                    $comparison['difference']['explanation']
                ]
            ]
        );
        
        // Show breakdown
        $this->line("Package Breakdown:");
        $this->line("- Delivered packages total: $" . number_format($comparison['breakdown']['delivered_packages_total'], 2));
        $this->line("- Non-delivered packages total: $" . number_format($comparison['breakdown']['non_delivered_packages_total'], 2));
        $this->line("- Total all packages: $" . number_format($comparison['breakdown']['total_all_packages'], 2));
        
        // Show old calculation details
        $this->line("\nOld Calculation Details:");
        $this->line("- Total Owed: $" . number_format($comparison['old_calculation']['total_owed'], 2));
        $this->line("- Total Collections: $" . number_format($comparison['old_calculation']['total_collections'], 2));
        $this->line("- Total Write-offs: $" . number_format($comparison['old_calculation']['total_write_offs'], 2));
        
        if ($comparison['difference']['amount'] != 0) {
            $this->warn("⚠️  Discrepancy detected! The old calculation is off by $" . number_format(abs($comparison['difference']['amount']), 2));
        } else {
            $this->info("✅ Both calculations match for this customer.");
        }
    }
}