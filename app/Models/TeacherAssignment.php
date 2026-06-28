<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAssignment extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'teacher_id',
        'grade_level_id',
        'class_id',
        'subject_id',
        'role',
        'assigned_at',
        'assigned_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
        'assigned_at' => 'date',
        'assigned_until' => 'date',
        'is_active' => 'boolean',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
