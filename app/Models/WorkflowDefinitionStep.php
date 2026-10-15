<?php

namespace App\Models;

use App\Models\Concerns\IsolatesViaSchoolRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowDefinitionStep extends Model
{
    use IsolatesViaSchoolRelation;

    protected $fillable = [
        'definition_id', 'step_order', 'name', 'approver_role',
        'approver_user_id', 'escalation_hours', 'position',
        'approval_mode', 'required_approvals',
    ];

    public static function schoolIsolationRelation(): string
    {
        return 'definition';
    }

    protected function casts(): array
    {
        return ['position' => 'array'];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'definition_id');
    }
}
