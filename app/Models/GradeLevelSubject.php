<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeLevelSubject extends Model
{
    use BelongsToSchool;

    protected $table = 'grade_level_subjects';

    protected $fillable = [
        'school_id',
        'grade_level_id',
        'subject_id',
        'stream_id',
        'is_core',
    ];

    protected function casts(): array
    {
        return ['is_core' => 'boolean'];
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }
}
