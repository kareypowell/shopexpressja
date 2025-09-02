<?php

namespace App\Http\Livewire\Admin;

use App\Models\User;
use App\Models\BroadcastMessage;
use App\Services\BroadcastMessageService;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use HTMLPurifier;
use HTMLPurifier_Config;

class BroadcastComposer extends Component
{
    use WithPagination;

    // Message composition properties
    public $subject = '';
    public $content = '';
    public $recipientType = 'all';
    public $selectedCustomers = [];
    public $customerSearch = '';
    public $isDraft = false;

    // Scheduling properties
    public $isScheduled = false;
    public $scheduledDate = '';
    public $scheduledTime = '';

    // UI state properties
    public $showPreview = false;
    public $showCustomerSelection = false;
    public $recipientCount = 0;

    // Pagination
    protected $paginationTheme = 'tailwind';

    protected $rules = [
        'subject' => 'required|string|max:255',
        'content' => 'required|string|min:10',
        'recipientType' => 'required|in:all,selected',
        'selectedCustomers' => 'required_if:recipientType,selected|array|min:1',
        'selectedCustomers.*' => 'exists:users,id',
        'scheduledDate' => 'required_if:isScheduled,true|date|after:today',
        'scheduledTime' => 'required_if:isScheduled,true|date_format:H:i',
    ];

    public function getRules()
    {
        $rules = [
            'subject' => 'required|string|max:255',
            'content' => 'required|string|min:10',
            'recipientType' => 'required|in:all,selected',
        ];

        // Only require selectedCustomers if recipientType is 'selected'
        if ($this->recipientType === 'selected') {
            $rules['selectedCustomers'] = 'required|array|min:1';
            $rules['selectedCustomers.*'] = 'exists:users,id';
        }

        // Add scheduling validation if scheduled
        if ($this->isScheduled) {
            $rules['scheduledDate'] = 'required|date|after:today';
            $rules['scheduledTime'] = 'required|date_format:H:i';
            
            // Add custom validation for scheduled datetime
            if ($this->scheduledDate && $this->scheduledTime) {
                $rules['scheduledDate'] = [
                    'required',
                    'date',
                    function ($attribute, $value, $fail) {
                        $scheduledDateTime = Carbon::createFromFormat('Y-m-d H:i', $value . ' ' . $this->scheduledTime);
                        if ($scheduledDateTime->isPast()) {
                            $fail('The scheduled date and time must be in the future.');
                        }
                        if ($scheduledDateTime->diffInMinutes(Carbon::now()) < 5) {
                            $fail('The scheduled date and time must be at least 5 minutes in the future.');
                        }
                    }
                ];
            }
        }
        
        return $rules;
    }

    protected $messages = [
        'subject.required' => 'Please enter a subject for your message.',
        'content.required' => 'Please enter the message content.',
        'content.min' => 'Message content must be at least 10 characters.',
        'selectedCustomers.required_if' => 'Please select at least one customer.',
        'selectedCustomers.min' => 'Please select at least one customer.',
        'scheduledDate.after' => 'Scheduled date must be in the future.',
        'scheduledTime.required_if' => 'Please select a time for the scheduled message.',
    ];

    public function mount()
    {
        // Check if we're editing a draft
        if (session()->has('edit_draft_id')) {
            $this->loadDraft(session('edit_draft_id'));
            session()->forget('edit_draft_id');
        }
        
        $this->updateRecipientCount();
    }

    private function loadDraft($draftId)
    {
        try {
            $draft = BroadcastMessage::with('recipients.customer')->findOrFail($draftId);
            
            if ($draft->status !== BroadcastMessage::STATUS_DRAFT) {
                session()->flash('error', 'Only draft messages can be edited.');
                return;
            }

            // Load draft data
            $this->subject = $draft->subject;
            $this->content = $draft->content;
            $this->recipientType = $draft->recipient_type;
            
            if ($draft->recipient_type === 'selected') {
                $this->selectedCustomers = $draft->recipients->pluck('customer_id')->toArray();
            }
            
            if ($draft->scheduled_at) {
                $this->isScheduled = true;
                $this->scheduledDate = $draft->scheduled_at->format('Y-m-d');
                $this->scheduledTime = $draft->scheduled_at->format('H:i');
            }
            
            $this->isDraft = true;
            session()->flash('success', 'Draft loaded for editing.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load draft: ' . $e->getMessage());
        }
    }

    public function updatedContent($value)
    {
        $this->content = $this->sanitizeHtmlContent($value);
    }

