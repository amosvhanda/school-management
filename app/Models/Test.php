<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Test extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'class_id',
        'subject_id',
        'teacher_id',
        'term_id',
        'name',
        'description',
        'test_date',
        'start_time',
        'end_time',
        'total_marks',
        'passing_marks',
        'academic_year',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
        'test_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'total_marks' => 'decimal:2',
        'passing_marks' => 'decimal:2',
        'is_published' => 'boolean',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function testResults(): HasMany
    {
        return $this->hasMany(TestResult::class);
    }

    /**
     * Get all students in the class for this test
     */
    public function getStudentsAttribute()
    {
        return Student::where('school_id', $this->school_id)
            ->where('class_id', $this->class_id)
            ->where('status', 'active')
            ->get();
    }
}
