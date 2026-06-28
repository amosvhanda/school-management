<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordVersion extends Model
{
    use BelongsToSchool;

    public $timestamps = false;

    protected $fillable = [
        'school_id', 'versionable_type', 'versionable_id', 'version_number',
        'snapshot', 'changed_by', 'change_reason', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
