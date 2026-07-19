<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'term_id',
        'grade_level_id',
        'subject_id',
        'name',
        'description',
        'exam_date',
        'start_time',
        'end_time',
        'total_marks',
        'passing_marks',
        'academic_year',
        'is_published',
        'results_approved_at',
        'results_approved_by',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date:Y-m-d',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'total_marks' => 'decimal:2',
            'passing_marks' => 'decimal:2',
            'is_published' => 'boolean',
            'results_approved_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    /**
     * Get all students in the grade level for this exam
     */
    public function getStudentsAttribute()
    {
        return Student::where('school_id', $this->school_id)
            ->where('grade_level_id', $this->grade_level_id)
            ->where('status', 'active')
            ->get();
    }
}
