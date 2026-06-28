<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolidayEnrollment extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'holiday_program_id', 'student_id', 'invoice_id',
        'status', 'enrolled_at',
    ];

    protected function casts(): array
    {
        return ['enrolled_at' => 'datetime'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(HolidayProgram::class, 'holiday_program_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
