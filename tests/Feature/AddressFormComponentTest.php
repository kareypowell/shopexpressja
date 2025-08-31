<?php

namespace Tests\Feature;

use App\Http\Livewire\Admin\AddressForm;
use App\Models\Address;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AddressFormComponentTest extends TestCase
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
    public function it_can_render_the_create_form()
    {
        $this->actingAs($this->admin);

        Livewire::test(AddressForm::class)
            ->assertStatus(200)
            ->assertSee('Street Address')
            ->assertSee('City')
            ->assertSee('State/Province')
            ->assertSee('ZIP/Postal Code')
            ->assertSee('Country')
            ->assertSee('Primary Address')
            ->assertSee('Create Address');
    }

    /** @test */
    public function it_can_render_the_edit_form()
    {
        $this->actingAs($this->admin);

        $address = Address::factory()->create([
            'street_address' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'zip_code' => '10001',
            'country' => 'USA',
            'is_primary' => true
        ]);

        Livewire::test(AddressForm::class, ['address' => $address])
            ->assertStatus(200)
            ->assertSet('street_address', '123 Main St')
            ->assertSet('city', 'New York')
            ->assertSet('state', 'NY')
            ->assertSet('zip_code', '10001')
            ->assertSet('country', 'USA')
            ->assertSet('is_primary', true)
            ->assertSet('isEditing', true)
            ->assertSee('Update Address');
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->actingAs($this->admin);

        Livewire::test(AddressForm::class)
            ->call('save')
            ->assertHasErrors([
                'street_address' => 'required',
                'city' => 'required',
                'state' => 'required',
                'zip_code' => 'required',
                'country' => 'required'
            ]);
    }

    /** @test */
    public function it_validates_field_lengths()
    {
        $this->actingAs($this->admin);

        Livewire::test(AddressForm::class)
            ->set('street_address', str_repeat('a', 256)) // Too long
            ->set('city', str_repeat('b', 101)) // Too long
            ->set('state', str_repeat('c', 101)) // Too long
            ->set('zip_code', str_repeat('d', 21)) // Too long
            ->set('country', str_repeat('e', 101)) // Too long
            ->call('save')
            ->assertHasErrors([
                'street_address' => 'max',
                'city' => 'max',
                'state' => 'max',
                'zip_code' => 'max',
                'country' => 'max'
            ]);
    }

    /** @test */
    public function it_can_create_a_new_address()
    {
        $this->actingAs($this->admin);

        Livewire::test(AddressForm::class)
            ->set('street_address', '123 Main St')
            ->set('city', 'New York')
            ->set('state', 'NY')
            ->set('zip_code', '10001')
            ->set('country', 'USA')
            ->set('is_primary', false)
            ->call('save');

        $this->assertDatabaseHas('addresses', [
            'street_address' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'zip_code' => '10001',
            'country' => 'USA',
            'is_primary' => false
        ]);
    }

    /** @test */
    public function it_can_create_a_primary_address()
    {
        $this->actingAs($this->admin);

        Livewire::test(AddressForm::class)
            ->set('street_address', '123 Main St')
            ->set('city', 'New York')
            ->set('state', 'NY')
            ->set('zip_code', '10001')
            ->set('country', 'USA')
            ->set('is_primary', true)
            ->call('save');

        $this->assertDatabaseHas('addresses', [
            'street_address' => '123 Main St',
            'is_primary' => true
        ]);
    }

    /** @test */
    public function it_can_update_an_existing_address()
    {
        $this->actingAs($this->admin);

        $address = Address::factory()->create([
            'street_address' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'zip_code' => '10001',
            'country' => 'USA',
            'is_primary' => false
        ]);

        Livewire::test(AddressForm::class, ['address' => $address])
            ->set('street_address', '456 Oak Ave')
            ->set('city', 'Los Angeles')
            ->set('state', 'CA')
            ->set('zip_code', '90210')
            ->set('country', 'USA')
            ->set('is_primary', true)
            ->call('save');

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'street_address' => '456 Oak Ave',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'zip_code' => '90210',
            'country' => 'USA',
            'is_primary' => true
        ]);
    }

    /** @test */
    public function it_enforces_single_primary_address_constraint_on_create()
    {
        $this->actingAs($this->admin);

        // Create an existing primary address
        $existingPrimary = Address::factory()->create([
            'street_address' => '123 Main St',
            'is_primary' => true
        ]);

        // Create a new primary address
        Livewire::test(AddressForm::class)
            ->set('street_address', '456 Oak Ave')
            ->set('city', 'Los Angeles')
            ->set('state', 'CA')
            ->set('zip_code', '90210')
            ->set('country', 'USA')
            ->set('is_primary', true)
            ->call('save');

        // The existing primary should no longer be primary
        $existingPrimary->refresh();
        $this->assertFalse($existingPrimary->is_primary);

        // The new address should be primary
        $this->assertDatabaseHas('addresses', [
            'street_address' => '456 Oak Ave',
            'is_primary' => true
        ]);
    }

    /** @test */
    public function it_enforces_single_primary_address_constraint_on_update()
    {
        $this->actingAs($this->admin);

        // Create two addresses, one primary
        $primaryAddress = Address::factory()->create([
            'street_address' => '123 Main St',
            'is_primary' => true
        ]);

        $regularAddress = Address::factory()->create([
            'street_address' => '456 Oak Ave',
            'is_primary' => false
        ]);

        // Update the regular address to be primary
        Livewire::test(AddressForm::class, ['address' => $regularAddress])
            ->set('street_address', '456 Oak Ave')
            ->set('city', 'Los Angeles')
            ->set('state', 'CA')
            ->set('zip_code', '90210')
            ->set('country', 'USA')
            ->set('is_primary', true)
            ->call('save');

        // The original primary should no longer be primary
        $primaryAddress->refresh();
        $this->assertFalse($primaryAddress->is_primary);

        // The updated address should be primary
        $regularAddress->refresh();
        $this->assertTrue($regularAddress->is_primary);
    }

    /** @test */
    public function it_can_update_address_without_changing_primary_status()
    {
        $this->actingAs($this->admin);

        $address = Address::factory()->create([
            'street_address' => '123 Main St',
            'city' => 'New York',
            'is_primary' => true
        ]);

        Livewire::test(AddressForm::class, ['address' => $address])
            ->set('street_address', '123 Main Street') // Just change the street address
            ->set('city', 'New York City') // And city
            ->call('save');

        $address->refresh();
        $this->assertEquals('123 Main Street', $address->street_address);
        $this->assertEquals('New York City', $address->city);
        $this->assertTrue($address->is_primary); // Should still be primary
    }

    /** @test */
    public function it_shows_validation_errors_for_individual_fields()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AddressForm::class)
            ->set('street_address', '') // Required field
            ->call('save');

        $component->assertHasErrors(['street_address']);
        $component->assertSee('Street address is required.');
    }

    /** @test */
    public function it_shows_custom_validation_messages()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AddressForm::class)
            ->set('street_address', str_repeat('a', 256))
            ->call('save');

        $component->assertHasErrors(['street_address']);
        $component->assertSee('Street address cannot exceed 255 characters.');
    }

    /** @test */
    public function it_initializes_correctly_for_new_address()
    {
        $this->actingAs($this->admin);

        Livewire::test(AddressForm::class)
            ->assertSet('street_address', '')
            ->assertSet('city', '')
            ->assertSet('state', '')
            ->assertSet('zip_code', '')
            ->assertSet('country', '')
            ->assertSet('is_primary', false)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function it_initializes_correctly_for_existing_address()
    {
        $this->actingAs($this->admin);

        $address = Address::factory()->create([
            'street_address' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'zip_code' => '10001',
            'country' => 'USA',
            'is_primary' => true
        ]);

        Livewire::test(AddressForm::class, ['address' => $address])
            ->assertSet('street_address', '123 Main St')
            ->assertSet('city', 'New York')
            ->assertSet('state', 'NY')
            ->assertSet('zip_code', '10001')
            ->assertSet('country', 'USA')
            ->assertSet('is_primary', true)
            ->assertSet('isEditing', true);
    }

    /** @test */
    public function it_can_handle_boolean_primary_field_correctly()
    {
        $this->actingAs($this->admin);

        // Test with checkbox checked (true)
        Livewire::test(AddressForm::class)
            ->set('street_address', '123 Main St')
            ->set('city', 'New York')
            ->set('state', 'NY')
            ->set('zip_code', '10001')
            ->set('country', 'USA')
            ->set('is_primary', true)
            ->call('save');

        $this->assertDatabaseHas('addresses', [
            'street_address' => '123 Main St',
            'is_primary' => 1
        ]);

        // Test with checkbox unchecked (false)
        Livewire::test(AddressForm::class)
            ->set('street_address', '456 Oak Ave')
            ->set('city', 'Los Angeles')
            ->set('state', 'CA')
            ->set('zip_code', '90210')
            ->set('country', 'USA')
            ->set('is_primary', false)
            ->call('save');

        $this->assertDatabaseHas('addresses', [
            'street_address' => '456 Oak Ave',
            'is_primary' => 0
        ]);
    }
}