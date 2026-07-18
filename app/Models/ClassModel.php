<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassModel extends Model
{
    use BelongsToSchool, HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'form',
        'school',
        'capacity',
        'current_enrollment',
        'teacher_id',
        'status',
        'school_id',
        'grade_level_id',
        'stream_id',
        'room_id',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    /** @deprecated Prefer students() via class_id; kept for legacy string class column. */
    public function studentsByClassName(): HasMany
    {
        return $this->hasMany(Student::class, 'class', 'name');
    }

    /** @alias students() */
    public function studentsByClassId(): HasMany
    {
        return $this->students();
    }

    public function timetable(): HasMany
    {
        return $this->hasMany(Timetable::class, 'class_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get enrollment records for this class
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get students enrolled in this class (many-to-many via enrollments)
     */
    public function enrolledStudents(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'enrollments', 'class_id', 'student_id')
            ->withPivot('academic_year', 'enrolled_at', 'left_at', 'status')
            ->withTimestamps();
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }
}
