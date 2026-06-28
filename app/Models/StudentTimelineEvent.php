<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class StudentTimelineEvent extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'event_type', 'title',
        'description', 'metadata', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'occurred_at' => 'datetime'];
    }
}
