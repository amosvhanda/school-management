<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class LearningOutcomeRecord extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'subject_id', 'outcome_code',
        'competency_level', 'score', 'evidence', 'assessed_by', 'assessed_on',
    ];

    protected function casts(): array
    {
        return ['assessed_on' => 'date', 'score' => 'decimal:2'];
    }
}
