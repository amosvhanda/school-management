<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentApplication extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'first_name',
        'surname',
        'date_of_birth',
        'gender',
        'national_id',
        'address',
        'suburb',
        'phone',
        'email',
        'previous_school',
        'grade_applying_for',
        'academic_year',
        'guardian_first_name',
        'guardian_surname',
        'guardian_relationship',
        'guardian_phone',
        'guardian_email',
        'guardian_address',
        'guardian_employer',
        'medical_conditions',
        'allergies',
        'emergency_contact',
        'emergency_phone',
        'birth_certificate',
        'report_cards',
        'medical_certificate',
        'passport_photo',
        'status',
        'notes',
        'reviewed_by',
        'reviewed_at',
        'student_id',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'birth_certificate' => 'boolean',
            'report_cards' => 'boolean',
            'medical_certificate' => 'boolean',
            'passport_photo' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