    private function sanitizeHtmlContent($content)
    {
        $config = HTMLPurifier_Config::createDefault();
        
        // Allow safe HTML tags and attributes
        $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,h1,h2,h3,h4,h5,h6,ul,ol,li,a[href|title],img[src|alt|width|height|style],table,thead,tbody,tr,th,td,blockquote,div[class|style],span[class|style]');
        
        // Allow some CSS properties for styling
        $config->set('CSS.AllowedProperties', 'font-weight,font-style,text-decoration,color,background-color,text-align,margin,padding,border,width,height');
        
        // Enable HTML5 parsing
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        
        // Disable cache for simplicity (you may want to enable this in production)
        $config->set('Cache.DefinitionImpl', null);
        
        $purifier = new HTMLPurifier($config);
        return $purifier->purify($content);
    }

    public function updatedRecipientType()
    {
        if ($this->recipientType === 'all') {
            $this->selectedCustomers = [];
            $this->showCustomerSelection = false;
        } else {
            $this->showCustomerSelection = true;
        }
        $this->updateRecipientCount();
    }

    public function updatedCustomerSearch()
    {
        $this->resetPage();
    }

    public function updatedIsScheduled()
    {
        if (!$this->isScheduled) {
            $this->scheduledDate = '';
            $this->scheduledTime = '';
        } else {
            // Set default to tomorrow at 9 AM
            $this->scheduledDate = Carbon::tomorrow()->format('Y-m-d');
            $this->scheduledTime = '09:00';
        }
    }

    public function updatedScheduledDate()
    {
        $this->validateScheduleTime();
    }

    public function updatedScheduledTime()
    {
        $this->validateScheduleTime();
    }

    public function validateScheduleTime()
    {
        if ($this->isScheduled && $this->scheduledDate && $this->scheduledTime) {
            try {
                $scheduledDateTime = Carbon::createFromFormat('Y-m-d H:i', $this->scheduledDate . ' ' . $this->scheduledTime);
                
                if ($scheduledDateTime->isPast()) {
                    $this->addError('scheduledTime', 'The scheduled date and time must be in the future.');
                } elseif ($scheduledDateTime->diffInMinutes(Carbon::now()) < 5) {
                    $this->addError('scheduledTime', 'The scheduled date and time must be at least 5 minutes in the future.');
                } else {
                    $this->resetErrorBag(['scheduledDate', 'scheduledTime']);
                }
            } catch (\Exception $e) {
                $this->addError('scheduledTime', 'Invalid date or time format.');
            }
        }
    }

    public function getAvailableCustomersProperty()
    {
        return User::forCustomerSearch($this->customerSearch)
            ->activeCustomers()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(20);
    }

    public function toggleCustomer($customerId)
    {
        if (in_array($customerId, $this->selectedCustomers)) {
            $this->selectedCustomers = array_values(array_diff($this->selectedCustomers, [$customerId]));
        } else {
            $this->selectedCustomers[] = $customerId;
        }
        $this->updateRecipientCount();
    }

    public function selectAllCustomers()
    {
        $customerIds = User::forCustomerSearch($this->customerSearch)
            ->activeCustomers()
            ->pluck('id')
            ->toArray();
        
        $this->selectedCustomers = array_unique(array_merge($this->selectedCustomers, $customerIds));
        $this->updateRecipientCount();
    }

    public function clearSelection()
    {
        $this->selectedCustomers = [];
        $this->updateRecipientCount();
    }

    public function updateRecipientCount()
    {
        if ($this->recipientType === 'all') {
            $this->recipientCount = User::activeCustomers()->count();
        } else {
            $this->recipientCount = count($this->selectedCustomers);
        }
    }

