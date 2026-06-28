<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ComplianceRequirement extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'authority', 'requirement_code', 'title', 'due_date', 'status', 'evidence',
    ];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'evidence' => 'array'];
    }
}
