<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Livewire\CustomerAnalytics;
use App\Models\User;
use App\Models\Profile;
use App\Models\Package;
use App\Models\Role;
use App\Services\DashboardCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Carbon\Carbon;

class CustomerAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create customer role
        Role::factory()->create([
            'id' => 3,
            'name' => 'customer'
        ]);
    }

    /** @test */
    public function it_can_render_customer_analytics_component()
    {
        Livewire::test(CustomerAnalytics::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.customer-analytics');
    }

    /** @test */
    public function it_can_get_customer_growth_data()
    {
        // Create test customers
        $customer1 = User::factory()->create([
            'role_id' => 3,
            'created_at' => Carbon::now()->subDays(5)
        ]);
        
        $customer2 = User::factory()->create([
            'role_id' => 3,
            'created_at' => Carbon::now()->subDays(3)
        ]);

        $component = Livewire::test(CustomerAnalytics::class, [
            'filters' => ['date_range' => '7']
        ]);

        $growthData = $component->instance()->getCustomerGrowthData();

        $this->assertIsArray($growthData);
        $this->assertArrayHasKey('labels', $growthData);
        $this->assertArrayHasKey('datasets', $growthData);
        $this->assertArrayHasKey('summary', $growthData);
        $this->assertEquals(2, $growthData['summary']['total_new']);
    }

    /** @test */
    public function it_can_get_customer_status_distribution()
    {
        // Create active customer
        $activeCustomer = User::factory()->create([
            'role_id' => 3,
            'email_verified_at' => now(),
            'deleted_at' => null,
            'created_at' => now()
        ]);

        // Create inactive customer
        $inactiveCustomer = User::factory()->create([
            'role_id' => 3,
            'email_verified_at' => null,
            'deleted_at' => null,
            'created_at' => now()
        ]);

        $component = Livewire::test(CustomerAnalytics::class);
        $statusData = $component->instance()->getCustomerStatusDistribution();

        $this->assertIsArray($statusData);
        $this->assertArrayHasKey('labels', $statusData);
        $this->assertArrayHasKey('datasets', $statusData);
        $this->assertArrayHasKey('summary', $statusData);
        $this->assertEquals(2, $statusData['summary']['total']);
    }

    /** @test */
    public function it_can_get_geographic_distribution()
    {
        // Create customers with profiles containing location data
        $customer1 = User::factory()->create(['role_id' => 3]);
        Profile::factory()->create([
            'user_id' => $customer1->id,
            'parish' => 'Kingston',
            'country' => 'Jamaica'
        ]);

        $customer2 = User::factory()->create(['role_id' => 3]);
        Profile::factory()->create([
            'user_id' => $customer2->id,
            'parish' => 'St. Andrew',
            'country' => 'Jamaica'
        ]);

        $component = Livewire::test(CustomerAnalytics::class);
        $geoData = $component->instance()->getGeographicDistribution();

        $this->assertIsArray($geoData);
        $this->assertArrayHasKey('parish_distribution', $geoData);
        $this->assertArrayHasKey('country_summary', $geoData);
        $this->assertArrayHasKey('total_with_location', $geoData);
    }

    /** @test */
    public function it_can_get_customer_activity_levels()
    {
        // Create customer with high activity
        $highActivityCustomer = User::factory()->create(['role_id' => 3]);
        Package::factory()->count(6)->create([
            'user_id' => $highActivityCustomer->id,
            'created_at' => Carbon::now()->subDays(2)
        ]);

        // Create customer with medium activity
        $mediumActivityCustomer = User::factory()->create(['role_id' => 3]);
        Package::factory()->count(3)->create([
            'user_id' => $mediumActivityCustomer->id,
            'created_at' => Carbon::now()->subDays(2)
        ]);

        // Create customer with low activity
        $lowActivityCustomer = User::factory()->create(['role_id' => 3]);
        Package::factory()->create([
            'user_id' => $lowActivityCustomer->id,
            'created_at' => Carbon::now()->subDays(2)
        ]);

        // Create customer with no activity
        $noActivityCustomer = User::factory()->create(['role_id' => 3]);

        $component = Livewire::test(CustomerAnalytics::class);
        $activityData = $component->instance()->getCustomerActivityLevels();

        $this->assertIsArray($activityData);
        $this->assertArrayHasKey('activity_distribution', $activityData);
        $this->assertArrayHasKey('revenue_by_activity', $activityData);
        $this->assertArrayHasKey('summary', $activityData);
        $this->assertEquals(4, $activityData['summary']['total_customers']);
    }

    /** @test */
    public function it_can_refresh_data()
    {
        $component = Livewire::test(CustomerAnalytics::class);
        
        $component->call('refreshData')
            ->assertEmitted('dataRefreshed');
    }

    /** @test */
    public function it_responds_to_filter_updates()
    {
        $component = Livewire::test(CustomerAnalytics::class);
        
        $component->set('filters.date_range', '7')
            ->assertEmitted('filtersUpdated');
    }
}