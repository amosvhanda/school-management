<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSubstitution extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'class_id', 'subject_id', 'absent_teacher_id', 'substitute_teacher_id',
        'leave_request_id', 'date', 'period', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function absentTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'absent_teacher_id');
    }

    public function substituteTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'substitute_teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
