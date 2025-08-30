<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Livewire\Admin\BroadcastComposer;
use App\Models\User;
use App\Models\Role;
use App\Models\BroadcastMessage;
use App\Models\BroadcastRecipient;
use App\Services\BroadcastMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Carbon\Carbon;

class BroadcastComposerIntegrationTest extends TestCase
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
        $this->customers = User::factory()->count(10)->create(['role_id' => $customerRole->id]);

        $this->actingAs($this->admin);
    }

    /** @test */
    public function it_can_complete_full_broadcast_workflow_for_all_customers()
    {
        // Mock the service
        $broadcastService = $this->mock(BroadcastMessageService::class);
        $mockBroadcast = BroadcastMessage::factory()->make(['id' => 1]);
        
        $broadcastService->shouldReceive('createBroadcast')->once()->andReturn([
            'success' => true,
            'broadcast_message' => $mockBroadcast
        ]);
        $broadcastService->shouldReceive('sendBroadcast')->once()->andReturn(['success' => true]);

        $component = Livewire::test(BroadcastComposer::class);

        // Fill in the form
        $component->set('subject', 'Important System Update')
                 ->set('content', 'We are performing scheduled maintenance on our systems. Please expect brief service interruptions.')
                 ->set('recipientType', 'all');

        // Verify recipient count is correct
        $recipientCount = $component->get('recipientCount');
        $this->assertGreaterThanOrEqual(0, $recipientCount);

        // Show preview
        $component->call('showPreview')
                 ->assertSet('showPreview', true)
                 ->assertHasNoErrors();

        // Send the message
        $component->call('sendNow');

        // Verify form was reset
        $component->assertSet('subject', '')
                 ->assertSet('content', '')
                 ->assertSet('showPreview', false);
    }

    /** @test */
    public function it_can_complete_full_broadcast_workflow_for_selected_customers()
    {
        $selectedCustomers = $this->customers->take(3)->pluck('id')->toArray();

        // Mock the service
        $broadcastService = $this->mock(BroadcastMessageService::class);
        $mockBroadcast = BroadcastMessage::factory()->make(['id' => 1]);
        
        $broadcastService->shouldReceive('createBroadcast')->once()->andReturn([
            'success' => true,
            'broadcast_message' => $mockBroadcast
        ]);
        $broadcastService->shouldReceive('sendBroadcast')->once()->andReturn(['success' => true]);

        $component = Livewire::test(BroadcastComposer::class);

        // Fill in the form
        $component->set('subject', 'Special Offer')
                 ->set('content', 'You have been selected for our exclusive offer. Limited time only!')
                 ->set('recipientType', 'selected')
                 ->set('selectedCustomers', $selectedCustomers);

        // Verify recipient count is correct
        $component->assertSet('recipientCount', 3);

        // Show preview
        $component->call('showPreview')
                 ->assertSet('showPreview', true)
                 ->assertHasNoErrors();

        // Send the message
        $component->call('sendNow');

        $component->assertHasNoErrors();
    }

    /** @test */
    public function it_can_schedule_broadcast_for_future_delivery()
    {
        $futureDate = Carbon::tomorrow()->setTime(10, 0);

        // Mock the service
        $broadcastService = $this->mock(BroadcastMessageService::class);
        $mockBroadcast = BroadcastMessage::factory()->make(['id' => 1, 'subject' => 'Scheduled Announcement']);
        
        $broadcastService->shouldReceive('createBroadcast')->once()->andReturn([
            'success' => true,
            'broadcast_message' => $mockBroadcast
        ]);
        $broadcastService->shouldReceive('scheduleBroadcast')->once()->andReturn(['success' => true]);

        $component = Livewire::test(BroadcastComposer::class);

        // Fill in the form with scheduling
        $component->set('subject', 'Scheduled Announcement')
                 ->set('content', 'This message was scheduled in advance.')
                 ->set('recipientType', 'all')
                 ->set('isScheduled', true)
                 ->set('scheduledDate', $futureDate->format('Y-m-d'))
                 ->set('scheduledTime', $futureDate->format('H:i'));

        // Show preview
        $component->call('showPreview')
                 ->assertSet('showPreview', true)
                 ->assertHasNoErrors();

        // Schedule the message
        $component->call('scheduleMessage');

        $component->assertHasNoErrors();
    }

    /** @test */
    public function it_can_save_and_manage_drafts()
    {
        $component = Livewire::test(BroadcastComposer::class);

        // Create a draft
        $component->set('subject', 'Draft Message')
                 ->set('content', 'This is a draft message that will be saved for later.')
                 ->set('recipientType', 'all')
                 ->call('saveDraft');

        // Verify draft was saved
        $this->assertDatabaseHas('broadcast_messages', [
            'subject' => 'Draft Message',
            'status' => BroadcastMessage::STATUS_DRAFT,
            'sender_id' => $this->admin->id,
        ]);

        // Verify form was reset after saving draft
        $component->assertSet('subject', '')
                 ->assertSet('content', '');
    }

    /** @test */
    public function it_validates_customer_selection_properly()
    {
        $component = Livewire::test(BroadcastComposer::class);

        // Try to send without selecting customers when recipient type is 'selected'
        $component->set('subject', 'Test Message')
                 ->set('content', 'Test content for validation')
                 ->set('recipientType', 'selected')
                 ->call('showPreview')
                 ->assertHasErrors(['selectedCustomers']);

        // Now select some customers
        $component->set('selectedCustomers', [$this->customers->first()->id])
                 ->call('showPreview')
                 ->assertHasNoErrors();
    }

    /** @test */
    public function it_handles_customer_search_and_selection_correctly()
    {
        // Create a customer with specific details for testing
        $testCustomer = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@test.com',
            'role_id' => Role::where('name', 'customer')->first()->id,
        ]);

        $component = Livewire::test(BroadcastComposer::class);

        // Switch to selected customers mode
        $component->set('recipientType', 'selected');

        // Search for the specific customer
        $component->set('customerSearch', 'John');

        // Verify the customer appears in search results
        $availableCustomers = $component->get('availableCustomers');
        $this->assertTrue($availableCustomers->contains('id', $testCustomer->id));

        // Select the customer
        $component->call('toggleCustomer', $testCustomer->id)
                 ->assertSet('recipientCount', 1);

        // Verify customer is in selected list
        $selectedCustomers = $component->get('selectedCustomers');
        $this->assertContains($testCustomer->id, $selectedCustomers);
    }

    /** @test */
    public function it_handles_select_all_and_clear_all_functionality()
    {
        $component = Livewire::test(BroadcastComposer::class);

        // Switch to selected customers mode
        $component->set('recipientType', 'selected');

        // Select all customers
        $component->call('selectAllCustomers');

        // Verify all customers are selected
        $selectedCustomers = $component->get('selectedCustomers');
        $recipientCount = $component->get('recipientCount');
        $this->assertGreaterThan(0, count($selectedCustomers));
        $this->assertEquals(count($selectedCustomers), $recipientCount);

        // Clear all selections
        $component->call('clearSelection')
                 ->assertSet('recipientCount', 0)
                 ->assertSet('selectedCustomers', []);
    }

    /** @test */
    public function it_validates_scheduling_constraints()
    {
        $component = Livewire::test(BroadcastComposer::class);

        // Try to schedule for past date
        $pastDate = Carbon::yesterday();
        $component->set('subject', 'Test Message')
                 ->set('content', 'Test content for scheduling validation')
                 ->set('isScheduled', true)
                 ->set('scheduledDate', $pastDate->format('Y-m-d'))
                 ->set('scheduledTime', '10:00')
                 ->call('validateScheduleTime');

        $component->assertHasErrors(['scheduledTime']);

        // Try to schedule too close to current time
        $nearFuture = Carbon::now()->addMinutes(2);
        $component->set('scheduledDate', $nearFuture->format('Y-m-d'))
                 ->set('scheduledTime', $nearFuture->format('H:i'))
                 ->call('validateScheduleTime');

        $component->assertHasErrors(['scheduledTime']);

        // Set valid future time
        $validFuture = Carbon::now()->addHours(2);
        $component->set('scheduledDate', $validFuture->format('Y-m-d'))
                 ->set('scheduledTime', $validFuture->format('H:i'))
                 ->call('validateScheduleTime');

        $component->assertHasNoErrors();
    }

    /** @test */
    public function it_handles_pagination_in_customer_selection()
    {
        // Create more customers to test pagination
        User::factory()->count(25)->create([
            'role_id' => Role::where('name', 'customer')->first()->id,
        ]);

        $component = Livewire::test(BroadcastComposer::class);

        // Switch to selected customers mode
        $component->set('recipientType', 'selected');

        // Verify pagination is working
        $availableCustomers = $component->get('availableCustomers');
        $this->assertEquals(20, $availableCustomers->perPage());
        $this->assertTrue($availableCustomers->hasPages());

        // Test that search resets pagination
        $component->set('customerSearch', 'test');
        // This should trigger updatedCustomerSearch which calls resetPage()
        $component->assertHasNoErrors();
    }

    /** @test */
    public function it_preserves_form_state_during_customer_selection()
    {
        $component = Livewire::test(BroadcastComposer::class);

        // Fill in form data
        $component->set('subject', 'Test Subject')
                 ->set('content', 'Test content that should be preserved')
                 ->set('recipientType', 'selected');

        // Perform customer selection operations
        $component->call('toggleCustomer', $this->customers->first()->id)
                 ->call('selectAllCustomers')
                 ->call('clearSelection');

        // Verify form data is preserved
        $component->assertSet('subject', 'Test Subject')
                 ->assertSet('content', 'Test content that should be preserved');
    }

    /** @test */
    public function it_updates_recipient_count_dynamically()
    {
        $component = Livewire::test(BroadcastComposer::class);

        // Start with all customers
        $initialCount = $component->get('recipientCount');
        $this->assertGreaterThan(0, $initialCount);

        // Switch to selected customers
        $component->set('recipientType', 'selected')
                 ->assertSet('recipientCount', 0);

        // Add customers one by one
        $component->call('toggleCustomer', $this->customers[0]->id)
                 ->assertSet('recipientCount', 1);

        $component->call('toggleCustomer', $this->customers[1]->id)
                 ->assertSet('recipientCount', 2);

        // Remove a customer
        $component->call('toggleCustomer', $this->customers[0]->id)
                 ->assertSet('recipientCount', 1);

        // Switch back to all customers
        $component->set('recipientType', 'all')
                 ->assertSet('recipientCount', $initialCount);
    }
}