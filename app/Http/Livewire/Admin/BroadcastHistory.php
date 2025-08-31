<?php

namespace App\Http\Livewire\Admin;

use App\Models\BroadcastMessage;
use App\Services\BroadcastMessageService;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class BroadcastHistory extends Component
{
    use WithPagination;

    public $selectedBroadcast = null;
    public $showDetails = false;
    public $filterStatus = 'all';
    public $searchTerm = '';

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'filterStatus' => ['except' => 'all'],
        'searchTerm' => ['except' => ''],
    ];

    public function updatedSearchTerm()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function getBroadcastsProperty()
    {
        $query = BroadcastMessage::with(['sender', 'recipients.customer'])
            ->orderBy('created_at', 'desc');

        // Apply status filter
        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        // Apply search filter
        if (!empty($this->searchTerm)) {
            $query->where(function($q) {
                $q->where('subject', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('content', 'like', '%' . $this->searchTerm . '%')
                  ->orWhereHas('sender', function($senderQuery) {
                      $senderQuery->where('first_name', 'like', '%' . $this->searchTerm . '%')
                                  ->orWhere('last_name', 'like', '%' . $this->searchTerm . '%')
                                  ->orWhere('email', 'like', '%' . $this->searchTerm . '%');
                  });
            });
        }

        return $query->paginate(15);
    }

    public function showBroadcastDetails($broadcastId)
    {
        $this->selectedBroadcast = BroadcastMessage::with([
            'sender', 
            'recipients.customer', 
            'deliveries.customer'
        ])->findOrFail($broadcastId);
        
        $this->showDetails = true;
    }

    public function hideBroadcastDetails()
    {
        $this->selectedBroadcast = null;
        $this->showDetails = false;
    }

    public function cancelScheduledBroadcast($broadcastId)
    {
        try {
            $broadcast = BroadcastMessage::findOrFail($broadcastId);
            
            if ($broadcast->status !== BroadcastMessage::STATUS_SCHEDULED) {
                session()->flash('error', 'Only scheduled messages can be cancelled.');
                return;
            }

            if ($broadcast->scheduled_at && $broadcast->scheduled_at->isPast()) {
                session()->flash('error', 'Cannot cancel a message that was scheduled to be sent in the past.');
                return;
            }

            $broadcast->update(['status' => BroadcastMessage::STATUS_DRAFT]);
            
            session()->flash('success', 'Scheduled message cancelled successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to cancel scheduled message: ' . $e->getMessage());
        }
    }

    public function resendBroadcast($broadcastId)
    {
        try {
            $broadcast = BroadcastMessage::findOrFail($broadcastId);
            
            if ($broadcast->status === BroadcastMessage::STATUS_SENDING) {
                session()->flash('error', 'Message is currently being sent.');
                return;
            }

            $broadcastService = app(BroadcastMessageService::class);
            $broadcastService->sendBroadcast($broadcast->id);
            
            session()->flash('success', 'Message queued for resending.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to resend message: ' . $e->getMessage());
        }
    }

    public function editDraft($broadcastId)
    {
        try {
            $broadcast = BroadcastMessage::findOrFail($broadcastId);
            
            if ($broadcast->status !== BroadcastMessage::STATUS_DRAFT) {
                session()->flash('error', 'Only draft messages can be edited.');
                return;
            }

            // Store draft ID in session for composer to pick up
            session(['edit_draft_id' => $broadcastId]);
            
            // Redirect to composer page
            return redirect()->route('admin.broadcast-messages.create');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to edit draft: ' . $e->getMessage());
        }
    }

    public function deleteDraft($broadcastId)
    {
        try {
            $broadcast = BroadcastMessage::findOrFail($broadcastId);
            
            if ($broadcast->status !== BroadcastMessage::STATUS_DRAFT) {
                session()->flash('error', 'Only draft messages can be deleted.');
                return;
            }

            $broadcast->delete();
            
            session()->flash('success', 'Draft deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete draft: ' . $e->getMessage());
        }
    }

    public function getStatusBadgeClass($status)
    {
        switch ($status) {
            case BroadcastMessage::STATUS_DRAFT:
                return 'bg-gray-100 text-gray-800';
            case BroadcastMessage::STATUS_SCHEDULED:
                return 'bg-blue-100 text-blue-800';
            case BroadcastMessage::STATUS_SENDING:
                return 'bg-yellow-100 text-yellow-800';
            case BroadcastMessage::STATUS_SENT:
                return 'bg-green-100 text-green-800';
            case BroadcastMessage::STATUS_FAILED:
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    public function render()
    {
        return view('livewire.admin.broadcast-history', [
            'broadcasts' => $this->broadcasts,
        ]);
    }
}