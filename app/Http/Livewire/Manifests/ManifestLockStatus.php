<?php

namespace App\Http\Livewire\Manifests;

use App\Models\Manifest;
use App\Services\ManifestLockService;
use Livewire\Component;

class ManifestLockStatus extends Component
{
    public Manifest $manifest;
    public bool $showUnlockModal = false;
    public bool $showLockModal = false;
    public string $unlockReason = '';
    public string $lockReason = '';

    protected $rules = [
        'unlockReason' => 'required|string|min:10|max:500',
        'lockReason' => 'required|string|min:10|max:500',
    ];

    protected $messages = [
        'unlockReason.required' => 'A reason for unlocking is required.',
        'unlockReason.min' => 'The reason must be at least 10 characters long.',
        'unlockReason.max' => 'The reason cannot exceed 500 characters.',
        'lockReason.required' => 'A reason for locking is required.',
        'lockReason.min' => 'The reason must be at least 10 characters long.',
        'lockReason.max' => 'The reason cannot exceed 500 characters.',
    ];

    public function mount(Manifest $manifest)
    {
        $this->manifest = $manifest;
    }

    public function render()
    {
        return view('livewire.manifests.manifest-lock-status');
    }

    public function showUnlockModal()
    {
        if (!auth()->user()->can('unlock', $this->manifest)) {
            $this->addError('unlock', 'You do not have permission to unlock this manifest.');
            return;
        }

        $this->showUnlockModal = true;
        $this->resetErrorBag();
    }

    public function unlockManifest()
    {
        $this->validate(['unlockReason' => 'required|string|min:10|max:500']);

        if (!auth()->user()->can('unlock', $this->manifest)) {
            $this->addError('unlock', 'You do not have permission to unlock this manifest.');
            return;
        }

        $lockService = app(ManifestLockService::class);
        $result = $lockService->unlockManifest(
            $this->manifest,
            auth()->user(),
            $this->unlockReason
        );

        if ($result['success']) {
            $this->manifest->refresh();
            $this->showUnlockModal = false;
            $this->unlockReason = '';
            $this->resetErrorBag();
            $this->emit('manifestUnlocked');
            session()->flash('success', $result['message']);
        } else {
            $this->addError('unlock', $result['message']);
        }
    }

    public function cancelUnlock()
    {
        $this->showUnlockModal = false;
        $this->unlockReason = '';
        $this->resetErrorBag();
    }

    public function updatedUnlockReason()
    {
        $this->validateOnly('unlockReason');
    }

    public function showLockModal()
    {
        if (!auth()->user()->can('edit', $this->manifest)) {
            $this->addError('lock', 'You do not have permission to lock this manifest.');
            return;
        }

        $this->showLockModal = true;
        $this->resetErrorBag();
    }

    public function lockManifest()
    {
        $this->validate(['lockReason' => 'required|string|min:10|max:500']);

        if (!auth()->user()->can('edit', $this->manifest)) {
            $this->addError('lock', 'You do not have permission to lock this manifest.');
            return;
        }

        $lockService = app(ManifestLockService::class);
        $result = $lockService->lockManifest(
            $this->manifest,
            auth()->user(),
            $this->lockReason
        );

        if ($result['success']) {
            $this->manifest->refresh();
            $this->showLockModal = false;
            $this->lockReason = '';
            $this->resetErrorBag();
            $this->emit('manifestLocked');
            session()->flash('success', $result['message']);
        } else {
            $this->addError('lock', $result['message']);
        }
    }

    public function cancelLock()
    {
        $this->showLockModal = false;
        $this->lockReason = '';
        $this->resetErrorBag();
    }

    public function updatedLockReason()
    {
        $this->validateOnly('lockReason');
    }
}