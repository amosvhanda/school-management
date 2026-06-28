<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Term extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'academic_year',
        'start_date',
        'end_date',
        'is_current',
        'is_active',
        'order',
        'description',
    ];

    protected function casts(): array
    {
        return [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'term', 'name');
    }

    /**
     * Scope to get current term for a school
     */
    public function scopeCurrent($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId)
            ->where('is_current', true)
            ->where('is_active', true);
    }

    /**
     * Scope to get terms for an academic year
     */
    public function scopeForAcademicYear($query, int $schoolId, string $academicYear)
    {
        return $query->where('school_id', $schoolId)
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->orderBy('order');
    }
}
