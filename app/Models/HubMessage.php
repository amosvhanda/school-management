<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HubMessage extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'sender_id', 'subject', 'body', 'channels',
        'audience_type', 'audience_ids', 'status', 'sent_at', 'delivery_stats',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'audience_ids' => 'array',
            'delivery_stats' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(HubMessageDelivery::class);
    }
}
