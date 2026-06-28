<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowDefinition extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'code', 'name', 'module', 'description', 'layout', 'is_active'];

    protected function casts(): array
    {
        return [
            'layout' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowDefinitionStep::class, 'definition_id')->orderBy('step_order');
    }
}
