<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'head_teacher_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
        'is_active' => 'boolean',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function headTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'head_teacher_id');
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class, 'department', 'name');
    }
}
