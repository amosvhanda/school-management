<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestResult extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'test_id',
        'student_id',
        'subject_id',
        'marks_obtained',
        'total_marks',
        'percentage',
        'grade',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
        'marks_obtained' => 'decimal:2',
        'total_marks' => 'decimal:2',
        'percentage' => 'decimal:2',
    ];
    }

    protected static function booted()
    {
        static::creating(function ($testResult) {
            // Calculate percentage
            if ($testResult->total_marks > 0) {
                $testResult->percentage = ($testResult->marks_obtained / $testResult->total_marks) * 100;
            }

            // Get grade from grading scale
            if ($testResult->percentage !== null) {
                $grade = \App\Models\GradingScale::getGradeForScore($testResult->school_id, $testResult->percentage);
                $testResult->grade = $grade;
            }
        });

        static::updating(function ($testResult) {
            // Recalculate percentage if marks changed
            if ($testResult->isDirty(['marks_obtained', 'total_marks']) && $testResult->total_marks > 0) {
                $testResult->percentage = ($testResult->marks_obtained / $testResult->total_marks) * 100;
            }

            // Recalculate grade if percentage changed
            if ($testResult->isDirty('percentage') && $testResult->percentage !== null) {
                $grade = \App\Models\GradingScale::getGradeForScore($testResult->school_id, $testResult->percentage);
                $testResult->grade = $grade;
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
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
