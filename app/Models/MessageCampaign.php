<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class MessageCampaign extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'name', 'channels', 'audience_filter', 'message_body',
        'scheduled_at', 'sent_at', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'audience_filter' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }
}
