<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BehaviorPoint extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'points', 'category',
        'description', 'recorded_by', 'recorded_on',
    ];

    protected function casts(): array
    {
        return ['recorded_on' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
