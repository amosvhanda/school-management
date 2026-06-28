<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplinaryRecord extends Model
{
    use Auditable, BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'incident_date', 'category', 'severity',
        'description', 'action_taken', 'recorded_by', 'parent_notified', 'parent_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'parent_notified' => 'boolean',
            'parent_notified_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
