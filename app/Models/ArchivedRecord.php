<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ArchivedRecord extends Model
{
    use BelongsToSchool;

    public $timestamps = false;

    protected $fillable = [
        'school_id', 'source_table', 'source_id', 'snapshot', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'archived_at' => 'datetime',
        ];
    }
}
