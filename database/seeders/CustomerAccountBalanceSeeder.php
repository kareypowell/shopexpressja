<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\CustomerTransaction;
use Illuminate\Support\Facades\DB;

class CustomerAccountBalanceSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Clear existing customer transactions and reset balances
        $this->command->info('Clearing existing customer transactions and resetting balances...');
        
        CustomerTransaction::truncate();
        
        // Reset all customer balances to zero
        $customerRole = Role::where('name', 'customer')->first();
        if ($customerRole) {
            User::where('role_id', $customerRole->id)->update([
                'account_balance' => 0.00,
                'credit_balance' => 0.00,
            ]);
        }

        $this->command->info('Creating test customer account scenarios...');

        // Scenario 1: Customer with positive account balance
        $customer1 = User::where('email', 'like', '%customer%')->first();
        if ($customer1) {
            $this->createCustomerScenario($customer1, 'positive_balance', [
                'initial_payment' => 500.00,
                'description' => 'Initial account deposit'
            ]);
        }

        // Scenario 2: Customer with credit balance from overpayment
        $customer2 = User::where('email', 'like', '%test%')->skip(1)->first();
        if ($customer2) {
            $this->createCustomerScenario($customer2, 'credit_balance', [
                'overpayment_credit' => 150.75,
                'description' => 'Credit from previous overpayment'
            ]);
        }

        // Scenario 3: Customer with negative balance (owes money)
        $customer3 = User::where('email', 'like', '%analytics%')->first();
        if ($customer3) {
            $this->createCustomerScenario($customer3, 'negative_balance', [
                'charge_amount' => 75.50,
                'description' => 'Outstanding package charges'
            ]);
        }

        // Scenario 4: Customer with both account and credit balance
        $customer4 = User::where('first_name', 'Simba')->where('last_name', 'Powell')->first();
        if ($customer4) {
            $this->createCustomerScenario($customer4, 'mixed_balance', [
                'account_payment' => 200.00,
                'credit_amount' => 50.25,
                'description' => 'Mixed balance scenario'
            ]);
        }

        $this->command->info('Customer account balance scenarios created successfully!');
    }

    /**
     * Create different customer balance scenarios
     */
    private function createCustomerScenario(User $customer, string $scenario, array $params): void
    {
        switch ($scenario) {
            case 'positive_balance':
                // Customer with positive account balance
                $customer->recordPayment(
                    $params['initial_payment'],
                    $params['description'],
                    null,
                    'initial_deposit',
                    null,
                    ['scenario' => 'positive_balance']
                );
                break;

            case 'credit_balance':
                // Customer with credit balance only
                $customer->update(['credit_balance' => $params['overpayment_credit']]);
                $customer->transactions()->create([
                    'type' => CustomerTransaction::TYPE_CREDIT,
                    'amount' => $params['overpayment_credit'],
                    'balance_before' => 0.00,
                    'balance_after' => $params['overpayment_credit'],
                    'description' => $params['description'],
                    'reference_type' => 'seed_data',
                    'reference_id' => null,
                    'created_by' => null,
                    'metadata' => ['scenario' => 'credit_balance'],
                ]);
                break;

            case 'negative_balance':
                // Customer with negative balance (owes money)
                $customer->recordCharge(
                    $params['charge_amount'],
                    $params['description'],
                    null,
                    'outstanding_charges',
                    null,
                    ['scenario' => 'negative_balance']
                );
                break;

            case 'mixed_balance':
                // Customer with both account balance and credit balance
                $customer->recordPayment(
                    $params['account_payment'],
                    'Account payment - ' . $params['description'],
                    null,
                    'account_payment',
                    null,
                    ['scenario' => 'mixed_balance']
                );
                
                $customer->update(['credit_balance' => $params['credit_amount']]);
                $customer->transactions()->create([
                    'type' => CustomerTransaction::TYPE_CREDIT,
                    'amount' => $params['credit_amount'],
                    'balance_before' => $params['credit_amount'],
                    'balance_after' => $params['credit_amount'],
                    'description' => 'Credit balance - ' . $params['description'],
                    'reference_type' => 'seed_data',
                    'reference_id' => null,
                    'created_by' => null,
                    'metadata' => ['scenario' => 'mixed_balance'],
                ]);
                break;
        }

        $this->command->info("Created {$scenario} scenario for {$customer->full_name}");
    }
}