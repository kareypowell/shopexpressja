<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PackageDistribution;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FixHistoricalOverpayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:historical-overpayments {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix historical package distributions where customers overpaid but credit was not added';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('Running in dry-run mode - no changes will be made');
        }

        // Find distributions where amount collected > total amount (overpayments)
        $overpaidDistributions = PackageDistribution::whereRaw('amount_collected > total_amount')
            ->with('customer')
            ->get();

        if ($overpaidDistributions->isEmpty()) {
            $this->info('No historical overpayments found.');
            return 0;
        }

        $this->info("Found {$overpaidDistributions->count()} distributions with overpayments:");

        $totalCreditToAdd = 0;
        $customersAffected = [];

        foreach ($overpaidDistributions as $distribution) {
            $overpayment = $distribution->amount_collected - $distribution->total_amount;
            $customer = $distribution->customer;
            
            $this->line("- Distribution #{$distribution->receipt_number}: Customer {$customer->full_name} overpaid by $" . number_format($overpayment, 2));
            
            $totalCreditToAdd += $overpayment;
            $customersAffected[$customer->id] = $customer;

            if (!$dryRun) {
                DB::beginTransaction();
                try {
                    // Add overpayment to customer's credit balance
                    $customer->addOverpaymentCredit(
                        $overpayment,
                        "Historical overpayment credit from distribution #{$distribution->receipt_number}",
                        null, // No specific user for historical fix
                        'package_distribution',
                        $distribution->id,
                        [
                            'distribution_id' => $distribution->id,
                            'historical_fix' => true,
                            'original_total' => $distribution->total_amount,
                            'original_collected' => $distribution->amount_collected,
                            'overpayment' => $overpayment,
                        ]
                    );

                    DB::commit();
                    $this->info("  ✓ Added $" . number_format($overpayment, 2) . " credit to {$customer->full_name}");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("  ✗ Failed to add credit for {$customer->full_name}: " . $e->getMessage());
                }
            }
        }

        $this->info("\nSummary:");
        $this->info("- Total overpayments: $" . number_format($totalCreditToAdd, 2));
        $this->info("- Customers affected: " . count($customersAffected));

        if ($dryRun) {
            $this->info("\nRun without --dry-run to apply these changes.");
        } else {
            $this->info("\nHistorical overpayments have been processed!");
        }

        return 0;
    }
}