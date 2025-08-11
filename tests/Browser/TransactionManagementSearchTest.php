<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class TransactionManagementSearchTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function user_can_search_and_select_customers()
    {
        // Create admin and customer users
        $admin = User::factory()->create(['role_id' => 1]);
        $customer = User::factory()->create([
            'role_id' => 3,
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        $this->browse(function (Browser $browser) use ($admin, $customer) {
            $browser->loginAs($admin)
                    ->visit('/admin/transactions')
                    ->waitFor('#customerSearch')
                    ->type('#customerSearch', 'John')
                    ->waitFor('.absolute.z-10') // Wait for dropdown
                    ->assertSee('John Doe')
                    ->click('.cursor-pointer:first-child') // Click first customer
                    ->waitUntilMissing('.absolute.z-10') // Wait for dropdown to close
                    ->assertInputValue('#customerSearch', ''); // Should be cleared after selection
        });
    }

    /** @test */
    public function dropdown_shows_no_results_message()
    {
        $admin = User::factory()->create(['role_id' => 1]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                    ->visit('/admin/transactions')
                    ->waitFor('#customerSearch')
                    ->type('#customerSearch', 'NonExistentCustomer')
                    ->waitFor('.absolute.z-10') // Wait for dropdown
                    ->assertSee('No customers found');
        });
    }
}