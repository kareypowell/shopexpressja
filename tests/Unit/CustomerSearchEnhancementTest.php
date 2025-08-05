<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class CustomerSearchEnhancementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $customerRole;
    protected $adminRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use existing roles created by base TestCase
        $this->customerRole = Role::where('name', 'customer')->first();
        $this->adminRole = Role::where('name', 'admin')->first();
    }

    /** @test */
    public function it_can_search_customers_by_first_name()
    {
        $customer = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role_id' => $this->customerRole->id,
        ]);

        Profile::factory()->create(['user_id' => $customer->id]);

        $results = User::customers()->search('John')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_can_search_customers_by_last_name()
    {
        $customer = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role_id' => $this->customerRole->id,
        ]);

        Profile::factory()->create(['user_id' => $customer->id]);

        $results = User::customers()->search('Doe')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_can_search_customers_by_full_name()
    {
        $customer = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role_id' => $this->customerRole->id,
        ]);

        Profile::factory()->create(['user_id' => $customer->id]);

        $results = User::customers()->search('John Doe')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_can_search_customers_by_email()
    {
        $customer = User::factory()->create([
            'email' => 'john.doe@example.com',
            'role_id' => $this->customerRole->id,
        ]);

        Profile::factory()->create(['user_id' => $customer->id]);

        $results = User::customers()->search('john.doe@example.com')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_can_search_customers_by_partial_email()
    {
        $customer = User::factory()->create([
            'email' => 'john.doe@example.com',
            'role_id' => $this->customerRole->id,
        ]);

        Profile::factory()->create(['user_id' => $customer->id]);

        $results = User::customers()->search('john.doe')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_can_search_customers_by_account_number()
    {
        $customer = User::factory()->create(['role_id' => $this->customerRole->id]);
        Profile::factory()->create([
            'user_id' => $customer->id,
            'account_number' => 'ACC123456',
        ]);

        $results = User::customers()->search('ACC123456')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_can_search_customers_by_tax_number()
    {
        $customer = User::factory()->create(['role_id' => $this->customerRole->id]);
        Profile::factory()->create([
            'user_id' => $customer->id,
            'tax_number' => 'TRN123456789',
        ]);

        $results = User::customers()->search('TRN123456789')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_can_search_customers_by_telephone_number()
    {
        $customer = User::factory()->create(['role_id' => $this->customerRole->id]);
        Profile::factory()->create([
            'user_id' => $customer->id,
            'telephone_number' => '876-555-1234',
        ]);

        $results = User::customers()->search('876-555-1234')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_can_search_customers_by_parish()
    {
        $customer = User::factory()->create(['role_id' => $this->customerRole->id]);
        Profile::factory()->create([
            'user_id' => $customer->id,
            'parish' => 'Kingston',
        ]);

        $results = User::customers()->search('Kingston')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_can_search_customers_by_street_address()
    {
        $customer = User::factory()->create(['role_id' => $this->customerRole->id]);
        Profile::factory()->create([
            'user_id' => $customer->id,
            'street_address' => '123 Main Street',
        ]);

        $results = User::customers()->search('Main Street')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_can_search_customers_by_city_town()
    {
        $customer = User::factory()->create(['role_id' => $this->customerRole->id]);
        Profile::factory()->create([
            'user_id' => $customer->id,
            'city_town' => 'Spanish Town',
        ]);

        $results = User::customers()->search('Spanish Town')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_can_search_customers_by_multiple_terms()
    {
        $customer = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role_id' => $this->customerRole->id,
        ]);

        Profile::factory()->create([
            'user_id' => $customer->id,
            'parish' => 'Kingston',
        ]);

        $results = User::customers()->search('John Kingston')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_returns_empty_results_for_non_matching_search()
    {
        $customer = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role_id' => $this->customerRole->id,
        ]);

        Profile::factory()->create(['user_id' => $customer->id]);

        $results = User::customers()->search('NonExistentName')->get();

        $this->assertCount(0, $results);
    }

    /** @test */
    public function it_handles_empty_search_term()
    {
        $customer = User::factory()->create(['role_id' => $this->customerRole->id]);
        Profile::factory()->create(['user_id' => $customer->id]);

        $results = User::customers()->search('')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_can_perform_advanced_search_by_name()
    {
        $customer = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role_id' => $this->customerRole->id,
        ]);

        Profile::factory()->create(['user_id' => $customer->id]);

        $results = User::customers()->advancedSearch(['name' => 'John'])->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_can_perform_advanced_search_by_email()
    {
        $customer = User::factory()->create([
            'email' => 'john.doe@example.com',
            'role_id' => $this->customerRole->id,
        ]);

        Profile::factory()->create(['user_id' => $customer->id]);

        $results = User::customers()->advancedSearch(['email' => 'john.doe'])->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_can_perform_advanced_search_by_account_number()
    {
        $customer = User::factory()->create(['role_id' => $this->customerRole->id]);
        Profile::factory()->create([
            'user_id' => $customer->id,
            'account_number' => 'ACC123456',
        ]);

        $results = User::customers()->advancedSearch(['account_number' => 'ACC123'])->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_can_perform_advanced_search_by_tax_number()
    {
        $customer = User::factory()->create(['role_id' => $this->customerRole->id]);
        Profile::factory()->create([
            'user_id' => $customer->id,
            'tax_number' => 'TRN123456789',
        ]);

        $results = User::customers()->advancedSearch(['tax_number' => 'TRN123'])->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_can_perform_advanced_search_by_telephone_number()
    {
        $customer = User::factory()->create(['role_id' => $this->customerRole->id]);
        Profile::factory()->create([
            'user_id' => $customer->id,
            'telephone_number' => '876-555-1234',
        ]);

        $results = User::customers()->advancedSearch(['telephone_number' => '876-555'])->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_can_perform_advanced_search_by_parish()
    {
        $customer = User::factory()->create(['role_id' => $this->customerRole->id]);
        Profile::factory()->create([
            'user_id' => $customer->id,
            'parish' => 'Kingston',
        ]);

        $results = User::customers()->advancedSearch(['parish' => 'Kingston'])->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_can_perform_advanced_search_by_address()
    {
        $customer = User::factory()->create(['role_id' => $this->customerRole->id]);
        Profile::factory()->create([
            'user_id' => $customer->id,
            'street_address' => '123 Main Street',
            'city_town' => 'Spanish Town',
        ]);

        $results = User::customers()->advancedSearch(['address' => 'Main Street'])->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_can_perform_advanced_search_by_registration_date_range()
    {
        $customer = User::factory()->create([
            'role_id' => $this->customerRole->id,
            'created_at' => '2024-01-15',
        ]);

        Profile::factory()->create(['user_id' => $customer->id]);

        $results = User::customers()->advancedSearch([
            'registration_date_from' => '2024-01-01',
            'registration_date_to' => '2024-01-31',
        ])->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }

    /** @test */
    public function it_can_perform_advanced_search_by_status()
    {
        $activeCustomer = User::factory()->create(['role_id' => $this->customerRole->id]);
        Profile::factory()->create(['user_id' => $activeCustomer->id]);

        $deletedCustomer = User::factory()->create(['role_id' => $this->customerRole->id]);
        Profile::factory()->create(['user_id' => $deletedCustomer->id]);
        $deletedCustomer->delete();

        // Search for active customers
        $activeResults = User::withTrashed()->customers()->advancedSearch(['status' => 'active'])->get();
        $this->assertCount(1, $activeResults);
        $this->assertEquals($activeCustomer->id, $activeResults->first()->id);

        // Search for deleted customers
        $deletedResults = User::withTrashed()->customers()->advancedSearch(['status' => 'deleted'])->get();
        $this->assertCount(1, $deletedResults);
        $this->assertEquals($deletedCustomer->id, $deletedResults->first()->id);
    }

    /** @test */
    public function it_can_combine_multiple_advanced_search_criteria()
    {
        $customer1 = User::factory()->create([
            'first_name' => 'John',
            'email' => 'john@example.com',
            'role_id' => $this->customerRole->id,
        ]);

        Profile::factory()->create([
            'user_id' => $customer1->id,
            'parish' => 'Kingston',
        ]);

        $customer2 = User::factory()->create([
            'first_name' => 'Jane',
            'email' => 'jane@example.com',
            'role_id' => $this->customerRole->id,
        ]);

        Profile::factory()->create([
            'user_id' => $customer2->id,
            'parish' => 'St. Andrew',
        ]);

        $results = User::customers()->advancedSearch([
            'name' => 'John',
            'parish' => 'Kingston',
        ])->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer1->id, $results->first()->id);
    }

    /** @test */
    public function it_ignores_empty_advanced_search_criteria()
    {
        $customer = User::factory()->create(['role_id' => $this->customerRole->id]);
        Profile::factory()->create(['user_id' => $customer->id]);

        $results = User::customers()->advancedSearch([
            'name' => '',
            'email' => '',
            'account_number' => '',
        ])->get();

        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->id);
    }
}