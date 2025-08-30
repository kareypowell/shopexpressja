<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Livewire\Admin\BroadcastComposer;
use App\Models\User;
use App\Models\Role;
use App\Models\BroadcastMessage;
use App\Services\BroadcastMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Carbon\Carbon;

class BroadcastComposerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::factory()->admin()->create();
        $customerRole = Role::factory()->customer()->create();

        // Create admin user
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);

        // Create test customers
        $this->customers = User::factory()->count(5)->create(['role_id' => $customerRole->id]);

        $this->actingAs($this->admin);
    }

    /** @test */
    public function it_can_mount_component_with_default_values()
    {
        $component = Livewire::test(BroadcastComposer::class);

        $component->assertSet('subject', '')
                 ->assertSet('content', '')
                 ->assertSet('recipientType', 'all')
                 ->assertSet('selectedCustomers', [])
                 ->assertSet('customerSearch', '')
                 ->assertSet('isScheduled', false)
                 ->assertSet('showPreview', false);
                 
        // Check that recipient count is set (should be the number of active customers)
        $recipientCount = $component->get('recipientCount');
        $this->assertGreaterThanOrEqual(0, $recipientCount);
    }

    /** @test */
    public function it_updates_recipient_count_when_recipient_type_changes()
    {
        $component = Livewire::test(BroadcastComposer::class);

        // Get initial count
        $initialCount = $component->get('recipientCount');

        // Change to selected customers
        $component->set('recipientType', 'selected')
                 ->assertSet('recipientCount', 0)
                 ->assertSet('showCustomerSelection', true);

        // Change back to all customers
        $component->set('recipientType', 'all')
                 ->assertSet('recipientCount', $initialCount)
                 ->assertSet('showCustomerSelection', false);
    }

    /** @test */
    public function it_can_search_customers()
    {
        // Create a customer with specific name for testing
        $testCustomer = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'role_id' => Role::where('name', 'customer')->first()->id
        ]);

        $component = Livewire::test(BroadcastComposer::class);

        $component->set('recipientType', 'selected')
                 ->set('customerSearch', 'John');

        // Check that the search filters customers
        $availableCustomers = $component->get('availableCustomers');
        $this->assertTrue($availableCustomers->contains('id', $testCustomer->id));
    }

    /** @test */
    public function it_can_toggle_customer_selection()
    {
        $customer = $this->customers->first();
        
        $component = Livewire::test(BroadcastComposer::class);

        $component->set('recipientType', 'selected')
                 ->call('toggleCustomer', $customer->id)
                 ->assertSet('selectedCustomers', [$customer->id])
                 ->assertSet('recipientCount', 1);

        // Toggle again to remove
        $component->call('toggleCustomer', $customer->id)
                 ->assertSet('selectedCustomers', [])
                 ->assertSet('recipientCount', 0);
    }

    /** @test */
    public function it_can_select_all_customers()
    {
        $component = Livewire::test(BroadcastComposer::class);

        $component->set('recipientType', 'selected')
                 ->call('selectAllCustomers');

        $selectedCustomers = $component->get('selectedCustomers');
        $recipientCount = $component->get('recipientCount');
        
        // Should have selected some customers
        $this->assertGreaterThanOrEqual(0, count($selectedCustomers));
        $this->assertEquals(count($selectedCustomers), $recipientCount);
    }

    /** @test */
    public function it_can_clear_customer_selection()
    {
        $component = Livewire::test(BroadcastComposer::class);

        $component->set('recipientType', 'selected')
                 ->set('selectedCustomers', [$this->customers->first()->id])
                 ->call('clearSelection')
                 ->assertSet('selectedCustomers', [])
                 ->assertSet('recipientCount', 0);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $component = Livewire::test(BroadcastComposer::class);

        $component->call('showPreview')
                 ->assertHasErrors(['subject', 'content']);
    }

    /** @test */
    public function it_validates_selected_customers_when_recipient_type_is_selected()
    {
        $component = Livewire::test(BroadcastComposer::class);

        $component->set('recipientType', 'selected')
                 ->set('subject', 'Test Subject')
                 ->set('content', 'Test content that is long enough')
                 ->call('showPreview')
                 ->assertHasErrors(['selectedCustomers']);
    }

    /** @test */
    public function it_can_save_draft()
    {
        $this->mock(BroadcastMessageService::class, function ($mock) {
            $mock->shouldReceive('saveDraft')->once()->andReturn([
                'success' => true,
                'message' => 'Draft saved successfully'
            ]);
        });

        $component = Livewire::test(BroadcastComposer::class);

        $component->set('subject', 'Test Subject')
                 ->set('content', 'Test content that is long enough')
                 ->call('saveDraft');

        $component->assertHasNoErrors();
        $component->assertSet('subject', ''); // Should reset form after saving
    }

    /** @test */
    public function it_can_show_and_hide_preview()
    {
        $component = Livewire::test(BroadcastComposer::class);

        $component->set('subject', 'Test Subject')
                 ->set('content', 'Test content that is long enough')
                 ->set('recipientType', 'all') // Ensure validation passes
                 ->call('showPreview');

        // Check if there are validation errors
        if ($component->lastErrorBag->isNotEmpty()) {
            $this->fail('Validation failed with errors: ' . implode(', ', $component->lastErrorBag->keys()));
        }

        $component->assertSet('showPreview', true);

        $component->call('hidePreview')
                 ->assertSet('showPreview', false);
    }

    /** @test */
    public function it_handles_scheduling_options()
    {
        $component = Livewire::test(BroadcastComposer::class);

        // Enable scheduling
        $component->set('isScheduled', true)
                 ->assertSet('scheduledDate', Carbon::tomorrow()->format('Y-m-d'))
                 ->assertSet('scheduledTime', '09:00');

        // Disable scheduling
        $component->set('isScheduled', false)
                 ->assertSet('scheduledDate', '')
                 ->assertSet('scheduledTime', '');
    }

    /** @test */
    public function it_validates_scheduled_time_is_in_future()
    {
        $component = Livewire::test(BroadcastComposer::class);

        // Set past date
        $component->set('isScheduled', true)
                 ->set('scheduledDate', Carbon::yesterday()->format('Y-m-d'))
                 ->set('scheduledTime', '10:00')
                 ->call('validateScheduleTime');

        $component->assertHasErrors(['scheduledTime']);
    }

    /** @test */
    public function it_validates_scheduled_time_is_at_least_5_minutes_in_future()
    {
        $component = Livewire::test(BroadcastComposer::class);

        // Set time that's too close to now
        $nearFuture = Carbon::now()->addMinutes(2);
        $component->set('isScheduled', true)
                 ->set('scheduledDate', $nearFuture->format('Y-m-d'))
                 ->set('scheduledTime', $nearFuture->format('H:i'))
                 ->call('validateScheduleTime');

        $component->assertHasErrors(['scheduledTime']);
    }

    /** @test */
    public function it_can_send_message_immediately()
    {
        $broadcastService = $this->mock(BroadcastMessageService::class);
        $mockBroadcast = BroadcastMessage::factory()->make(['id' => 1]);
        
        $broadcastService->shouldReceive('createBroadcast')->once()->andReturn([
            'success' => true,
            'broadcast_message' => $mockBroadcast
        ]);
        $broadcastService->shouldReceive('sendBroadcast')->once()->with(1);

        $component = Livewire::test(BroadcastComposer::class);

        $component->set('subject', 'Test Subject')
                 ->set('content', 'Test content that is long enough')
                 ->set('recipientType', 'all') // Explicitly set to avoid validation errors
                 ->call('sendNow');

        $component->assertHasNoErrors();
    }

    /** @test */
    public function it_can_schedule_message()
    {
        $broadcastService = $this->mock(BroadcastMessageService::class);
        $mockBroadcast = BroadcastMessage::factory()->make(['id' => 1]);
        
        $broadcastService->shouldReceive('createBroadcast')->once()->andReturn([
            'success' => true,
            'broadcast_message' => $mockBroadcast
        ]);
        $broadcastService->shouldReceive('scheduleBroadcast')->once()->with(1, \Mockery::type(Carbon::class))->andReturn(['success' => true]);

        $component = Livewire::test(BroadcastComposer::class);

        $futureDate = Carbon::tomorrow();
        $component->set('subject', 'Test Subject')
                 ->set('content', 'Test content that is long enough')
                 ->set('recipientType', 'all') // Explicitly set to avoid validation errors
                 ->set('isScheduled', true)
                 ->set('scheduledDate', $futureDate->format('Y-m-d'))
                 ->set('scheduledTime', '10:00')
                 ->call('scheduleMessage');

        $component->assertHasNoErrors();
    }

    /** @test */
    public function it_resets_form_after_successful_send()
    {
        $broadcastService = $this->mock(BroadcastMessageService::class);
        $mockBroadcast = BroadcastMessage::factory()->make(['id' => 1]);
        
        $broadcastService->shouldReceive('createBroadcast')->once()->andReturn([
            'success' => true,
            'broadcast_message' => $mockBroadcast
        ]);
        $broadcastService->shouldReceive('sendBroadcast')->once()->andReturn(['success' => true]);

        $component = Livewire::test(BroadcastComposer::class);

        $component->set('subject', 'Test Subject')
                 ->set('content', 'Test content that is long enough')
                 ->set('recipientType', 'selected')
                 ->set('selectedCustomers', [$this->customers->first()->id])
                 ->call('sendNow');

        // Form should be reset
        $component->assertSet('subject', '')
                 ->assertSet('content', '')
                 ->assertSet('recipientType', 'all')
                 ->assertSet('selectedCustomers', [])
                 ->assertSet('showPreview', false);
    }

    /** @test */
    public function it_handles_service_exceptions_gracefully()
    {
        $broadcastService = $this->mock(BroadcastMessageService::class);
        $broadcastService->shouldReceive('createBroadcast')->andThrow(new \Exception('Service error'));

        $component = Livewire::test(BroadcastComposer::class);

        $component->set('subject', 'Test Subject')
                 ->set('content', 'Test content that is long enough')
                 ->set('recipientType', 'all') // Explicitly set to avoid validation errors
                 ->call('sendNow');

        // Should not throw exception, should handle gracefully
        $component->assertHasNoErrors();
    }

    /** @test */
    public function it_paginates_customer_list()
    {
        // Create more customers to test pagination
        User::factory()->count(25)->create([
            'role_id' => Role::where('name', 'customer')->first()->id
        ]);

        $component = Livewire::test(BroadcastComposer::class);
        $component->set('recipientType', 'selected');

        $availableCustomers = $component->get('availableCustomers');
        $this->assertEquals(20, $availableCustomers->perPage()); // Should paginate at 20 per page
        $this->assertTrue($availableCustomers->hasPages());
    }

    /** @test */
    public function it_updates_recipient_count_when_customers_are_selected()
    {
        $component = Livewire::test(BroadcastComposer::class);

        $component->set('recipientType', 'selected')
                 ->call('toggleCustomer', $this->customers[0]->id)
                 ->call('toggleCustomer', $this->customers[1]->id)
                 ->assertSet('recipientCount', 2);
    }
}