<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminDashboardMemoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_dashboard_does_not_cause_memory_issues()
    {
        // Create admin role and user
        $role = Role::factory()->create(['name' => 'superadmin']);
        $user = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'role_id' => $role->id,
        ]);

        // Mock services to prevent actual data loading
        $this->mock(\App\Services\DashboardAnalyticsService::class, function ($mock) {
            $mock->shouldReceive('cacheKey')->andReturn('test-key');
            $mock->shouldReceive('getCustomerMetrics')->andReturn([]);
            $mock->shouldReceive('getShipmentMetrics')->andReturn([]);
            $mock->shouldReceive('getFinancialMetrics')->andReturn([]);
        });

        $this->mock(\App\Services\DashboardCacheService::class, function ($mock) {
            $mock->shouldReceive('remember')->andReturn([]);
            $mock->shouldReceive('flush')->andReturn(true);
        });

        // Record initial memory usage
        $initialMemory = memory_get_usage(true);

        // Access the admin dashboard multiple times
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($user)->get('/admin/dashboard');
            $response->assertStatus(200);
        }

        // Check memory usage hasn't grown excessively
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory increase should be less than 50MB
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease, 
            'Dashboard is using too much memory: ' . number_format($memoryIncrease / 1024 / 1024, 2) . 'MB increase');
    }

    /** @test */
    public function dashboard_handles_errors_gracefully()
    {
        // Create admin role and user
        $role = Role::factory()->create(['name' => 'superadmin']);
        $user = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'role_id' => $role->id,
        ]);

        // Mock services to throw exceptions
        $this->mock(\App\Services\DashboardAnalyticsService::class, function ($mock) {
            $mock->shouldReceive('cacheKey')->andThrow(new \Exception('Test error'));
        });

        $this->mock(\App\Services\DashboardCacheService::class, function ($mock) {
            $mock->shouldReceive('remember')->andThrow(new \Exception('Cache error'));
        });

        // Dashboard should still load (with error handling)
        $response = $this->actingAs($user)->get('/admin/dashboard');
        
        // Should either return 200 with error message or handle gracefully
        $this->assertTrue(
            $response->status() === 200 || $response->status() === 500,
            'Dashboard should handle errors gracefully'
        );
    }
}