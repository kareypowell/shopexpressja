<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'broadcast_message_id',
        'customer_id'
    ];

    /**
     * Get the broadcast message this recipient belongs to.
     */
    public function broadcastMessage(): BelongsTo
    {
        return $this->belongsTo(BroadcastMessage::class);
    }

    /**
     * Get the customer who is the recipient.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
