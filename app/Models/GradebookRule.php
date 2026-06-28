<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class GradebookRule extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'subject_id', 'grade_level_id', 'category_weights', 'pass_mark',
    ];

    protected function casts(): array
    {
        return ['category_weights' => 'array', 'pass_mark' => 'decimal:2'];
    }
}
