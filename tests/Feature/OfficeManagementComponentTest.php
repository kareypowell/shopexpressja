<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Office;
use App\Models\Manifest;
use App\Models\Package;
use App\Models\Profile;
use App\Http\Livewire\Admin\OfficeManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class OfficeManagementComponentTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customer;
    protected $adminRole;
    protected $customerRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->customerRole = Role::factory()->create(['name' => 'customer']);

        // Create admin user
        $this->admin = User::factory()->create([
            'role_id' => $this->adminRole->id,
            'first_name' => 'Admin',
            'last_name' => 'User'
        ]);

        // Create customer user
        $this->customer = User::factory()->create([
            'role_id' => $this->customerRole->id,
            'first_name' => 'Customer',
            'last_name' => 'User'
        ]);
    }

    /** @test */
    public function admin_can_access_office_management_component()
    {
        $component = Livewire::actingAs($this->admin)
            ->test(OfficeManagement::class);

        $component->assertStatus(200);
    }

    /** @test */
    public function customer_cannot_access_office_management_component()
    {
        // Ensure the customer has the role relationship loaded
        $this->customer->load('role');
        
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::actingAs($this->customer)
            ->test(OfficeManagement::class);
    }

    /** @test */
    public function it_displays_offices_list()
    {
        $office1 = Office::factory()->create(['name' => 'Main Office']);
        $office2 = Office::factory()->create(['name' => 'Branch Office']);

        $component = Livewire::actingAs($this->admin)
            ->test(OfficeManagement::class);

        $component->assertSee('Main Office')
                 ->assertSee('Branch Office');
    }

    /** @test */
    public function it_can_search_offices_by_name()
    {
        $office1 = Office::factory()->create(['name' => 'Main Office']);
        $office2 = Office::factory()->create(['name' => 'Branch Office']);

        $component = Livewire::actingAs($this->admin)
            ->test(OfficeManagement::class);

        $component->set('search', 'Main')
                 ->assertSee('Main Office')
                 ->assertDontSee('Branch Office');
    }

    /** @test */
    public function it_can_search_offices_by_address()
    {
        $office1 = Office::factory()->create([
            'name' => 'Office A',
            'address' => '123 Kingston Street'
        ]);
        $office2 = Office::factory()->create([
            'name' => 'Office B',
            'address' => '456 Spanish Town Road'
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(OfficeManagement::class);

        $component->set('search', 'Kingston')
                 ->assertSee('Office A')
                 ->assertDontSee('Office B');
    }

    /** @test */
    public function it_updates_search_results_in_real_time()
    {
        $office1 = Office::factory()->create(['name' => 'Main Office']);
        $office2 = Office::factory()->create(['name' => 'Branch Office']);

        $component = Livewire::actingAs($this->admin)
            ->test(OfficeManagement::class);

        // Initially shows all offices
        $component->assertSee('Main Office')
                 ->assertSee('Branch Office');

        // Search for specific office
        $component->set('search', 'Main')
                 ->assertSee('Main Office')
                 ->assertDontSee('Branch Office')
                 ->assertSet('showSearchResults', true);

        // Clear search
        $component->call('clearSearch')
                 ->assertSee('Main Office')
                 ->assertSee('Branch Office')
                 ->assertSet('showSearchResults', false)
                 ->assertSet('search', '');
    }

    /** @test */
    public function it_displays_office_relationship_counts()
    {
        $office = Office::factory()->create(['name' => 'Test Office']);
        
        // Create related records
        $manifest = Manifest::factory()->create();
        $package = Package::factory()->create([
            'office_id' => $office->id,
            'manifest_id' => $manifest->id
        ]);
        $profile = Profile::factory()->create(['pickup_location' => $office->id]);

        $component = Livewire::actingAs($this->admin)
            ->test(OfficeManagement::class);

        $component->assertSee('1') // Should see count of 1 for each relationship
                 ->assertSee('Manifests')
                 ->assertSee('Packages')
                 ->assertSee('Profiles');
    }

    /** @test */
    public function it_can_confirm_delete_office()
    {
        $office = Office::factory()->create(['name' => 'Test Office']);

        $component = Livewire::actingAs($this->admin)
            ->test(OfficeManagement::class);

        $component->call('confirmDelete', $office->id)
                 ->assertSet('showDeleteModal', true)
                 ->assertSet('selectedOffice.id', $office->id);
    }

    /** @test */
    public function it_can_delete_office_without_relationships()
    {
        $office = Office::factory()->create(['name' => 'Test Office']);

        $component = Livewire::actingAs($this->admin)
            ->test(OfficeManagement::class);

        $component->call('confirmDelete', $office->id)
                 ->call('deleteOffice')
                 ->assertSet('showDeleteModal', false)
                 ->assertSet('selectedOffice', null);

        $this->assertDatabaseMissing('offices', ['id' => $office->id]);
    }

    /** @test */
    public function it_prevents_deletion_of_office_with_relationships()
    {
        $office = Office::factory()->create(['name' => 'Test Office']);
        
        // Create a related package (which creates the relationship)
        Package::factory()->create(['office_id' => $office->id]);

        $component = Livewire::actingAs($this->admin)
            ->test(OfficeManagement::class);

        $component->call('confirmDelete', $office->id)
                 ->call('deleteOffice')
                 ->assertHasErrors([])
                 ->assertSet('errorMessage', 'Cannot delete office "Test Office" because it has associated records. Please reassign or remove the associated records first.');

        $this->assertDatabaseHas('offices', ['id' => $office->id]);
    }

    /** @test */
    public function it_can_cancel_delete_operation()
    {
        $office = Office::factory()->create(['name' => 'Test Office']);

        $component = Livewire::actingAs($this->admin)
            ->test(OfficeManagement::class);

        $component->call('confirmDelete', $office->id)
                 ->assertSet('showDeleteModal', true)
                 ->call('cancelDelete')
                 ->assertSet('showDeleteModal', false)
                 ->assertSet('selectedOffice', null);
    }

    /** @test */
    public function it_displays_relationship_counts_in_delete_modal()
    {
        $office = Office::factory()->create(['name' => 'Test Office']);
        
        // Create related records
        $manifest1 = Manifest::factory()->create();
        $manifest2 = Manifest::factory()->create();
        Package::factory()->count(2)->create([
            'office_id' => $office->id,
            'manifest_id' => $manifest1->id
        ]);
        Package::factory()->create([
            'office_id' => $office->id,
            'manifest_id' => $manifest2->id
        ]);
        Profile::factory()->count(1)->create(['pickup_location' => $office->id]);

        $component = Livewire::actingAs($this->admin)
            ->test(OfficeManagement::class);

        $component->call('confirmDelete', $office->id);

        $relationshipCounts = $component->get('relationshipCounts');
        $this->assertEquals(2, $relationshipCounts['manifests']);
        $this->assertEquals(3, $relationshipCounts['packages']);
        $this->assertEquals(1, $relationshipCounts['profiles']);
    }

    /** @test */
    public function it_paginates_offices_correctly()
    {
        // Create more than 15 offices to test pagination
        Office::factory()->count(20)->create();

        $component = Livewire::actingAs($this->admin)
            ->test(OfficeManagement::class);

        // Should show 15 offices per page
        $offices = $component->get('offices');
        $this->assertEquals(15, $offices->perPage());
        $this->assertEquals(20, $offices->total());
    }

    /** @test */
    public function it_resets_page_when_searching()
    {
        // Create offices to trigger pagination
        Office::factory()->count(20)->create();
        Office::factory()->create(['name' => 'Special Office']);

        $component = Livewire::actingAs($this->admin)
            ->test(OfficeManagement::class);

        // Go to page 2
        $component->set('page', 2);

        // Search should reset to page 1
        $component->set('search', 'Special')
                 ->assertSet('page', 1);
    }

    /** @test */
    public function it_shows_empty_state_when_no_offices_exist()
    {
        $component = Livewire::actingAs($this->admin)
            ->test(OfficeManagement::class);

        $component->assertSee('No offices yet')
                 ->assertSee('Get started by creating your first office location');
    }

    /** @test */
    public function it_shows_no_results_message_when_search_returns_empty()
    {
        Office::factory()->create(['name' => 'Test Office']);

        $component = Livewire::actingAs($this->admin)
            ->test(OfficeManagement::class);

        $component->set('search', 'NonExistentOffice')
                 ->assertSee('No offices found')
                 ->assertSee('Try adjusting your search criteria');
    }

    /** @test */
    public function it_displays_search_summary()
    {
        Office::factory()->count(3)->create([
            'name' => 'Main Office 1',
            'address' => 'Address 1'
        ]);
        Office::factory()->count(3)->create([
            'name' => 'Main Office 2', 
            'address' => 'Address 2'
        ]);
        Office::factory()->count(3)->create([
            'name' => 'Main Office 3',
            'address' => 'Address 3'
        ]);
        Office::factory()->count(2)->create([
            'name' => 'Branch Office 1',
            'address' => 'Branch Address 1'
        ]);
        Office::factory()->count(2)->create([
            'name' => 'Branch Office 2',
            'address' => 'Branch Address 2'
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(OfficeManagement::class);

        $component->set('search', 'Main');

        $searchSummary = $component->get('searchSummary');
        $this->assertEquals(9, $searchSummary['total_count']);
        $this->assertEquals('Main', $searchSummary['search_term']);
    }

    /** @test */
    public function it_can_clear_success_and_error_messages()
    {
        $component = Livewire::actingAs($this->admin)
            ->test(OfficeManagement::class);

        $component->set('successMessage', 'Test success message')
                 ->set('errorMessage', 'Test error message')
                 ->call('clearMessages')
                 ->assertSet('successMessage', '')
                 ->assertSet('errorMessage', '');
    }
}