<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class PolicyRule extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'description',
        'module',
        'trigger_event',
        'conditions',
        'actions',
        'priority',
        'is_active',
        'stop_on_match',
        'last_triggered_at',
        'execution_count',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'actions' => 'array',
            'is_active' => 'boolean',
            'stop_on_match' => 'boolean',
            'last_triggered_at' => 'datetime',
            'execution_count' => 'integer',
        ];
    }

    /**
     * Scope query to active rules for a specific event sorted by priority.
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->where('trigger_event', $event)
            ->where('is_active', true)
            ->orderBy('priority', 'asc');
    }
}
