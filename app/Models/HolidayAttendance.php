<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolidayAttendance extends Model
{
    use BelongsToSchool;

    protected $table = 'holiday_attendance';

    protected $fillable = [
        'school_id', 'holiday_program_id', 'student_id', 'date', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(HolidayProgram::class, 'holiday_program_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
