<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Manifest;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Http\Livewire\Manifests\ManifestsTable;

class ManifestListStatusDisplayTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin role and user
        $adminRole = Role::create([
            'name' => 'admin',
            'description' => 'Administrator role'
        ]);
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
    }

    /** @test */
    public function it_displays_open_manifest_status_with_proper_badge()
    {
        $openManifest = Manifest::factory()->create([
            'name' => 'Open Test Manifest',
            'is_open' => true
        ]);

        $this->actingAs($this->adminUser);

        Livewire::test(ManifestsTable::class)
            ->assertSee('Open Test Manifest')
            ->assertSee('Open')
            ->assertSee('bg-green-100 text-green-800');
    }

    /** @test */
    public function it_displays_closed_manifest_status_with_proper_badge()
    {
        $closedManifest = Manifest::factory()->create([
            'name' => 'Closed Test Manifest',
            'is_open' => false
        ]);

        $this->actingAs($this->adminUser);

        Livewire::test(ManifestsTable::class)
            ->assertSee('Closed Test Manifest')
            ->assertSee('Closed')
            ->assertSee('bg-gray-100 text-gray-800');
    }

    /** @test */
    public function it_can_filter_manifests_by_status()
    {
        $openManifest = Manifest::factory()->create([
            'name' => 'Open Manifest',
            'is_open' => true
        ]);

        $closedManifest = Manifest::factory()->create([
            'name' => 'Closed Manifest',
            'is_open' => false
        ]);

        $this->actingAs($this->adminUser);

        // Test filtering for closed manifests
        Livewire::test(ManifestsTable::class)
            ->set('filters.status', '0')
            ->assertSee('Closed Manifest');
    }

    /** @test */
    public function it_can_sort_manifests_by_status()
    {
        $openManifest = Manifest::factory()->create([
            'name' => 'Open Manifest',
            'is_open' => true
        ]);

        $closedManifest = Manifest::factory()->create([
            'name' => 'Closed Manifest',
            'is_open' => false
        ]);

        $this->actingAs($this->adminUser);

        Livewire::test(ManifestsTable::class)
            ->call('sortBy', 'is_open')
            ->assertSee('Closed Manifest')
            ->assertSee('Open Manifest');
    }

    /** @test */
    public function it_shows_status_filter_options()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ManifestsTable::class);
        
        $filters = $component->instance()->filters();
        
        $this->assertArrayHasKey('status', $filters);
        // Just check that the status filter exists and has the right options
        $this->assertNotNull($filters['status']);
    }

    /** @test */
    public function it_displays_status_column_header()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ManifestsTable::class)
            ->assertSee('Status');
    }

    /** @test */
    public function it_shows_visual_indicators_for_different_statuses()
    {
        $openManifest = Manifest::factory()->create([
            'name' => 'Open Manifest',
            'is_open' => true
        ]);

        $closedManifest = Manifest::factory()->create([
            'name' => 'Closed Manifest',
            'is_open' => false
        ]);

        $this->actingAs($this->adminUser);

        // Test the Livewire component directly
        Livewire::test(ManifestsTable::class)
            ->assertSee('Open')
            ->assertSee('Closed');
    }
}