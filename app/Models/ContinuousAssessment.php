<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContinuousAssessment extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'subject_id', 'category_id', 'term_id',
        'title', 'score', 'max_score', 'assessed_on', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'assessed_on' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssessmentCategory::class, 'category_id');
    }
}
