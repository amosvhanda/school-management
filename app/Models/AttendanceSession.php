<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSession extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'class_id', 'subject_id', 'teacher_id', 'date', 'period',
        'submitted_at', 'locked_at', 'locked_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'submitted_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }
}
