<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class SubjectPrerequisite extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'subject_id', 'prerequisite_subject_id', 'minimum_grade',
    ];

    protected function casts(): array
    {
        return ['minimum_grade' => 'decimal:2'];
    }
}
