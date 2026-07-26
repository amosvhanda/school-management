<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use App\Models\Concerns\HasCustomFields;
use App\Models\Concerns\Versionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use Auditable, BelongsToSchool, HasCustomFields, HasFactory, Versionable;

    protected $fillable = [
        'student_number',
        'first_name',
        'last_name',
        'full_name',
        'date_of_birth',
        'gender',
        'national_id',
        'phone',
        'email',
        'address',
        'suburb',
        'class',
        'school',
        'status',
        'status_reason',
        'status_changed_at',
        'exited_at',
        'repetition_count',
        'previous_school',
        'balance',
        'currency',
        'user_id',
        'class_id',
        'stream_id',
        'house_id',
        'school_id',
        'student_category_id',
        'grade_level_id',
        'guardian_first_name',
        'guardian_last_name',
        'guardian_phone',
        'guardian_email',
        'guardian_relationship',
    ];

    protected $appends = ['guardian'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'exited_at' => 'date',
            'status_changed_at' => 'datetime',
            'balance' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function studentCategory(): BelongsTo
    {
        return $this->belongsTo(StudentCategory::class);
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function statusEvents(): HasMany
    {
        return $this->hasMany(StudentStatusEvent::class);
    }

    /**
     * Get parents associated with this student
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot('relationship', 'is_primary', 'school_id')
            ->withTimestamps();
    }

    /**
     * Get enrollment records for this student
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get classes this student is enrolled in (many-to-many via enrollments)
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(ClassModel::class, 'enrollments', 'student_id', 'class_id')
            ->withPivot('academic_year', 'enrolled_at', 'left_at', 'status')
            ->withTimestamps();
    }

    /**
     * Guardian object for API responses (frontend expects nested guardian).
     * Safe when guardian_* columns do not exist yet (before migration).
     */
    public function getGuardianAttribute(): array
    {
        if ($this->relationLoaded('guardians') && $this->guardians->isNotEmpty()) {
            $guardian = $this->guardians->first(fn ($g) => (bool) ($g->pivot->is_primary ?? false))
                ?? $this->guardians->first();

            return [
                'id' => $guardian->id,
                'first_name' => $guardian->first_name,
                'last_name' => $guardian->last_name,
                'firstName' => $guardian->first_name,
                'surname' => $guardian->last_name,
                'full_name' => $guardian->full_name,
                'phone' => $guardian->phone,
                'email' => $guardian->email,
                'relationship' => $guardian->pivot->relationship ?? $guardian->relationship ?? 'parent',
            ];
        }

        $fn = array_key_exists('guardian_first_name', $this->attributes) ? ($this->guardian_first_name ?? '') : '';
        $sn = array_key_exists('guardian_last_name', $this->attributes) ? ($this->guardian_last_name ?? '') : '';
        $phone = array_key_exists('guardian_phone', $this->attributes) ? ($this->guardian_phone ?? '') : '';
        $email = array_key_exists('guardian_email', $this->attributes) ? ($this->guardian_email ?? '') : '';
        $rel = array_key_exists('guardian_relationship', $this->attributes) ? ($this->guardian_relationship ?? 'parent') : 'parent';

        return [
            'first_name' => $fn,
            'last_name' => $sn,
            'firstName' => $fn,
            'surname' => $sn,
            'full_name' => trim("{$fn} {$sn}"),
            'phone' => $phone,
            'email' => $email,
            'relationship' => $rel,
        ];
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    /**
     * Get guardians associated with this student
     */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'guardian_student')
            ->withPivot('relationship', 'is_primary', 'can_pickup', 'emergency_contact')
            ->withTimestamps();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    protected function getCustomFieldEntityType(): string
    {
        return CustomField::ENTITY_STUDENT;
    }
}
