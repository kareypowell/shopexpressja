<?php

namespace App\Http\Livewire\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Office;
use Livewire\Component;
use Livewire\WithPagination;

class OfficeManagement extends Component
{
    use AuthorizesRequests, WithPagination;

    // Search and filtering
    public string $search = '';
    public bool $showSearchResults = false;

    // Delete modal state
    public bool $showDeleteModal = false;
    public ?Office $selectedOffice = null;
    public array $relationshipCounts = [];

    // UI state
    public string $successMessage = '';
    public string $errorMessage = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    protected $listeners = [
        'officeDeleted' => 'handleOfficeDeleted',
        'refreshOffices' => '$refresh',
    ];

    public function mount()
    {
        $this->authorize('viewAny', Office::class);
    }

    /**
     * Handle search input updates
     */
    public function updatedSearch()
    {
        $this->resetPage();
        $this->showSearchResults = !empty($this->search);
    }

    /**
     * Clear search
     */
    public function clearSearch()
    {
        $this->search = '';
        $this->showSearchResults = false;
        $this->resetPage();
    }

    /**
     * Confirm office deletion
     */
    public function confirmDelete(Office $office)
    {
        $this->authorize('delete', $office);
        
        $this->selectedOffice = $office;
        
        // Get relationship counts for validation
        $this->relationshipCounts = [
            'manifests' => $office->manifests()->count(),
            'packages' => $office->packages()->count(),
            'profiles' => $office->profiles()->count(),
        ];
        
        $this->showDeleteModal = true;
    }

    /**
     * Delete the selected office
     */
    public function deleteOffice()
    {
        if (!$this->selectedOffice) {
            $this->errorMessage = 'No office selected for deletion.';
            return;
        }

        $this->authorize('delete', $this->selectedOffice);

        // Check for relationships that would prevent deletion
        $totalRelationships = array_sum($this->relationshipCounts);
        
        if ($totalRelationships > 0) {
            $this->errorMessage = 'Cannot delete office "' . $this->selectedOffice->name . '" because it has associated records. Please reassign or remove the associated records first.';
            $this->cancelDelete();
            return;
        }

        try {
            $officeName = $this->selectedOffice->name;
            $this->selectedOffice->delete();
            
            $this->successMessage = "Office \"{$officeName}\" has been deleted successfully.";
            $this->cancelDelete();
            
            // Refresh the page to update the list
            $this->resetPage();
            
        } catch (\Exception $e) {
            $this->errorMessage = 'An error occurred while deleting the office. Please try again.';
            \Log::error('Office deletion error: ' . $e->getMessage());
        }
    }

    /**
     * Cancel delete operation
     */
    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->selectedOffice = null;
        $this->relationshipCounts = [];
    }

    /**
     * Handle office deleted event
     */
    public function handleOfficeDeleted($message)
    {
        $this->successMessage = $message;
        $this->resetPage();
    }

    /**
     * Clear messages
     */
    public function clearMessages()
    {
        $this->successMessage = '';
        $this->errorMessage = '';
    }

    /**
     * Get filtered offices
     */
    public function getOfficesProperty()
    {
        $query = Office::query();

        // Apply search
        if (!empty($this->search)) {
            $query->search($this->search);
        }

        return $query->orderBy('name')
                    ->paginate(15);
    }

    /**
     * Get search results summary
     */
    public function getSearchSummaryProperty()
    {
        if (empty($this->search)) {
            return null;
        }

        $totalCount = $this->offices->total();
        
        return [
            'total_count' => $totalCount,
            'search_term' => $this->search,
        ];
    }

    public function render()
    {
        return view('livewire.admin.office-management');
    }
}