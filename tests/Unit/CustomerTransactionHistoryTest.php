<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Livewire\Customers\CustomerTransactionHistory;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class CustomerTransactionHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test customer with balances
        $customerRole = Role::where('name', 'customer')->first();
        $this->customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'account_balance' => 150.75,
            'credit_balance' => 25.50,
        ]);
    }

    /** @test */
    public function it_displays_transaction_history_correctly()
    {
        $this->actingAs($this->customer);
        
        $component = Livewire::test(CustomerTransactionHistory::class);
        
        $component->assertSee('Transaction History')
                  ->assertSee('0 recent transactions')
                  ->assertSee('View Transactions');
    }

    /** @test */
    public function it_can_toggle_transaction_history()
    {
        $this->actingAs($this->customer);
        
        $component = Livewire::test(CustomerTransactionHistory::class);
        
        // Initially transactions should be hidden (showTransactions = false)
        $component->assertSet('showTransactions', false);
        
        // Toggle to show transactions
        $component->call('toggleTransactions');
        $component->assertSet('showTransactions', true);
        
        // Toggle to hide transactions
        $component->call('toggleTransactions');
        $component->assertSet('showTransactions', false);
    }

    /** @test */
    public function it_works_with_specific_customer_id()
    {
        // Create another customer
        $customerRole = Role::where('name', 'customer')->first();
        $anotherCustomer = User::factory()->create([
            'role_id' => $customerRole->id,
            'account_balance' => 200.00,
            'credit_balance' => 0.00,
        ]);
        
        $component = Livewire::test(CustomerTransactionHistory::class, ['customerId' => $anotherCustomer->id]);
        
        $component->assertSee('Transaction History')
                  ->assertSee('0 recent transactions')
                  ->assertSee('View Transactions');
    }
}