<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AcademicCalendarEntry extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'term_id', 'entry_type', 'title', 'start_date', 'end_date', 'is_holiday', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_holiday' => 'boolean',
            'metadata' => 'array',
        ];
    }
}
