<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class OperationsAlert extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'severity', 'category', 'title', 'message',
        'metadata', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'resolved_at' => 'datetime',
        ];
    }
}
