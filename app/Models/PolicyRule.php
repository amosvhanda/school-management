<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class PolicyRule extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'code', 'name', 'module', 'trigger_event',
        'conditions', 'actions', 'priority', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'actions' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
