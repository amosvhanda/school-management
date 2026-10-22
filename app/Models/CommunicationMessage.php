<?php

namespace App\Models;

use App\Models\Concerns\IsolatesViaSchoolRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationMessage extends Model
{
    use IsolatesViaSchoolRelation;

    protected $fillable = ['thread_id', 'sender_id', 'body', 'read_at'];

    public static function schoolIsolationRelation(): string
    {
        return 'thread';
    }

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(CommunicationThread::class, 'thread_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
