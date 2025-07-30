<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Manifest;
use App\Http\Livewire\Manifests\Packages\ManifestPackage;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test customers with profiles
        $this->customer1 = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'role_id' => 3, // Customer role
            'email_verified_at' => now(),
        ]);
        
        Profile::create([
            'user_id' => $this->customer1->id,
            'account_number' => 'ACC001',
            'tax_number' => 'TAX001',
            'telephone_number' => '123-456-7890',
            'street_address' => '123 Main St',
            'city_town' => 'Kingston',
            'parish' => 'St. Andrew',
        ]);
        
        $this->customer2 = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'role_id' => 3, // Customer role
            'email_verified_at' => now(),
        ]);
        
        Profile::create([
            'user_id' => $this->customer2->id,
            'account_number' => 'ACC002',
            'tax_number' => 'TAX002',
            'telephone_number' => '123-456-7891',
            'street_address' => '456 Oak St',
            'city_town' => 'Kingston',
            'parish' => 'St. Andrew',
        ]);
        
        // Create a test manifest
        $this->manifest = Manifest::factory()->create([
            'type' => 'air',
        ]);
    }

    /** @test */
    public function it_can_search_customers_by_name()
    {
        Livewire::test(ManifestPackage::class)
            ->set('manifest_id', $this->manifest->id)
            ->call('create') // Open the modal
            ->set('customerSearch', 'John')
            ->assertSet('showCustomerDropdown', true)
            ->assertCount('filteredCustomers', 1)
            ->assertSee('John Doe');
    }

    /** @test */
    public function it_can_search_customers_by_account_number()
    {
        Livewire::test(ManifestPackage::class)
            ->set('manifest_id', $this->manifest->id)
            ->call('create') // Open the modal
            ->set('customerSearch', 'ACC002')
            ->assertSet('showCustomerDropdown', true)
            ->assertCount('filteredCustomers', 1)
            ->assertSee('Jane Smith');
    }

    /** @test */
    public function it_can_select_customer_from_search_results()
    {
        Livewire::test(ManifestPackage::class)
            ->set('manifest_id', $this->manifest->id)
            ->call('create') // Open the modal
            ->call('selectCustomer', $this->customer1->id)
            ->assertSet('user_id', $this->customer1->id)
            ->assertSet('showCustomerDropdown', false)
            ->assertSee('John Doe (ACC001)');
    }

    /** @test */
    public function it_shows_no_results_message_when_no_customers_match()
    {
        Livewire::test(ManifestPackage::class)
            ->set('manifest_id', $this->manifest->id)
            ->call('create') // Open the modal
            ->set('customerSearch', 'NonExistentCustomer')
            ->assertSet('showCustomerDropdown', true)
            ->assertCount('filteredCustomers', 0)
            ->assertSee('No customers found matching "NonExistentCustomer"', false);
    }

    /** @test */
    public function it_can_clear_customer_selection()
    {
        Livewire::test(ManifestPackage::class)
            ->set('manifest_id', $this->manifest->id)
            ->call('create') // Open the modal
            ->set('user_id', $this->customer1->id)
            ->set('customerSearch', 'John Doe (ACC001)')
            ->call('clearCustomerSelection')
            ->assertSet('user_id', 0)
            ->assertSet('customerSearch', '')
            ->assertSet('showCustomerDropdown', false);
    }

    /** @test */
    public function it_limits_search_results_to_10_customers()
    {
        // Create 15 customers
        for ($i = 3; $i <= 17; $i++) {
            $customer = User::factory()->create([
                'first_name' => 'Customer',
                'last_name' => "Test{$i}",
                'email' => "customer{$i}@example.com",
                'role_id' => 3,
                'email_verified_at' => now(),
            ]);
            
            Profile::create([
                'user_id' => $customer->id,
                'account_number' => "ACC{$i}",
                'tax_number' => "TAX{$i}",
                'telephone_number' => "123-456-{$i}",
                'street_address' => "{$i} Test St",
                'city_town' => 'Kingston',
                'parish' => 'St. Andrew',
            ]);
        }

        Livewire::test(ManifestPackage::class)
            ->set('manifest_id', $this->manifest->id)
            ->call('create') // Open the modal
            ->set('customerSearch', 'Customer')
            ->assertSet('showCustomerDropdown', true)
            ->assertCount('filteredCustomers', 10); // Should be limited to 10
    }
}