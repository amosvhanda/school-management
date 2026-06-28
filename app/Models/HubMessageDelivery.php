<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HubMessageDelivery extends Model
{
    protected $fillable = [
        'hub_message_id', 'recipient_user_id', 'channel', 'recipient_address',
        'status', 'delivered_at', 'read_at', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(HubMessage::class, 'hub_message_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
