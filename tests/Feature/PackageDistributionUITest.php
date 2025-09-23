<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Http\Livewire\PackageDistribution;

class PackageDistributionUITest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role_id' => 1]);
        $this->customer = User::factory()->create([
            'role_id' => 3,
            'account_balance' => 500.00,
            'credit_balance' => 200.00,
        ]);
    }

    /** @test */
    public function it_shows_separate_balance_options_when_customer_has_both_balances()
    {
        $this->actingAs($this->admin);

        $package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 100.00,
            'clearance_fee' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        Livewire::test(PackageDistribution::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('selectedPackages', [$package->id])
            ->set('showAdvancedOptions', true)
            ->assertSee('Apply Customer Balance')
            ->assertSee('Apply credit balance ($200.00)')
            ->assertSee('Apply account balance ($500.00)')
            ->assertSee('Total available: $700.00');
    }

    /** @test */
    public function it_shows_only_credit_option_when_customer_has_only_credit_balance()
    {
        $this->actingAs($this->admin);

        $customer = User::factory()->create([
            'role_id' => 3,
            'account_balance' => 0.00,
            'credit_balance' => 200.00,
        ]);

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 100.00,
            'clearance_fee' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        Livewire::test(PackageDistribution::class)
            ->set('selectedCustomerId', $customer->id)
            ->set('selectedPackages', [$package->id])
            ->set('showAdvancedOptions', true)
            ->assertSee('Apply Customer Balance')
            ->assertSee('Apply credit balance ($200.00)')
            ->assertDontSee('Apply account balance')
            ->assertDontSee('Total available');
    }

    /** @test */
    public function it_shows_only_account_option_when_customer_has_only_account_balance()
    {
        $this->actingAs($this->admin);

        $customer = User::factory()->create([
            'role_id' => 3,
            'account_balance' => 500.00,
            'credit_balance' => 0.00,
        ]);

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 100.00,
            'clearance_fee' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        Livewire::test(PackageDistribution::class)
            ->set('selectedCustomerId', $customer->id)
            ->set('selectedPackages', [$package->id])
            ->set('showAdvancedOptions', true)
            ->assertSee('Apply Customer Balance')
            ->assertSee('Apply account balance ($500.00)')
            ->assertDontSee('Apply credit balance')
            ->assertDontSee('Total available');
    }

    /** @test */
    public function it_can_select_credit_balance_only()
    {
        $this->actingAs($this->admin);

        $package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 100.00,
            'clearance_fee' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        Livewire::test(PackageDistribution::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('selectedPackages', [$package->id])
            ->set('applyCreditBalance', true)
            ->set('applyAccountBalance', false)
            ->assertSet('applyCreditBalance', true)
            ->assertSet('applyAccountBalance', false);
    }

    /** @test */
    public function it_can_select_account_balance_only()
    {
        $this->actingAs($this->admin);

        $package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 100.00,
            'clearance_fee' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        Livewire::test(PackageDistribution::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('selectedPackages', [$package->id])
            ->set('applyCreditBalance', false)
            ->set('applyAccountBalance', true)
            ->assertSet('applyCreditBalance', false)
            ->assertSet('applyAccountBalance', true);
    }

    /** @test */
    public function it_can_select_both_balances()
    {
        $this->actingAs($this->admin);

        $package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 100.00,
            'clearance_fee' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        Livewire::test(PackageDistribution::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('selectedPackages', [$package->id])
            ->set('applyCreditBalance', true)
            ->set('applyAccountBalance', true)
            ->assertSet('applyCreditBalance', true)
            ->assertSet('applyAccountBalance', true);
    }
}