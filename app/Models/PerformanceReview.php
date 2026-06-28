<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class PerformanceReview extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'staff_user_id', 'reviewer_id', 'period',
        'overall_score', 'summary', 'criteria_scores', 'status',
    ];

    protected function casts(): array
    {
        return ['criteria_scores' => 'array', 'overall_score' => 'decimal:2'];
    }
}
