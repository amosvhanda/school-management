<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use BelongsToSchool;

    public $timestamps = false;

    protected $fillable = [
        'school_id', 'user_id', 'module', 'action', 'auditable_type', 'auditable_id',
        'description', 'old_values', 'new_values', 'metadata',
        'ip_address', 'user_agent', 'device_type', 'platform', 'location',
        'request_method', 'request_path', 'created_at',
        'integrity_hash', 'previous_hash',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
