<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeePenaltyRule extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'name', 'grace_days', 'penalty_type',
        'penalty_value', 'frequency', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'penalty_value' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(FeePenalty::class, 'rule_id');
    }
}
