<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'exam_id',
        'student_id',
        'subject_id',
        'marks_obtained',
        'total_marks',
        'percentage',
        'grade',
        'remarks',
        'status',
        'entered_by',
        'approved_by',
        'approved_at',
        'is_locked',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
        'marks_obtained' => 'decimal:2',
        'total_marks' => 'decimal:2',
        'percentage' => 'decimal:2',
        'approved_at' => 'datetime',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];
    }

    protected static function booted()
    {
        static::creating(function ($examResult) {
            // Calculate percentage
            if ($examResult->total_marks > 0) {
                $examResult->percentage = ($examResult->marks_obtained / $examResult->total_marks) * 100;
            }

            // Get grade from grading scale
            if ($examResult->percentage !== null) {
                $grade = \App\Models\GradingScale::getGradeForScore($examResult->school_id, $examResult->percentage);
                $examResult->grade = $grade;
            }
        });

        static::updating(function ($examResult) {
            if ($examResult->is_locked || ($examResult->getOriginal('is_locked') && $examResult->isDirty(['marks_obtained', 'total_marks', 'remarks', 'grade', 'percentage']))) {
                throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Exam marks are locked after moderation approval.');
            }

            // Recalculate percentage if marks changed
            if ($examResult->isDirty(['marks_obtained', 'total_marks']) && $examResult->total_marks > 0) {
                $examResult->percentage = ($examResult->marks_obtained / $examResult->total_marks) * 100;
            }

            // Recalculate grade if percentage changed
            if ($examResult->isDirty('percentage') && $examResult->percentage !== null) {
                $grade = GradingScale::getGradeForScore($examResult->school_id, $examResult->percentage);
                $examResult->grade = $grade;
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
