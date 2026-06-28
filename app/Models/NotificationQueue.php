<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationQueue extends Model
{
    use BelongsToSchool, HasFactory;

    protected $table = 'notification_queue';

    protected $fillable = [
        'school_id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'guardian_id',
        'parent_user_id',
        'channel',
        'recipient_email',
        'recipient_phone',
        'subject',
        'message',
        'data',
        'status',
        'sent_at',
        'error_message',
        'retry_count',
    ];

    protected function casts(): array
    {
        return [
        'data' => 'array',
        'sent_at' => 'datetime',
        'retry_count' => 'integer',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
