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
use Livewire\Livewire;

class EditManifestPackageRouteTest extends TestCase
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
    public function it_properly_handles_route_parameters()
    {
        // Mock the request route parameters
        request()->setRouteResolver(function () {
            return new class {
                public function parameter($key) {
                    $params = [
                        'manifest' => 14,
                        'package' => 3,
                    ];
                    return $params[$key] ?? null;
                }
            };
        });

        $component = new EditManifestPackage();
        
        // This should not throw a TypeError anymore
        $this->expectNotToPerformAssertions();
        
        // The mount method should handle the route parameters correctly
        // Note: This will abort(404) because the IDs don't exist, but that's expected behavior
        try {
            $component->mount();
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            // This is expected when the manifest/package don't exist
            $this->assertEquals(404, $e->getStatusCode());
        }
    }

    /** @test */
    public function it_validates_required_parameters()
    {
        // Mock request with missing parameters
        request()->setRouteResolver(function () {
            return new class {
                public function parameter($key) {
                    return null; // Simulate missing parameters
                }
            };
        });

        $component = new EditManifestPackage();
        
        // Should abort with 404 when parameters are missing
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        
        $component->mount();
    }

    /** @test */
    public function it_works_with_valid_route_parameters()
    {
        // Mock request with valid parameters
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
                        'manifest' => $this->manifest->id,
                        'package' => $this->package->id,
                    ];
                    return $params[$key] ?? null;
                }
            };
        });

        $component = new EditManifestPackage();
        $component->mount();
        
        // Should successfully set the properties
        $this->assertEquals($this->manifest->id, $component->manifest_id);
        $this->assertEquals($this->package->id, $component->package_id);
        $this->assertEquals($this->user->id, $component->user_id);
    }
}