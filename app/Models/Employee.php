<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'employee_number',
        'first_name',
        'last_name',
        'name',
        'email',
        'phone',
        'designation_id',
        'department_id',
        'joining_date',
        'employment_type',
        'status',
        'base_salary',
        'salary_currency',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'base_salary' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Employee $employee) {
            if (empty($employee->name)) {
                $employee->name = trim($employee->first_name.' '.$employee->last_name);
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
