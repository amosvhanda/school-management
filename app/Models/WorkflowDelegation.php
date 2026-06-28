<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class WorkflowDelegation extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'delegator_id', 'delegate_id', 'starts_on', 'ends_on', 'scope', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
