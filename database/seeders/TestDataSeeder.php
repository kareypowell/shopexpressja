<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting comprehensive test data seeding...');
        
        // Step 1: Reset customer account balances and transactions
        $this->command->info('📊 Step 1: Setting up customer account balances...');
        $this->call(CustomerAccountBalanceSeeder::class);
        
        // Step 2: Create test packages for distribution
        $this->command->info('📦 Step 2: Creating test packages...');
        $this->call(PackageDistributionTestDataSeeder::class);
        
        $this->command->info('✅ Test data seeding completed successfully!');
        $this->command->info('');
        $this->command->info('📋 Test Scenarios Created:');
        $this->command->info('  • Customer with positive account balance + single package');
        $this->command->info('  • Customer with credit balance + multiple packages');
        $this->command->info('  • Customer with negative balance + high-value package');
        $this->command->info('  • Customer with mixed balances + mixed status packages');
        $this->command->info('');
        $this->command->info('🧪 You can now test:');
        $this->command->info('  • Package distribution with various payment scenarios');
        $this->command->info('  • Credit balance application');
        $this->command->info('  • Overpayment handling');
        $this->command->info('  • Account balance updates');
        $this->command->info('  • Dashboard balance display');
    }
}