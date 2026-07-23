<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    use BelongsToSchool, HasFactory;

    protected $table = 'assignments';

    protected $fillable = [
        'school_id',
        'title',
        'description',
        'subject',
        'class_id',
        'teacher_id',
        'due_date',
        'total_marks',
        'status',
        'submissions_count',
        'instructions',
        'attachment_url',
        'submission_type',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'total_marks' => 'decimal:2',
        ];
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
