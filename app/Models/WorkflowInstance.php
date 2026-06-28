<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowInstance extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'definition_id', 'subject_type', 'subject_id', 'status',
        'current_step_order', 'initiated_by', 'metadata', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'completed_at' => 'datetime'];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'definition_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(WorkflowApproval::class, 'instance_id');
    }
}
