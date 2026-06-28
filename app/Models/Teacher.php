<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Models\Concerns\HasCustomFields;
use App\Models\CustomField;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use BelongsToSchool, HasCustomFields, HasFactory;

    protected $fillable = [
        'employee_id',
        'first_name',
        'last_name',
        'name',
        'email',
        'phone',
        'address',
        'subject',
        'department',
        'qualification',
        'joining_date',
        'status',
        'user_id',
        'school_id',
        'base_salary',
        'salary_currency',
        'allowances',
        'deductions',
        'bank_name',
        'bank_account_number',
        'payment_method',
        'employment_type',
        'period_rate',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'base_salary' => 'decimal:2',
            'period_rate' => 'decimal:2',
            'allowances' => 'array',
            'deductions' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassModel::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function payroll(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    protected function getCustomFieldEntityType(): string
    {
        return CustomField::ENTITY_TEACHER;
    }
}
