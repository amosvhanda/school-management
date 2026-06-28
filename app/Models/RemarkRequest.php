<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class RemarkRequest extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'exam_result_id', 'student_id', 'reason', 'status',
        'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }
}
