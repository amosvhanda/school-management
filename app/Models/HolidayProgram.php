<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HolidayProgram extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'name', 'academic_year', 'start_date', 'end_date',
        'fee_amount', 'currency', 'is_active', 'description',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'fee_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(HolidayEnrollment::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(HolidayAttendance::class);
    }
}
