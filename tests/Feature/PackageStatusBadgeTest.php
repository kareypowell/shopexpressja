<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Package;
use App\Models\User;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PackageStatusBadgeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function package_status_badges_display_correct_colors()
    {
        // Create a test user
        $user = User::factory()->create(['role_id' => 3]);
        
        // Test each status and its expected badge class
        $statusTests = [
            ['status' => PackageStatus::PENDING, 'expected_class' => 'default', 'expected_label' => 'Pending'],
            ['status' => PackageStatus::PROCESSING, 'expected_class' => 'primary', 'expected_label' => 'Processing'],
            ['status' => PackageStatus::SHIPPED, 'expected_class' => 'shs', 'expected_label' => 'Shipped'],
            ['status' => PackageStatus::CUSTOMS, 'expected_class' => 'warning', 'expected_label' => 'At Customs'],
            ['status' => PackageStatus::READY, 'expected_class' => 'success', 'expected_label' => 'Ready for Pickup'],
            ['status' => PackageStatus::DELIVERED, 'expected_class' => 'success', 'expected_label' => 'Delivered'],
            ['status' => PackageStatus::DELAYED, 'expected_class' => 'danger', 'expected_label' => 'Delayed'],
        ];
        
        foreach ($statusTests as $test) {
            // Create package with specific status
            $package = Package::factory()->create([
                'user_id' => $user->id,
                'status' => $test['status'],
                'tracking_number' => 'TEST' . $test['status']
            ]);
            
            // Verify the badge class
            $this->assertEquals($test['expected_class'], $package->status_badge_class);
            
            // Verify the status label
            $this->assertEquals($test['expected_label'], $package->status_label);
        }
    }

    /** @test */
    public function package_status_enum_returns_correct_badge_classes()
    {
        $this->assertEquals('default', PackageStatus::PENDING()->getBadgeClass());
        $this->assertEquals('primary', PackageStatus::PROCESSING()->getBadgeClass());
        $this->assertEquals('shs', PackageStatus::SHIPPED()->getBadgeClass());
        $this->assertEquals('warning', PackageStatus::CUSTOMS()->getBadgeClass());
        $this->assertEquals('success', PackageStatus::READY()->getBadgeClass());
        $this->assertEquals('success', PackageStatus::DELIVERED()->getBadgeClass());
        $this->assertEquals('danger', PackageStatus::DELAYED()->getBadgeClass());
    }

    /** @test */
    public function package_status_enum_returns_correct_labels()
    {
        $this->assertEquals('Pending', PackageStatus::PENDING()->getLabel());
        $this->assertEquals('Processing', PackageStatus::PROCESSING()->getLabel());
        $this->assertEquals('Shipped', PackageStatus::SHIPPED()->getLabel());
        $this->assertEquals('At Customs', PackageStatus::CUSTOMS()->getLabel());
        $this->assertEquals('Ready for Pickup', PackageStatus::READY()->getLabel());
        $this->assertEquals('Delivered', PackageStatus::DELIVERED()->getLabel());
        $this->assertEquals('Delayed', PackageStatus::DELAYED()->getLabel());
    }
}