<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumVersion extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'subject_id', 'term_id', 'academic_year', 'version_number',
        'syllabus', 'learning_outcomes', 'status', 'published_by', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'learning_outcomes' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