    public function saveDraft()
    {
        $this->isDraft = true;
        $this->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string|min:10',
        ]);

        try {
            $broadcastService = app(BroadcastMessageService::class);
            
            $data = [
                'subject' => $this->subject,
                'content' => $this->sanitizeHtmlContent($this->content),
                'sender_id' => Auth::id(),
                'recipient_type' => $this->recipientType,
                'recipient_count' => $this->recipientCount,
                'status' => BroadcastMessage::STATUS_DRAFT,
                'selected_customers' => $this->recipientType === 'selected' ? $this->selectedCustomers : [],
            ];

            $result = $broadcastService->saveDraft($data);
            
            if ($result['success']) {
                session()->flash('success', 'Draft saved successfully!');
                $this->resetForm();
            } else {
                session()->flash('error', $result['message']);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save draft: ' . $e->getMessage());
        }
    }

    public function showPreview()
    {
        // Basic validation first
        $this->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string|min:10',
        ]);

        // Update recipient count to ensure it's current
        $this->updateRecipientCount();

        // Additional validation for recipients
        if ($this->recipientType === 'selected' && empty($this->selectedCustomers)) {
            $this->addError('selectedCustomers', 'Please select at least one customer.');
            return;
        }

        // Allow preview even with 0 recipients, but show warning in the preview modal
        if ($this->recipientCount === 0 && $this->recipientType === 'selected') {
            // Only block if selected type has no customers selected
            session()->flash('error', 'Please select at least one customer to preview.');
            return;
        }

        // Validate scheduling if enabled
        if ($this->isScheduled) {
            $this->validate([
                'scheduledDate' => 'required|date|after:today',
                'scheduledTime' => 'required|date_format:H:i',
            ]);
            
            $this->validateScheduleTime();
            
            if ($this->getErrorBag()->has(['scheduledDate', 'scheduledTime'])) {
                return;
            }
        }

        // Sanitize content before preview
        $this->content = $this->sanitizeHtmlContent($this->content);
        
        $this->showPreview = true;
    }

    public function hidePreview()
    {
        $this->showPreview = false;
    }

    /**
     * Get preview content with placeholders replaced
     *
     * @return array
     */
    public function getPreviewContentProperty()
    {
        $broadcastService = app(BroadcastMessageService::class);
        
        // Get a sample customer for preview if available
        $sampleCustomer = null;
        if ($this->recipientType === 'selected' && !empty($this->selectedCustomers)) {
            $sampleCustomer = User::find($this->selectedCustomers[0]);
        } elseif ($this->recipientType === 'all') {
            $sampleCustomer = User::activeCustomers()->first();
        }
        
        return [
            'subject' => $broadcastService->previewWithPlaceholders($this->subject, $sampleCustomer),
            'content' => $broadcastService->previewWithPlaceholders($this->content, $sampleCustomer),
            'sample_customer' => $sampleCustomer
        ];
    }

    public function sendNow()
    {
        $this->isScheduled = false;
        $this->send();
    }

    public function scheduleMessage()
    {
        $this->isScheduled = true;
        $this->send();
    }

    private function send()
    {
        $this->validate($this->getRules());

        try {
            $broadcastService = app(BroadcastMessageService::class);
            
            $data = [
                'subject' => $this->subject,
                'content' => $this->sanitizeHtmlContent($this->content),
                'sender_id' => Auth::id(),
                'recipient_type' => $this->recipientType,
                'recipient_count' => $this->recipientCount,
            ];

            // Only include selected_customers if recipient type is 'selected'
            if ($this->recipientType === 'selected') {
                $data['selected_customers'] = $this->selectedCustomers;
            }

            if ($this->isScheduled) {
                $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', $this->scheduledDate . ' ' . $this->scheduledTime);
                
                // Additional validation for scheduled time
                if ($scheduledAt->isPast()) {
                    session()->flash('error', 'Cannot schedule message for a past date and time.');
                    return;
                }
                
                if ($scheduledAt->diffInMinutes(Carbon::now()) < 5) {
                    session()->flash('error', 'Scheduled time must be at least 5 minutes in the future.');
                    return;
                }
                
                $broadcastResult = $broadcastService->createBroadcast($data);
                if (!$broadcastResult['success']) {
                    session()->flash('error', $broadcastResult['message']);
                    return;
                }
                
                $scheduleResult = $broadcastService->scheduleBroadcast($broadcastResult['broadcast_message']->id, $scheduledAt);
                if (!$scheduleResult['success']) {
                    session()->flash('error', $scheduleResult['message']);
                    return;
                }
                
                session()->flash('success', 'Message scheduled successfully for ' . $scheduledAt->format('M j, Y \a\t g:i A') . '!');
            } else {
                $broadcastResult = $broadcastService->createBroadcast($data);
                if (!$broadcastResult['success']) {
                    session()->flash('error', $broadcastResult['message']);
                    return;
                }
                
                $sendResult = $broadcastService->sendBroadcast($broadcastResult['broadcast_message']->id);
                if (!$sendResult['success']) {
                    session()->flash('error', $sendResult['message']);
                    return;
                }
                
                session()->flash('success', 'Message sent successfully to ' . $this->recipientCount . ' recipients!');
            }

            $this->resetForm();
            $this->showPreview = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to send message: ' . $e->getMessage());
        }
    }

    private function resetForm()
    {
        $this->subject = '';
        $this->content = '';
        $this->recipientType = 'all';
        $this->selectedCustomers = [];
        $this->customerSearch = '';
        $this->isScheduled = false;
        $this->scheduledDate = '';
        $this->scheduledTime = '';
        $this->showCustomerSelection = false;
        $this->isDraft = false;
        $this->updateRecipientCount();
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.admin.broadcast-composer', [
            'availableCustomers' => $this->availableCustomers,
        ]);
    }
}