<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCardNarrative extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'teacher_id', 'student_id', 'class_id', 'term_id',
        'academic_comment', 'behaviour_comment', 'recommendations', 'strengths',
        'areas_for_improvement', 'status', 'submitted_at',
    ];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }
}
