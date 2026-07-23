<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonPlan extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'teacher_id', 'class_id', 'subject_id', 'title', 'plan_type',
        'planned_date', 'topic', 'objectives', 'outcomes', 'activities', 'resources',
        'status', 'completed_at', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'planned_date' => 'date',
            'completed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
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
