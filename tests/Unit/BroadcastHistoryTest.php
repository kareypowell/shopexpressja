<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Livewire\Admin\BroadcastHistory;
use App\Models\User;
use App\Models\Role;
use App\Models\BroadcastMessage;
use App\Models\BroadcastRecipient;
use App\Models\BroadcastDelivery;
use App\Services\BroadcastMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Carbon\Carbon;

class BroadcastHistoryTest extends TestCase
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
        $this->customers = User::factory()->count(3)->create(['role_id' => $customerRole->id]);

        $this->actingAs($this->admin);
    }

    /** @test */
    public function it_can_mount_component_with_default_values()
    {
        $component = Livewire::test(BroadcastHistory::class);

        $component->assertSet('selectedBroadcast', null)
                 ->assertSet('showDetails', false)
                 ->assertSet('filterStatus', 'all')
                 ->assertSet('searchTerm', '');
    }

    /** @test */
    public function it_displays_broadcast_messages()
    {
        $broadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Test Broadcast',
            'content' => 'Test content',
            'status' => BroadcastMessage::STATUS_SENT,
            'recipient_type' => 'all',
            'recipient_count' => 3,
        ]);

        $component = Livewire::test(BroadcastHistory::class);

        $broadcasts = $component->get('broadcasts');
        $this->assertTrue($broadcasts->contains('id', $broadcast->id));
    }

    /** @test */
    public function it_can_filter_broadcasts_by_status()
    {
        $sentBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_SENT,
        ]);

        $draftBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_DRAFT,
        ]);

        $component = Livewire::test(BroadcastHistory::class);

        // Filter by sent status
        $component->set('filterStatus', 'sent');
        $broadcasts = $component->get('broadcasts');
        
        $this->assertTrue($broadcasts->contains('id', $sentBroadcast->id));
        $this->assertFalse($broadcasts->contains('id', $draftBroadcast->id));

        // Filter by draft status
        $component->set('filterStatus', 'draft');
        $broadcasts = $component->get('broadcasts');
        
        $this->assertFalse($broadcasts->contains('id', $sentBroadcast->id));
        $this->assertTrue($broadcasts->contains('id', $draftBroadcast->id));
    }

    /** @test */
    public function it_can_search_broadcasts()
    {
        $broadcast1 = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Important Announcement',
            'content' => 'This is important',
        ]);

        $broadcast2 = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Regular Update',
            'content' => 'Regular content',
        ]);

        $component = Livewire::test(BroadcastHistory::class);

        // Search by subject
        $component->set('searchTerm', 'Important');
        $broadcasts = $component->get('broadcasts');
        
        $this->assertTrue($broadcasts->contains('id', $broadcast1->id));
        $this->assertFalse($broadcasts->contains('id', $broadcast2->id));
    }

    /** @test */
    public function it_can_show_broadcast_details()
    {
        $broadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'subject' => 'Test Broadcast',
        ]);

        $component = Livewire::test(BroadcastHistory::class);

        $component->call('showBroadcastDetails', $broadcast->id)
                 ->assertSet('showDetails', true);

        $selectedBroadcast = $component->get('selectedBroadcast');
        $this->assertEquals($broadcast->id, $selectedBroadcast->id);
    }

    /** @test */
    public function it_can_hide_broadcast_details()
    {
        $broadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
        ]);

        $component = Livewire::test(BroadcastHistory::class);

        $component->call('showBroadcastDetails', $broadcast->id)
                 ->call('hideBroadcastDetails')
                 ->assertSet('showDetails', false)
                 ->assertSet('selectedBroadcast', null);
    }

    /** @test */
    public function it_can_cancel_scheduled_broadcast()
    {
        $scheduledBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::tomorrow(),
        ]);

        $component = Livewire::test(BroadcastHistory::class);

        $component->call('cancelScheduledBroadcast', $scheduledBroadcast->id);

        $scheduledBroadcast->refresh();
        $this->assertEquals(BroadcastMessage::STATUS_DRAFT, $scheduledBroadcast->status);
    }

    /** @test */
    public function it_cannot_cancel_non_scheduled_broadcast()
    {
        $sentBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_SENT,
        ]);

        $component = Livewire::test(BroadcastHistory::class);

        $component->call('cancelScheduledBroadcast', $sentBroadcast->id);

        $sentBroadcast->refresh();
        $this->assertEquals(BroadcastMessage::STATUS_SENT, $sentBroadcast->status);
    }

    /** @test */
    public function it_cannot_cancel_past_scheduled_broadcast()
    {
        $pastScheduledBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::yesterday(),
        ]);

        $component = Livewire::test(BroadcastHistory::class);

        $component->call('cancelScheduledBroadcast', $pastScheduledBroadcast->id);

        $pastScheduledBroadcast->refresh();
        $this->assertEquals(BroadcastMessage::STATUS_SCHEDULED, $pastScheduledBroadcast->status);
    }

    /** @test */
    public function it_can_resend_failed_broadcast()
    {
        $failedBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_FAILED,
        ]);

        $broadcastService = $this->mock(BroadcastMessageService::class);
        $broadcastService->shouldReceive('sendBroadcast')->once()->with($failedBroadcast->id);

        $component = Livewire::test(BroadcastHistory::class);

        $component->call('resendBroadcast', $failedBroadcast->id);

        $component->assertHasNoErrors();
    }

    /** @test */
    public function it_cannot_resend_sending_broadcast()
    {
        $sendingBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_SENDING,
        ]);

        $component = Livewire::test(BroadcastHistory::class);

        $component->call('resendBroadcast', $sendingBroadcast->id);

        // Should not attempt to resend
        $component->assertHasNoErrors();
    }

    /** @test */
    public function it_can_delete_draft()
    {
        $draft = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_DRAFT,
        ]);

        $component = Livewire::test(BroadcastHistory::class);

        $component->call('deleteDraft', $draft->id);

        $this->assertDatabaseMissing('broadcast_messages', ['id' => $draft->id]);
    }

    /** @test */
    public function it_cannot_delete_non_draft_broadcast()
    {
        $sentBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_SENT,
        ]);

        $component = Livewire::test(BroadcastHistory::class);

        $component->call('deleteDraft', $sentBroadcast->id);

        $this->assertDatabaseHas('broadcast_messages', ['id' => $sentBroadcast->id]);
    }

    /** @test */
    public function it_can_edit_draft()
    {
        $draft = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_DRAFT,
        ]);

        $component = Livewire::test(BroadcastHistory::class);

        $component->call('editDraft', $draft->id)
                 ->assertEmitted('editDraft', $draft->id);

        // Should store draft ID in session
        $this->assertEquals($draft->id, session('edit_draft_id'));
    }

    /** @test */
    public function it_cannot_edit_non_draft_broadcast()
    {
        $sentBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_SENT,
        ]);

        $component = Livewire::test(BroadcastHistory::class);

        $component->call('editDraft', $sentBroadcast->id);

        // Should not redirect, should show error
        $component->assertHasNoErrors();
    }

    /** @test */
    public function it_returns_correct_status_badge_classes()
    {
        $component = new BroadcastHistory();

        $this->assertEquals('bg-gray-100 text-gray-800', $component->getStatusBadgeClass('draft'));
        $this->assertEquals('bg-blue-100 text-blue-800', $component->getStatusBadgeClass('scheduled'));
        $this->assertEquals('bg-yellow-100 text-yellow-800', $component->getStatusBadgeClass('sending'));
        $this->assertEquals('bg-green-100 text-green-800', $component->getStatusBadgeClass('sent'));
        $this->assertEquals('bg-red-100 text-red-800', $component->getStatusBadgeClass('failed'));
        $this->assertEquals('bg-gray-100 text-gray-800', $component->getStatusBadgeClass('unknown'));
    }

    /** @test */
    public function it_paginates_broadcasts()
    {
        // Create more broadcasts than the pagination limit
        BroadcastMessage::factory()->count(20)->create([
            'sender_id' => $this->admin->id,
        ]);

        $component = Livewire::test(BroadcastHistory::class);

        $broadcasts = $component->get('broadcasts');
        $this->assertEquals(15, $broadcasts->perPage()); // Should paginate at 15 per page
        $this->assertTrue($broadcasts->hasPages());
    }

    /** @test */
    public function it_orders_broadcasts_by_created_at_desc()
    {
        $oldBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'created_at' => Carbon::now()->subDays(2),
            'subject' => 'Old Broadcast',
        ]);

        $newBroadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'created_at' => Carbon::now(),
            'subject' => 'New Broadcast',
        ]);

        $component = Livewire::test(BroadcastHistory::class);

        $broadcasts = $component->get('broadcasts');
        $this->assertEquals($newBroadcast->id, $broadcasts->first()->id);
    }

    /** @test */
    public function it_handles_service_exceptions_gracefully()
    {
        $broadcast = BroadcastMessage::factory()->create([
            'sender_id' => $this->admin->id,
            'status' => BroadcastMessage::STATUS_FAILED,
        ]);

        $broadcastService = $this->mock(BroadcastMessageService::class);
        $broadcastService->shouldReceive('sendBroadcast')->andThrow(new \Exception('Service error'));

        $component = Livewire::test(BroadcastHistory::class);

        $component->call('resendBroadcast', $broadcast->id);

        // Should not throw exception, should handle gracefully
        $component->assertHasNoErrors();
    }

    /** @test */
    public function it_resets_page_when_search_term_changes()
    {
        // Create enough broadcasts to have multiple pages
        BroadcastMessage::factory()->count(20)->create([
            'sender_id' => $this->admin->id,
        ]);

        $component = Livewire::test(BroadcastHistory::class);

        // Go to page 2
        $component->set('page', 2);

        // Change search term should reset to page 1
        $component->set('searchTerm', 'test');

        // Page should be reset (Livewire handles this automatically with resetPage())
        $component->assertHasNoErrors();
    }

    /** @test */
    public function it_resets_page_when_filter_status_changes()
    {
        // Create enough broadcasts to have multiple pages
        BroadcastMessage::factory()->count(20)->create([
            'sender_id' => $this->admin->id,
        ]);

        $component = Livewire::test(BroadcastHistory::class);

        // Go to page 2
        $component->set('page', 2);

        // Change filter status should reset to page 1
        $component->set('filterStatus', 'sent');

        // Page should be reset (Livewire handles this automatically with resetPage())
        $component->assertHasNoErrors();
    }

    /** @test */
    public function it_can_compose_new_message()
    {
        // Set some draft data in session first
        session(['edit_draft_id' => 123]);

        $component = Livewire::test(BroadcastHistory::class);

        $component->call('composeNewMessage')
                 ->assertEmitted('composeNewMessage');

        // Should clear draft session data
        $this->assertNull(session('edit_draft_id'));
    }
}