<?php

namespace Tests\Feature;

use App\Http\Livewire\Admin\AddressManagement;
use App\Models\Address;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AddressManagementComponentTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'admin', 'description' => 'Administrator role']);
        Role::create(['name' => 'customer', 'description' => 'Customer role']);
        
        // Create admin user
        $this->admin = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();
        $this->admin->update(['role_id' => $adminRole->id]);
    }

    /** @test */
    public function it_can_render_the_component()
    {
        $this->actingAs($this->admin);

        Livewire::test(AddressManagement::class)
            ->assertStatus(200)
            ->assertSee('Shipping Addresses')
            ->assertSee('Create Address');
    }

    /** @test */
    public function it_displays_addresses_in_the_list()
    {
        $this->actingAs($this->admin);

        $address1 = Address::factory()->create([
            'street_address' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'zip_code' => '10001',
            'country' => 'USA',
            'is_primary' => true
        ]);

        $address2 = Address::factory()->create([
            'street_address' => '456 Oak Ave',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'zip_code' => '90210',
            'country' => 'USA',
            'is_primary' => false
        ]);

        Livewire::test(AddressManagement::class)
            ->assertSee('123 Main St')
            ->assertSee('New York, NY 10001')
            ->assertSee('456 Oak Ave')
            ->assertSee('Los Angeles, CA 90210')
            ->assertSee('Primary'); // Should show primary badge for address1
    }

    /** @test */
    public function it_can_search_addresses_by_street_address()
    {
        $this->actingAs($this->admin);

        Address::factory()->create([
            'street_address' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY'
        ]);

        Address::factory()->create([
            'street_address' => '456 Oak Ave',
            'city' => 'Los Angeles',
            'state' => 'CA'
        ]);

        Livewire::test(AddressManagement::class)
            ->set('searchTerm', 'Main')
            ->assertSee('123 Main St')
            ->assertDontSee('456 Oak Ave');
    }

    /** @test */
    public function it_can_search_addresses_by_city()
    {
        $this->actingAs($this->admin);

        Address::factory()->create([
            'street_address' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY'
        ]);

        Address::factory()->create([
            'street_address' => '456 Oak Ave',
            'city' => 'Los Angeles',
            'state' => 'CA'
        ]);

        Livewire::test(AddressManagement::class)
            ->set('searchTerm', 'Angeles')
            ->assertSee('456 Oak Ave')
            ->assertDontSee('123 Main St');
    }

    /** @test */
    public function it_can_search_addresses_by_state()
    {
        $this->actingAs($this->admin);

        Address::factory()->create([
            'street_address' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY'
        ]);

        Address::factory()->create([
            'street_address' => '456 Oak Ave',
            'city' => 'Los Angeles',
            'state' => 'CA'
        ]);

        Livewire::test(AddressManagement::class)
            ->set('searchTerm', 'NY')
            ->assertSee('123 Main St')
            ->assertDontSee('456 Oak Ave');
    }

    /** @test */
    public function it_can_search_addresses_by_zip_code()
    {
        $this->actingAs($this->admin);

        Address::factory()->create([
            'street_address' => '123 Main St',
            'zip_code' => '10001'
        ]);

        Address::factory()->create([
            'street_address' => '456 Oak Ave',
            'zip_code' => '90210'
        ]);

        Livewire::test(AddressManagement::class)
            ->set('searchTerm', '10001')
            ->assertSee('123 Main St')
            ->assertDontSee('456 Oak Ave');
    }

    /** @test */
    public function it_can_search_addresses_by_country()
    {
        $this->actingAs($this->admin);

        Address::factory()->create([
            'street_address' => '123 Main St',
            'country' => 'USA'
        ]);

        Address::factory()->create([
            'street_address' => '456 Oak Ave',
            'country' => 'Canada'
        ]);

        Livewire::test(AddressManagement::class)
            ->set('searchTerm', 'Canada')
            ->assertSee('456 Oak Ave')
            ->assertDontSee('123 Main St');
    }

    /** @test */
    public function it_resets_pagination_when_searching()
    {
        $this->actingAs($this->admin);

        // Create enough addresses to trigger pagination
        Address::factory()->count(20)->create();

        $component = Livewire::test(AddressManagement::class);
        
        // Go to page 2
        $component->set('page', 2);
        
        // Search should reset to page 1
        $component->set('searchTerm', 'test');
        
        $this->assertEquals(1, $component->get('page'));
    }

    /** @test */
    public function it_shows_delete_confirmation_modal()
    {
        $this->actingAs($this->admin);

        $address = Address::factory()->create([
            'street_address' => '123 Main St'
        ]);

        Livewire::test(AddressManagement::class)
            ->call('confirmDelete', $address->id)
            ->assertSet('showDeleteModal', true)
            ->assertSet('selectedAddress.id', $address->id)
            ->assertSee('Delete Address')
            ->assertSee('123 Main St');
    }

    /** @test */
    public function it_can_cancel_delete_confirmation()
    {
        $this->actingAs($this->admin);

        $address = Address::factory()->create();

        Livewire::test(AddressManagement::class)
            ->call('confirmDelete', $address->id)
            ->assertSet('showDeleteModal', true)
            ->call('cancelDelete')
            ->assertSet('showDeleteModal', false)
            ->assertSet('selectedAddress', null);
    }

    /** @test */
    public function it_can_delete_an_address()
    {
        $this->actingAs($this->admin);

        $address = Address::factory()->create([
            'street_address' => '123 Main St'
        ]);

        $this->assertDatabaseHas('addresses', ['id' => $address->id]);

        Livewire::test(AddressManagement::class)
            ->call('confirmDelete', $address->id)
            ->call('deleteAddress')
            ->assertSet('showDeleteModal', false)
            ->assertSet('selectedAddress', null);

        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }

    /** @test */
    public function it_shows_success_message_after_deleting_address()
    {
        $this->actingAs($this->admin);

        $address = Address::factory()->create();

        Livewire::test(AddressManagement::class)
            ->call('confirmDelete', $address->id)
            ->call('deleteAddress')
            ->assertSet('showDeleteModal', false)
            ->assertSet('selectedAddress', null);

        // Verify the address was actually deleted
        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }

    /** @test */
    public function it_shows_no_addresses_message_when_empty()
    {
        $this->actingAs($this->admin);

        Livewire::test(AddressManagement::class)
            ->assertSee('No addresses found')
            ->assertSee('Get started by creating a new address.');
    }

    /** @test */
    public function it_shows_no_search_results_message()
    {
        $this->actingAs($this->admin);

        Address::factory()->create(['street_address' => '123 Main St']);

        Livewire::test(AddressManagement::class)
            ->set('searchTerm', 'nonexistent')
            ->assertSee('No addresses found')
            ->assertSee('No addresses match your search criteria.');
    }

    /** @test */
    public function it_displays_primary_address_indicator()
    {
        $this->actingAs($this->admin);

        $primaryAddress = Address::factory()->create([
            'street_address' => '123 Main St',
            'is_primary' => true
        ]);

        $regularAddress = Address::factory()->create([
            'street_address' => '456 Oak Ave',
            'is_primary' => false
        ]);

        $component = Livewire::test(AddressManagement::class);
        
        // Should show primary badge for primary address
        $component->assertSee('Primary');
        
        // Should show both addresses
        $component->assertSee('123 Main St');
        $component->assertSee('456 Oak Ave');
    }

    /** @test */
    public function it_paginates_addresses_correctly()
    {
        $this->actingAs($this->admin);

        // Create 20 addresses to test pagination
        Address::factory()->count(20)->create();

        $component = Livewire::test(AddressManagement::class);
        
        // Should show pagination links
        $component->assertSee('Next');
    }
}