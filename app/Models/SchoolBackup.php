<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolBackup extends Model
{
    protected $fillable = [
        'school_id',
        'path',
        'status',
        'table_count',
        'row_count',
        'file_size',
        'created_by',
        'restored_at',
    ];

    protected function casts(): array
    {
        return [
            'restored_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
