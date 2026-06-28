<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentIntervention extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'intervention_type', 'status',
        'summary', 'action_plan', 'assigned_to', 'created_by',
        'start_date', 'follow_up_date', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'follow_up_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
