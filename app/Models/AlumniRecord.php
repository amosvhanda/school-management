<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AlumniRecord extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'full_name', 'graduation_year',
        'email', 'phone', 'current_occupation', 'engagement_history',
    ];

    protected function casts(): array
    {
        return ['engagement_history' => 'array'];
    }
}
