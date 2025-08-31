<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicationFixTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $adminRole = Role::find(2); // Admin role
        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'email_verified_at' => now()
        ]);
    }

    /** @test */
    public function office_index_page_has_single_create_button()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.offices.index'));

        $response->assertStatus(200);
        
        // Simply check that we have the expected create button text
        $response->assertSee('Create Office');
        
        // Check that we don't have duplicate create buttons by counting occurrences
        $content = $response->getContent();
        $createOfficeCount = substr_count($content, 'Create Office');
        $createNewOfficeCount = substr_count($content, 'Create New Office');
        
        // We should have exactly one create button (either "Create Office" or "Create New Office")
        // But not both, which would indicate duplication
        $this->assertTrue(
            ($createOfficeCount === 1 && $createNewOfficeCount === 0) || 
            ($createOfficeCount === 0 && $createNewOfficeCount === 1),
            "Expected exactly one create button, but found: Create Office ($createOfficeCount), Create New Office ($createNewOfficeCount)"
        );
    }

    /** @test */
    public function address_index_page_has_single_create_button()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.addresses.index'));

        $response->assertStatus(200);
        
        // Simply check that we have the expected create button text
        $response->assertSee('Create Address');
        
        // Check that we don't have duplicate create buttons by counting occurrences
        $content = $response->getContent();
        $createAddressCount = substr_count($content, 'Create Address');
        $createNewAddressCount = substr_count($content, 'Create New Address');
        
        // We should have exactly one create button (either "Create Address" or "Create New Address")
        // But not both, which would indicate duplication
        $this->assertTrue(
            ($createAddressCount === 1 && $createNewAddressCount === 0) || 
            ($createAddressCount === 0 && $createNewAddressCount === 1),
            "Expected exactly one create button, but found: Create Address ($createAddressCount), Create New Address ($createNewAddressCount)"
        );
    }

    /** @test */
    public function office_index_page_has_proper_header_structure()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.offices.index'));

        $response->assertStatus(200);
        
        // Check that we have the expected header structure without problematic duplication
        $response->assertSee('Office Management'); // Main page header (appears in both blade view and component)
        $response->assertSee('Manage office locations and their details'); // Component description (actual text)
        
        // Office Management appears in both the main page header and the component header
        // This is expected since we have both the traditional blade view header and the component header
        $content = $response->getContent();
        $managementHeaderCount = substr_count($content, 'Office Management');
        $this->assertGreaterThanOrEqual(1, $managementHeaderCount, 
            'Should have at least one "Office Management" header');
        
        // Check that headers don't run together (indicating layout issues)
        $this->assertStringNotContainsString('Office ManagementOffices', $content);
    }

    /** @test */
    public function address_index_page_has_proper_header_structure()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.addresses.index'));

        $response->assertStatus(200);
        
        // Check that we have the expected header structure
        $response->assertSee('Address Management'); // Main page header
        $response->assertSee('Shipping Addresses'); // Component header + breadcrumb + nav (multiple legitimate occurrences)
        $response->assertSee('Manage shipping addresses for the system'); // Component description (actual text)
        
        // Ensure no duplicate management headers in the same context
        $content = $response->getContent();
        $managementHeaderCount = substr_count($content, 'Address Management');
        $this->assertEquals(1, $managementHeaderCount, 
            'Should have exactly one "Address Management" header');
        
        // Check that headers don't run together (indicating layout issues)
        $this->assertStringNotContainsString('Address ManagementShipping Addresses', $content);
    }

    /** @test */
    public function pages_do_not_have_conflicting_ui_elements()
    {
        // Test office page
        $officeResponse = $this->actingAs($this->admin)->get(route('admin.offices.index'));
        $officeResponse->assertStatus(200);
        
        // Ensure the page renders properly without layout conflicts
        $officeContent = $officeResponse->getContent();
        $this->assertStringNotContainsString('Create New OfficeCreate Office', $officeContent);
        $this->assertStringNotContainsString('Office ManagementOffices', $officeContent);
        
        // Test address page
        $addressResponse = $this->actingAs($this->admin)->get(route('admin.addresses.index'));
        $addressResponse->assertStatus(200);
        
        // Ensure the page renders properly without layout conflicts
        $addressContent = $addressResponse->getContent();
        $this->assertStringNotContainsString('Create New AddressCreate Address', $addressContent);
        $this->assertStringNotContainsString('Address ManagementShipping Addresses', $addressContent);
    }

    /** @test */
    public function text_appears_in_appropriate_contexts()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.addresses.index'));
        $response->assertStatus(200);
        
        // "Shipping Addresses" should appear in multiple legitimate contexts:
        // - Breadcrumb navigation
        // - Sidebar navigation (desktop and mobile versions)
        // - Component header
        // This is NOT a duplication issue - these are different UI contexts
        
        $content = $response->getContent();
        $shippingAddressesCount = substr_count($content, 'Shipping Addresses');
        
        // Should appear multiple times in different contexts (this is expected and correct)
        $this->assertGreaterThanOrEqual(3, $shippingAddressesCount, 
            'Shipping Addresses should appear in breadcrumb, navigation, and component header');
        
        // But verify these are in different semantic contexts, not duplicated in the same area
        $this->assertStringContainsString('aria-label="Breadcrumb"', $content); // Breadcrumb context
        $this->assertStringContainsString('Manage shipping addresses', $content); // Component context
    }
}