<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'student_id',
        'class_id',
        'teacher_id',
        'date',
        'status',
        'time_in',
        'time_out',
        'remarks',
        'marked_by',
        'school_id',
        'subject_id',
        'lesson_type',
        'parent_notified',
        'notification_sent_at',
        'submitted_at',
        'locked_at',
        'locked_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'time_in' => 'datetime',
            'time_out' => 'datetime',
            'parent_notified' => 'boolean',
            'notification_sent_at' => 'datetime',
            'submitted_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
