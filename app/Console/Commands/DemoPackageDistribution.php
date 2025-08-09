<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Package;
use App\Services\PackageDistributionService;
use App\Enums\PackageStatus;

class DemoPackageDistribution extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:package-distribution {customer_name?} {--amount=} {--apply-credit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demonstrate package distribution with balance updates';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $customerName = $this->argument('customer_name') ?? 'Simba Powell';
        $amount = $this->option('amount');
        $applyCredit = $this->option('apply-credit');

        // Find customer
        $nameParts = explode(' ', $customerName);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';

        $customer = User::where('first_name', 'like', "%{$firstName}%")
            ->where('last_name', 'like', "%{$lastName}%")
            ->first();

        if (!$customer) {
            $this->error("Customer '{$customerName}' not found.");
            return 1;
        }

        // Find ready packages for this customer
        $readyPackages = Package::where('user_id', $customer->id)
            ->where('status', PackageStatus::READY)
            ->get();

        if ($readyPackages->isEmpty()) {
            $this->error("No packages ready for distribution for {$customer->full_name}.");
            return 1;
        }

        // Show current customer balance
        $this->info("📊 Current Balance for {$customer->full_name}:");
        $this->line("  Account Balance: $" . number_format($customer->account_balance, 2));
        $this->line("  Credit Balance:  $" . number_format($customer->credit_balance, 2));
        $this->line("  Total Available: $" . number_format($customer->total_available_balance, 2));
        if ($customer->pending_package_charges > 0) {
            $this->line("  Pending Charges: $" . number_format($customer->pending_package_charges, 2));
            $this->line("  💰 Amount Needed to Collect All: $" . number_format($customer->total_amount_needed, 2));
        }
        $this->line("");

        // Show available packages
        $this->info("📦 Ready Packages:");
        $totalCost = 0;
        foreach ($readyPackages as $package) {
            $cost = $package->total_cost;
            $totalCost += $cost;
            $this->line("  {$package->tracking_number}: {$package->description} - $" . number_format($cost, 2));
        }
        $this->line("  Total Cost: $" . number_format($totalCost, 2));
        $this->line("");

        // Get amount to collect
        if (!$amount) {
            $amount = $this->ask("Enter amount to collect", $totalCost);
        }
        $amount = (float) $amount;

        // Confirm distribution
        $creditText = $applyCredit ? ' (with credit application)' : '';
        if (!$this->confirm("Distribute packages for $" . number_format($amount, 2) . "{$creditText}?")) {
            $this->info("Distribution cancelled.");
            return 0;
        }

        // Perform distribution
        $distributionService = app(PackageDistributionService::class);
        $adminUser = User::where('email', 'like', '%admin%')->first() ?? $customer;

        try {
            $result = $distributionService->distributePackages(
                $readyPackages->pluck('id')->toArray(),
                $amount,
                $adminUser,
                $applyCredit
            );

            if ($result['success']) {
                $this->info("✅ Distribution successful!");
                
                // Show updated balance
                $customer->refresh();
                $this->info("📊 Updated Balance for {$customer->full_name}:");
                $this->line("  Account Balance: $" . number_format($customer->account_balance, 2));
                $this->line("  Credit Balance:  $" . number_format($customer->credit_balance, 2));
                $this->line("  Total Available: $" . number_format($customer->total_available_balance, 2));
                
                // Show distribution details
                $distribution = $result['distribution'];
                $this->line("");
                $this->info("📋 Distribution Details:");
                $this->line("  Receipt Number: {$distribution->receipt_number}");
                $this->line("  Total Amount:   $" . number_format($distribution->total_amount, 2));
                $this->line("  Amount Collected: $" . number_format($distribution->amount_collected, 2));
                $this->line("  Credit Applied: $" . number_format($distribution->credit_applied, 2));
                $this->line("  Payment Status: {$distribution->payment_status}");
                
                if ($distribution->outstanding_balance > 0) {
                    $this->line("  Outstanding:    $" . number_format($distribution->outstanding_balance, 2));
                }

            } else {
                $this->error("❌ Distribution failed: " . $result['message']);
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("❌ Distribution error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}