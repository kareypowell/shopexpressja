<?php

namespace App\Http\Livewire\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Office;
use Livewire\Component;

class OfficeForm extends Component
{
    use AuthorizesRequests;

    // Form fields
    public string $name = '';
    public string $address = '';

    // Component state
    public ?Office $office = null;
    public bool $isEditing = false;
    public string $successMessage = '';
    public string $errorMessage = '';

    protected $rules = [
        'name' => 'required|string|max:255',
        'address' => 'required|string|max:500',
    ];

    protected $validationAttributes = [
        'name' => 'office name',
        'address' => 'office address',
    ];

    public function mount(?Office $office = null)
    {
        if ($office && $office->exists) {
            $this->authorize('update', $office);
            $this->office = $office;
            $this->isEditing = true;
            $this->name = $office->name;
            $this->address = $office->address;
        } else {
            $this->authorize('create', Office::class);
            $this->isEditing = false;
        }
    }

    /**
     * Real-time validation
     */
    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    /**
     * Save the office
     */
    public function save()
    {
        $this->validate();

        try {
            if ($this->isEditing) {
                $this->authorize('update', $this->office);
                
                $this->office->update([
                    'name' => $this->name,
                    'address' => $this->address,
                ]);

                $this->successMessage = "Office \"{$this->office->name}\" has been updated successfully.";
                
                // Emit event to refresh parent components
                $this->emit('officeUpdated', $this->successMessage);
                
                // Redirect to office show page
                return redirect()->route('admin.offices.show', $this->office)
                                ->with('success', $this->successMessage);
                
            } else {
                $this->authorize('create', Office::class);
                
                $office = Office::create([
                    'name' => $this->name,
                    'address' => $this->address,
                ]);

                $this->successMessage = "Office \"{$office->name}\" has been created successfully.";
                
                // Emit event to refresh parent components
                $this->emit('officeCreated', $this->successMessage);
                
                // Redirect to office show page
                return redirect()->route('admin.offices.show', $office)
                                ->with('success', $this->successMessage);
            }
            
        } catch (\Exception $e) {
            $this->errorMessage = 'An error occurred while saving the office. Please try again.';
            \Log::error('Office save error: ' . $e->getMessage());
        }
    }

    /**
     * Cancel and redirect back
     */
    public function cancel()
    {
        if ($this->isEditing && $this->office) {
            return redirect()->route('admin.offices.show', $this->office);
        } else {
            return redirect()->route('admin.offices.index');
        }
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
     * Get the page title
     */
    public function getPageTitleProperty()
    {
        return $this->isEditing ? 'Edit Office' : 'Create Office';
    }

    /**
     * Get the submit button text
     */
    public function getSubmitButtonTextProperty()
    {
        return $this->isEditing ? 'Update Office' : 'Create Office';
    }

    public function render()
    {
        return view('livewire.admin.office-form');
    }
}