<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Livewire\Manifests\Packages\EditManifestPackage;
use App\Models\Package;
use App\Models\User;
use App\Models\Role;
use App\Models\Manifest;
use App\Models\Shipper;
use App\Models\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EditManifestPackageModelBindingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $manifest;
    protected $package;
    protected $shipper;
    protected $office;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create necessary test data
        $customerRole = Role::where('name', 'customer')->first();
        $this->user = User::factory()->create(['role_id' => $customerRole->id]);
        
        $this->shipper = Shipper::factory()->create();
        $this->office = Office::factory()->create();
        
        $this->manifest = Manifest::factory()->create(['type' => 'air']);
        
        $this->package = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'shipper_id' => $this->shipper->id,
            'office_id' => $this->office->id,
        ]);
    }

    /** @test */
    public function it_handles_model_instances_from_route_binding()
    {
        // Mock request with actual model instances (as Laravel route model binding provides)
        $manifest = $this->manifest;
        $package = $this->package;
        
        request()->setRouteResolver(function () use ($manifest, $package) {
            return new class($manifest, $package) {
                private $manifest;
                private $package;
                
                public function __construct($manifest, $package) {
                    $this->manifest = $manifest;
                    $this->package = $package;
                }
                
                public function parameter($key) {
                    $params = [
                        'manifest' => $this->manifest, // Return actual model instance
                        'package' => $this->package,   // Return actual model instance
                    ];
                    return $params[$key] ?? null;
                }
            };
        });

        $component = new EditManifestPackage();
        
        // This should not throw a TypeError with our fix
        $component->mount();
        
        // Should successfully extract IDs from model instances
        $this->assertEquals($this->manifest->id, $component->manifest_id);
        $this->assertEquals($this->package->id, $component->package_id);
        $this->assertEquals($this->user->id, $component->user_id);
    }

    /** @test */
    public function it_handles_integer_ids_from_route_parameters()
    {
        // Mock request with integer IDs (fallback behavior)
        $manifest = $this->manifest;
        $package = $this->package;
        
        request()->setRouteResolver(function () use ($manifest, $package) {
            return new class($manifest, $package) {
                private $manifest;
                private $package;
                
                public function __construct($manifest, $package) {
                    $this->manifest = $manifest;
                    $this->package = $package;
                }
                
                public function parameter($key) {
                    $params = [
                        'manifest' => $this->manifest->id, // Return integer ID
                        'package' => $this->package->id,   // Return integer ID
                    ];
                    return $params[$key] ?? null;
                }
            };
        });

        $component = new EditManifestPackage();
        $component->mount();
        
        // Should successfully handle integer IDs
        $this->assertEquals($this->manifest->id, $component->manifest_id);
        $this->assertEquals($this->package->id, $component->package_id);
        $this->assertEquals($this->user->id, $component->user_id);
    }
}