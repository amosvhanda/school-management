<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'parent_school_id',
        'branch_type',
        'address',
        'phone',
        'email',
        'status',
        'contact_person',
        'contact_phone',
        'contact_email',
        'currency_default',
        'currency',
        'currency_locked',
        'academic_year',
        'current_term',
        'settings',
        'license_status',
        'license_plan',
        'license_expires_at',
    ];

    protected function casts(): array
    {
        return [
        'settings' => 'array',
        'currency_locked' => 'boolean',
        'license_expires_at' => 'datetime',
    ];
    }

    public function licenseKeys(): HasMany
    {
        return $this->hasMany(LicenseKey::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassModel::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function reportTemplates(): HasMany
    {
        return $this->hasMany(ReportTemplate::class, 'school_id');
    }

    public function feeStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function payroll(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function gradeLevels(): HasMany
    {
        return $this->hasMany(GradeLevel::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function gradingScales(): HasMany
    {
        return $this->hasMany(GradingScale::class);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function notificationQueue(): HasMany
    {
        return $this->hasMany(NotificationQueue::class);
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function tests(): HasMany
    {
        return $this->hasMany(Test::class);
    }

    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function enrollmentApplications(): HasMany
    {
        return $this->hasMany(EnrollmentApplication::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function schoolSettings(): HasMany
    {
        return $this->hasMany(SchoolSetting::class);
    }

    /**
     * Get the default currency for the school
     */
    public function getDefaultCurrency(): string
    {
        return $this->currency_default ?? $this->attributes['currency'] ?? 'USD';
    }

    /**
     * Get the default currency for the school
     */
    public function getCurrencyAttribute(): string
    {
        return $this->getDefaultCurrency();
    }

    /**
     * Validate currency is allowed (USD or ZWG)
     */
    public function validateCurrency(string $currency): bool
    {
        return in_array(strtoupper($currency), ['USD', 'ZWG']);
    }
}
