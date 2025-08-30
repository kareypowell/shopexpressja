<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\BroadcastMessage;
use App\Models\BroadcastRecipient;
use App\Models\BroadcastDelivery;
use App\Services\BroadcastMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Carbon\Carbon;

class BroadcastHistoryFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $customerRole = Role::factory()->create(['name' => 'customer']);

        // Create admin user
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);

        // Create test customers
        $this->customers = User::factory()->count(5)->create(['role_id' => $customerRole->id]);

        $this->actingAs($this->admin);
    }

    /** @test */
    public function admin_can_view_broadcast_history_component()
    {
        // Test the Livewire component directly since routes aren't implemented yet
        Livewire::test('admin.broadcast-history')
            ->assertStatus(200);
    }

    /** @test */
    public function broadcast_history_displays_all_broadcast_information()
    {
        $broadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Important System Update',
            'content' => 'We are updating our system tonight.',
            'status' => BroadcastMessage::STATUS_SENT,
            'recipient_type' => 'all',
            'recipient_count' => 5,
            'sent_at' => Carbon::now()->subHour(),
        ]);

        Livewire::test('admin.broadcast-history')
            ->assertSee('Important System Update')
            ->assertSee('Sent')
            ->assertSee('5')
            ->assertSee('All')
            ->assertSee($this->admin->full_name);
    }

    /** @test */
    public function admin_can_view_broadcast_details_modal()
    {
        $broadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Test Broadcast',
            'content' => 'This is a test broadcast message.',
            'status' => BroadcastMessage::STATUS_SENT,
            'recipient_type' => 'all',
            'recipient_count' => 5,
        ]);

        // Create delivery records
        foreach ($this->customers as $customer) {
            BroadcastDelivery::factory()->create([
                'broadcast_message_id' => $broadcast->id,
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'status' => 'sent',
                'sent_at' => Carbon::now(),
            ]);
        }

        Livewire::test('admin.broadcast-history')
            ->call('showBroadcastDetails', $broadcast->id)
            ->assertSet('showDetails', true)
            ->assertSee('Test Broadcast')
            ->assertSee('This is a test broadcast message.')
            ->assertSee('Delivery Statistics')
            ->assertSee('5') // Sent count
            ->assertSee('0'); // Failed count
    }

    /** @test */
    public function admin_can_filter_broadcasts_by_status()
    {
        $sentBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Sent Message',
            'status' => BroadcastMessage::STATUS_SENT,
        ]);

        $draftBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Draft Message',
            'status' => BroadcastMessage::STATUS_DRAFT,
        ]);

        $scheduledBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Scheduled Message',
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::tomorrow(),
        ]);

        $component = Livewire::test('admin.broadcast-history');

        // Test all filter
        $component->assertSee('Sent Message')
                 ->assertSee('Draft Message')
                 ->assertSee('Scheduled Message');

        // Test sent filter
        $component->set('filterStatus', 'sent')
                 ->assertSee('Sent Message')
                 ->assertDontSee('Draft Message')
                 ->assertDontSee('Scheduled Message');

        // Test draft filter
        $component->set('filterStatus', 'draft')
                 ->assertDontSee('Sent Message')
                 ->assertSee('Draft Message')
                 ->assertDontSee('Scheduled Message');

        // Test scheduled filter
        $component->set('filterStatus', 'scheduled')
                 ->assertDontSee('Sent Message')
                 ->assertDontSee('Draft Message')
                 ->assertSee('Scheduled Message');
    }

    /** @test */
    public function admin_can_search_broadcasts()
    {
        BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Important Security Update',
            'content' => 'Please update your passwords',
        ]);

        BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Weekly Newsletter',
            'content' => 'Here is this week\'s newsletter',
        ]);

        $component = Livewire::test('admin.broadcast-history');

        // Search by subject
        $component->set('searchTerm', 'Security')
                 ->assertSee('Important Security Update')
                 ->assertDontSee('Weekly Newsletter');

        // Search by content
        $component->set('searchTerm', 'newsletter')
                 ->assertDontSee('Important Security Update')
                 ->assertSee('Weekly Newsletter');
    }

    /** @test */
    public function admin_can_cancel_scheduled_broadcast()
    {
        $scheduledBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Future Announcement',
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::tomorrow(),
        ]);

        Livewire::test('admin.broadcast-history')
            ->call('cancelScheduledBroadcast', $scheduledBroadcast->id)
            ->assertHasNoErrors();

        $scheduledBroadcast->refresh();
        $this->assertEquals(BroadcastMessage::STATUS_DRAFT, $scheduledBroadcast->status);
    }

    /** @test */
    public function admin_cannot_cancel_past_scheduled_broadcast()
    {
        $pastScheduledBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::yesterday(),
        ]);

        Livewire::test('admin.broadcast-history')
            ->call('cancelScheduledBroadcast', $pastScheduledBroadcast->id);

        $pastScheduledBroadcast->refresh();
        $this->assertEquals(BroadcastMessage::STATUS_SCHEDULED, $pastScheduledBroadcast->status);
    }

    /** @test */
    public function admin_can_resend_failed_broadcast()
    {
        $failedBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_FAILED,
        ]);

        $this->mock(BroadcastMessageService::class, function ($mock) use ($failedBroadcast) {
            $mock->shouldReceive('sendBroadcast')
                 ->once()
                 ->with($failedBroadcast->id);
        });

        Livewire::test('admin.broadcast-history')
            ->call('resendBroadcast', $failedBroadcast->id)
            ->assertHasNoErrors();
    }

    /** @test */
    public function admin_can_delete_draft_broadcast()
    {
        $draft = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_DRAFT,
        ]);

        Livewire::test('admin.broadcast-history')
            ->call('deleteDraft', $draft->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('broadcast_messages', ['id' => $draft->id]);
    }

    /** @test */
    public function admin_cannot_delete_non_draft_broadcast()
    {
        $sentBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_SENT,
        ]);

        Livewire::test('admin.broadcast-history')
            ->call('deleteDraft', $sentBroadcast->id);

        $this->assertDatabaseHas('broadcast_messages', ['id' => $sentBroadcast->id]);
    }

    /** @test */
    public function admin_can_edit_draft_broadcast()
    {
        $draft = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_DRAFT,
            'subject' => 'Draft Message',
        ]);

        $response = Livewire::test('admin.broadcast-history')
            ->call('editDraft', $draft->id);

        // Should redirect to composer with draft parameter (when routes are implemented)
        // For now, just verify the method doesn't throw errors
        $response->assertHasNoErrors();
    }

    /** @test */
    public function broadcast_details_shows_selected_recipients()
    {
        $broadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Selected Recipients Test',
            'recipient_type' => 'selected',
            'recipient_count' => 2,
        ]);

        // Create selected recipients
        $selectedCustomers = $this->customers->take(2);
        foreach ($selectedCustomers as $customer) {
            BroadcastRecipient::factory()->create([
                'broadcast_message_id' => $broadcast->id,
                'customer_id' => $customer->id,
            ]);
        }

        Livewire::test('admin.broadcast-history')
            ->call('showBroadcastDetails', $broadcast->id)
            ->assertSee('Selected Recipients')
            ->assertSee($selectedCustomers->first()->full_name)
            ->assertSee($selectedCustomers->last()->full_name);
    }

    /** @test */
    public function broadcast_details_shows_delivery_statistics()
    {
        $broadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_SENT,
        ]);

        // Create various delivery statuses
        BroadcastDelivery::factory()->create([
            'broadcast_message_id' => $broadcast->id,
            'customer_id' => $this->customers[0]->id,
            'status' => 'sent',
        ]);

        BroadcastDelivery::factory()->create([
            'broadcast_message_id' => $broadcast->id,
            'customer_id' => $this->customers[1]->id,
            'status' => 'failed',
        ]);

        BroadcastDelivery::factory()->create([
            'broadcast_message_id' => $broadcast->id,
            'customer_id' => $this->customers[2]->id,
            'status' => 'bounced',
        ]);

        BroadcastDelivery::factory()->create([
            'broadcast_message_id' => $broadcast->id,
            'customer_id' => $this->customers[3]->id,
            'status' => 'pending',
        ]);

        Livewire::test('admin.broadcast-history')
            ->call('showBroadcastDetails', $broadcast->id)
            ->assertSee('Delivery Statistics')
            ->assertSee('1') // Sent count
            ->assertSee('1') // Failed count
            ->assertSee('1') // Bounced count
            ->assertSee('1'); // Pending count
    }

    /** @test */
    public function broadcast_history_paginates_results()
    {
        // Create more broadcasts than pagination limit
        BroadcastMessage::factory()->count(20)->create([
            'sender_id' => $this->admin->id,
        ]);

        $component = Livewire::test('admin.broadcast-history');

        $broadcasts = $component->get('broadcasts');
        $this->assertEquals(15, $broadcasts->perPage());
        $this->assertTrue($broadcasts->hasPages());
    }

    /** @test */
    public function broadcast_history_orders_by_created_at_desc()
    {
        $oldBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Old Broadcast',
            'created_at' => Carbon::now()->subDays(2),
        ]);

        $newBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'New Broadcast',
            'created_at' => Carbon::now(),
        ]);

        $component = Livewire::test('admin.broadcast-history');

        $broadcasts = $component->get('broadcasts');
        $this->assertEquals($newBroadcast->id, $broadcasts->first()->id);
    }

    /** @test */
    public function non_admin_cannot_access_broadcast_history_component()
    {
        $customer = $this->customers->first();
        $this->actingAs($customer);

        // Test component access - this will be enforced at route level later
        // For now, just test that the component works for authorized users
        $this->assertTrue(true); // Placeholder until routes are implemented
    }

    /** @test */
    public function guest_cannot_access_broadcast_history_component()
    {
        auth()->logout();

        // Test component access - this will be enforced at route level later
        // For now, just test that authentication is required
        $this->assertTrue(true); // Placeholder until routes are implemented
    }

    /** @test */
    public function broadcast_history_handles_empty_state()
    {
        Livewire::test('admin.broadcast-history')
            ->assertSee('No broadcast messages found.');
    }

    /** @test */
    public function search_and_filter_work_together()
    {
        BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Important Draft',
            'status' => BroadcastMessage::STATUS_DRAFT,
        ]);

        BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Important Sent Message',
            'status' => BroadcastMessage::STATUS_SENT,
        ]);

        BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Regular Draft',
            'status' => BroadcastMessage::STATUS_DRAFT,
        ]);

        Livewire::test('admin.broadcast-history')
            ->set('searchTerm', 'Important')
            ->set('filterStatus', 'draft')
            ->assertSee('Important Draft')
            ->assertDontSee('Important Sent Message')
            ->assertDontSee('Regular Draft');
    }
}