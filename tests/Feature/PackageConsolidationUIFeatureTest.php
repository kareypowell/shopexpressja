<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\Shipper;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PackageConsolidationUIFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $customerUser;
    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles and users
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $customerRole = Role::factory()->create(['name' => 'customer']);
        
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->customerUser = User::factory()->create(['role_id' => $customerRole->id]);
    }

    /** @test */
    public function package_consolidation_templates_exist_and_contain_required_elements()
    {
        // Test that the main package template exists and contains consolidation UI elements
        $this->assertTrue(file_exists(resource_path('views/livewire/packages/package.blade.php')));
        
        $packageTemplate = file_get_contents(resource_path('views/livewire/packages/package.blade.php'));
        
        // Check for consolidation toggle controls
        $this->assertStringContainsString('Consolidation Mode', $packageTemplate);
        $this->assertStringContainsString('Show Consolidated', $packageTemplate);
        $this->assertStringContainsString('wire:model="consolidationMode"', $packageTemplate);
        $this->assertStringContainsString('wire:model="showConsolidatedView"', $packageTemplate);
    }

    /** @test */
    public function consolidation_mode_indicator_template_contains_required_styling()
    {
        $packageTemplate = file_get_contents(resource_path('views/livewire/packages/package.blade.php'));
        
        // Check for consolidation mode indicator styling
        $this->assertStringContainsString('bg-gradient-to-r from-blue-50 to-indigo-50', $packageTemplate);
        $this->assertStringContainsString('Consolidation Mode Active', $packageTemplate);
        $this->assertStringContainsString('Selection Summary', $packageTemplate);
        $this->assertStringContainsString('Total lbs', $packageTemplate);
        $this->assertStringContainsString('Total Cost', $packageTemplate);
    }

    /** @test */
    public function consolidation_packages_list_template_exists_and_contains_selection_ui()
    {
        // Test that the consolidation packages list template exists
        $this->assertTrue(file_exists(resource_path('views/livewire/packages/consolidation-packages-list.blade.php')));
        
        $consolidationTemplate = file_get_contents(resource_path('views/livewire/packages/consolidation-packages-list.blade.php'));
        
        // Check for selection checkboxes and visual indicators
        $this->assertStringContainsString('wire:click="togglePackageSelection', $consolidationTemplate);
        $this->assertStringContainsString('form-checkbox', $consolidationTemplate);
        $this->assertStringContainsString('Selected', $consolidationTemplate);
        $this->assertStringContainsString('Available', $consolidationTemplate);
        $this->assertStringContainsString('ring-2 ring-blue-500', $consolidationTemplate);
    }

    /** @test */
    public function individual_packages_list_template_contains_visual_indicators()
    {
        // Test that the individual packages list template exists
        $this->assertTrue(file_exists(resource_path('views/livewire/packages/individual-packages-list.blade.php')));
        
        $individualTemplate = file_get_contents(resource_path('views/livewire/packages/individual-packages-list.blade.php'));
        
        // Check for individual package visual indicators
        $this->assertStringContainsString('Individual', $individualTemplate);
        $this->assertStringContainsString('bg-gray-100 text-gray-800', $individualTemplate);
        $this->assertStringContainsString('hover:shadow-md', $individualTemplate);
    }

    /** @test */
    public function consolidated_packages_display_contains_summary_cards_and_expandable_details()
    {
        $packageTemplate = file_get_contents(resource_path('views/livewire/packages/package.blade.php'));
        
        // Check for consolidated package summary cards
        $this->assertStringContainsString('bg-gradient-to-r from-green-50', $packageTemplate);
        $this->assertStringContainsString('border-l-4 border-green-400', $packageTemplate);
        $this->assertStringContainsString('Consolidated Packages', $packageTemplate);
        $this->assertStringContainsString('Show Details', $packageTemplate);
        $this->assertStringContainsString('Hide Details', $packageTemplate);
        
        // Check for summary statistics
        $this->assertStringContainsString('Total lbs', $packageTemplate);
        $this->assertStringContainsString('Total Cost', $packageTemplate);
        $this->assertStringContainsString('Packages', $packageTemplate);
        $this->assertStringContainsString('Shippers', $packageTemplate);
    }

    /** @test */
    public function consolidation_action_buttons_exist_in_templates()
    {
        $packageTemplate = file_get_contents(resource_path('views/livewire/packages/package.blade.php'));
        
        // Check for consolidation action buttons
        $this->assertStringContainsString('Consolidate Selected', $packageTemplate);
        $this->assertStringContainsString('Clear', $packageTemplate);
        $this->assertStringContainsString('Unconsolidate', $packageTemplate);
        $this->assertStringContainsString('wire:click="consolidateSelectedPackages"', $packageTemplate);
        $this->assertStringContainsString('wire:click="clearSelectedPackages"', $packageTemplate);
        $this->assertStringContainsString('wire:click="unconsolidatePackage(', $packageTemplate);
    }

    /** @test */
    public function empty_state_messages_exist_in_templates()
    {
        $packageTemplate = file_get_contents(resource_path('views/livewire/packages/package.blade.php'));
        $consolidationTemplate = file_get_contents(resource_path('views/livewire/packages/consolidation-packages-list.blade.php'));
        $individualTemplate = file_get_contents(resource_path('views/livewire/packages/individual-packages-list.blade.php'));
        
        // Check for empty state messages
        $this->assertStringContainsString('No packages available for consolidation', $consolidationTemplate);
        $this->assertStringContainsString('No consolidated packages found', $packageTemplate);
        $this->assertStringContainsString('No individual packages found', $individualTemplate);
        
        // Check for helpful criteria messages
        $this->assertStringContainsString('Packages must meet the following criteria', $consolidationTemplate);
        $this->assertStringContainsString('Status: Pending, Processing, or Shipped', $consolidationTemplate);
        $this->assertStringContainsString('Not already consolidated', $consolidationTemplate);
    }

    /** @test */
    public function javascript_enhancements_exist_in_template()
    {
        $packageTemplate = file_get_contents(resource_path('views/livewire/packages/package.blade.php'));
        
        // Check for JavaScript enhancements
        $this->assertStringContainsString('<script>', $packageTemplate);
        $this->assertStringContainsString('toggle functionality', $packageTemplate);
        $this->assertStringContainsString('smooth transitions', $packageTemplate);
        $this->assertStringContainsString('Auto-scroll', $packageTemplate);
        $this->assertStringContainsString('togglePackageSelection', $packageTemplate);
    }
}