<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'student_id',
        'class_id',
        'academic_year',
        'enrolled_at',
        'left_at',
        'status',
        'school_id',
    ];

    protected function casts(): array
    {
        return [
        'enrolled_at' => 'date',
        'left_at' => 'date',
    ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
