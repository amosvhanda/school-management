<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CbtExamSession extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'exam_id', 'student_id', 'question_ids', 'started_at',
        'submitted_at', 'score', 'status',
    ];

    protected function casts(): array
    {
        return [
            'question_ids' => 'array',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'score' => 'decimal:2',
        ];
    }

    public function responses(): HasMany
    {
        return $this->hasMany(CbtResponse::class, 'session_id');
    }
}
