<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoleChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $oldRoleId;
    public $newRoleId;
    public $reason;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, ?int $oldRoleId, int $newRoleId, ?string $reason = null)
    {
        $this->user = $user;
        $this->oldRoleId = $oldRoleId;
        $this->newRoleId = $newRoleId;
        $this->reason = $reason;
    }
}