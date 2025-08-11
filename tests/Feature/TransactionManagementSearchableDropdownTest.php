<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\CustomerTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Http\Livewire\Admin\TransactionManagement;

class TransactionManagementSearchableDropdownTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customers;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user (assuming role_id 1 is admin)
        $this->admin = User::factory()->create(['role_id' => 1]);
        
        // Create test customers (role_id 3) - use factory defaults for unique emails
        $this->customers = User::factory()->count(5)->create([
            'role_id' => 3,
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);
        
        // Create one more customer with different name for search testing
        $this->customers->push(User::factory()->create([
            'role_id' => 3,
            'first_name' => 'Jane',
            'last_name' => 'Smith'
        ]));
    }

    /** @test */
    public function it_can_search_customers_by_first_name()
    {
        $component = Livewire::actingAs($this->admin)
            ->test(TransactionManagement::class)
            ->set('customerSearch', 'Jane')
            ->call('showCustomerDropdown');

        $customers = $component->get('customers');
        
        $this->assertEquals(1, $customers->count());
        $this->assertEquals('Jane', $customers->first()->first_name);
    }

    /** @test */
    public function it_can_search_customers_by_email()
    {
        // Get the Jane customer's actual email
        $janeCustomer = $this->customers->where('first_name', 'Jane')->first();
        $emailPart = explode('@', $janeCustomer->email)[0]; // Get part before @
        
        $component = Livewire::actingAs($this->admin)
            ->test(TransactionManagement::class)
            ->set('customerSearch', $emailPart)
            ->call('showCustomerDropdown');

        $customers = $component->get('customers');
        
        $this->assertEquals(1, $customers->count());
        $this->assertEquals($janeCustomer->email, $customers->first()->email);
    }

    /** @test */
    public function it_can_search_customers_by_full_name()
    {
        $component = Livewire::actingAs($this->admin)
            ->test(TransactionManagement::class)
            ->set('customerSearch', 'Jane Smith')
            ->call('showCustomerDropdown');

        $customers = $component->get('customers');
        
        $this->assertEquals(1, $customers->count());
        $this->assertEquals('Jane', $customers->first()->first_name);
        $this->assertEquals('Smith', $customers->first()->last_name);
    }

    /** @test */
    public function it_can_select_a_customer()
    {
        $customer = $this->customers->first();
        
        $component = Livewire::actingAs($this->admin)
            ->test(TransactionManagement::class)
            ->call('selectCustomer', $customer->id, $customer->full_name);

        $component->assertSet('filterCustomer', $customer->id)
                 ->assertSet('selectedCustomerName', $customer->full_name)
                 ->assertSet('customerSearch', '')
                 ->assertSet('showCustomerDropdown', false);
    }

    /** @test */
    public function it_can_clear_customer_filter()
    {
        $customer = $this->customers->first();
        
        $component = Livewire::actingAs($this->admin)
            ->test(TransactionManagement::class)
            ->set('filterCustomer', $customer->id)
            ->set('selectedCustomerName', $customer->full_name)
            ->call('clearCustomerFilter');

        $component->assertSet('filterCustomer', '')
                 ->assertSet('selectedCustomerName', '')
                 ->assertSet('customerSearch', '')
                 ->assertSet('showCustomerDropdown', false);
    }

    /** @test */
    public function it_can_select_first_customer_with_keyboard()
    {
        $component = Livewire::actingAs($this->admin)
            ->test(TransactionManagement::class)
            ->set('customerSearch', 'Jane')
            ->call('selectFirstCustomer');

        $customers = $component->get('customers');
        $firstCustomer = $customers->first();

        $component->assertSet('filterCustomer', $firstCustomer->id)
                 ->assertSet('selectedCustomerName', $firstCustomer->full_name);
    }

    /** @test */
    public function it_shows_and_hides_dropdown_correctly()
    {
        $component = Livewire::actingAs($this->admin)
            ->test(TransactionManagement::class);

        // Show dropdown
        $component->call('showCustomerDropdown')
                 ->assertSet('showCustomerDropdown', true);

        // Hide dropdown
        $component->call('hideCustomerDropdown')
                 ->assertSet('showCustomerDropdown', false);
    }

    /** @test */
    public function it_filters_transactions_by_selected_customer()
    {
        $customer = $this->customers->first();
        $otherCustomer = $this->customers->last();
        
        // Create transactions for different customers
        CustomerTransaction::create([
            'user_id' => $customer->id,
            'type' => 'payment',
            'amount' => 100.00,
            'balance_before' => 0.00,
            'balance_after' => 100.00,
            'description' => 'Test transaction 1',
            'created_by' => $this->admin->id
        ]);
        
        CustomerTransaction::create([
            'user_id' => $otherCustomer->id,
            'type' => 'payment',
            'amount' => 200.00,
            'balance_before' => 0.00,
            'balance_after' => 200.00,
            'description' => 'Test transaction 2',
            'created_by' => $this->admin->id
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(TransactionManagement::class)
            ->set('filterCustomer', $customer->id);

        $transactions = $component->get('transactions');
        
        // Should only show transactions for the selected customer
        $this->assertGreaterThan(0, $transactions->count());
        foreach ($transactions as $transaction) {
            $this->assertEquals($customer->id, $transaction->user_id);
        }
    }

    /** @test */
    public function it_limits_customer_search_results()
    {
        // Create more customers than the limit (15)
        User::factory()->count(20)->create(['role_id' => 3]);

        $component = Livewire::actingAs($this->admin)
            ->test(TransactionManagement::class)
            ->set('customerSearch', 'test'); // This should match many customers

        $customers = $component->get('customers');
        
        // Should be limited to 15 results
        $this->assertLessThanOrEqual(15, $customers->count());
    }
}