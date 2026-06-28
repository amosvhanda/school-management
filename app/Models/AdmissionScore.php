<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AdmissionScore extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'enrollment_application_id', 'applicant_name',
        'academic_score', 'interview_score', 'total_score', 'recommendation',
    ];

    protected function casts(): array
    {
        return [
            'academic_score' => 'decimal:2',
            'interview_score' => 'decimal:2',
            'total_score' => 'decimal:2',
        ];
    }
}
