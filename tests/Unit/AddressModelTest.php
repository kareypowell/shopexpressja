<?php

namespace Tests\Unit;

use App\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->address = Address::factory()->create([
            'street_address' => '123 Test Street',
            'city' => 'Test City',
            'state' => 'Test State',
            'zip_code' => '12345',
            'country' => 'Test Country',
            'is_primary' => false
        ]);
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'street_address',
            'city',
            'state',
            'zip_code',
            'country',
            'is_primary',
        ];
        
        $this->assertEquals($fillable, $this->address->getFillable());
    }

    /** @test */
    public function it_casts_is_primary_to_boolean()
    {
        $this->assertIsBool($this->address->is_primary);
        
        // Test with database value
        $address = Address::create([
            'street_address' => '456 Another Street',
            'city' => 'Another City',
            'state' => 'Another State',
            'zip_code' => '67890',
            'country' => 'Another Country',
            'is_primary' => 1
        ]);
        
        $this->assertIsBool($address->is_primary);
        $this->assertTrue($address->is_primary);
    }

    /** @test */
    public function it_can_search_by_street_address()
    {
        Address::factory()->create(['street_address' => '456 Different Street']);
        
        $results = Address::search('Test Street')->get();
        
        $this->assertEquals(1, $results->count());
        $this->assertEquals('123 Test Street', $results->first()->street_address);
    }

    /** @test */
    public function it_can_search_by_city()
    {
        Address::factory()->create(['city' => 'Different City']);
        
        $results = Address::search('Test City')->get();
        
        $this->assertEquals(1, $results->count());
        $this->assertEquals('Test City', $results->first()->city);
    }

    /** @test */
    public function it_can_search_by_state()
    {
        Address::factory()->create(['state' => 'Different State']);
        
        $results = Address::search('Test State')->get();
        
        $this->assertEquals(1, $results->count());
        $this->assertEquals('Test State', $results->first()->state);
    }

    /** @test */
    public function it_can_search_by_zip_code()
    {
        Address::factory()->create(['zip_code' => '67890']);
        
        $results = Address::search('12345')->get();
        
        $this->assertEquals(1, $results->count());
        $this->assertEquals('12345', $results->first()->zip_code);
    }

    /** @test */
    public function it_can_search_by_country()
    {
        Address::factory()->create(['country' => 'Different Country']);
        
        $results = Address::search('Test Country')->get();
        
        $this->assertEquals(1, $results->count());
        $this->assertEquals('Test Country', $results->first()->country);
    }

    /** @test */
    public function it_can_search_with_partial_matches()
    {
        $results = Address::search('est')->get();
        
        $this->assertGreaterThan(0, $results->count());
        $this->assertTrue($results->contains($this->address));
    }

    /** @test */
    public function it_returns_empty_results_for_no_matches()
    {
        $results = Address::search('nonexistent')->get();
        
        $this->assertEquals(0, $results->count());
    }

    /** @test */
    public function it_ensures_only_one_primary_address_on_create()
    {
        // Create first primary address
        $primary1 = Address::create([
            'street_address' => '111 Primary Street',
            'city' => 'Primary City',
            'state' => 'Primary State',
            'zip_code' => '11111',
            'country' => 'Primary Country',
            'is_primary' => true
        ]);
        
        $this->assertTrue($primary1->fresh()->is_primary);
        
        // Create second primary address
        $primary2 = Address::create([
            'street_address' => '222 Another Primary Street',
            'city' => 'Another Primary City',
            'state' => 'Another Primary State',
            'zip_code' => '22222',
            'country' => 'Another Primary Country',
            'is_primary' => true
        ]);
        
        // First should no longer be primary
        $this->assertFalse($primary1->fresh()->is_primary);
        // Second should be primary
        $this->assertTrue($primary2->fresh()->is_primary);
    }

    /** @test */
    public function it_ensures_only_one_primary_address_on_update()
    {
        // Create first primary address
        $primary1 = Address::create([
            'street_address' => '111 Primary Street',
            'city' => 'Primary City',
            'state' => 'Primary State',
            'zip_code' => '11111',
            'country' => 'Primary Country',
            'is_primary' => true
        ]);
        
        // Update existing address to be primary
        $this->address->update(['is_primary' => true]);
        
        // First should no longer be primary
        $this->assertFalse($primary1->fresh()->is_primary);
        // Updated address should be primary
        $this->assertTrue($this->address->fresh()->is_primary);
    }

    /** @test */
    public function it_allows_multiple_non_primary_addresses()
    {
        $address2 = Address::create([
            'street_address' => '456 Another Street',
            'city' => 'Another City',
            'state' => 'Another State',
            'zip_code' => '67890',
            'country' => 'Another Country',
            'is_primary' => false
        ]);
        
        $this->assertFalse($this->address->is_primary);
        $this->assertFalse($address2->is_primary);
    }

    /** @test */
    public function it_does_not_affect_other_addresses_when_setting_non_primary()
    {
        // Create primary address
        $primary = Address::create([
            'street_address' => '111 Primary Street',
            'city' => 'Primary City',
            'state' => 'Primary State',
            'zip_code' => '11111',
            'country' => 'Primary Country',
            'is_primary' => true
        ]);
        
        // Update another address to non-primary (should not affect the primary)
        $this->address->update(['is_primary' => false]);
        
        $this->assertTrue($primary->fresh()->is_primary);
        $this->assertFalse($this->address->fresh()->is_primary);
    }
}