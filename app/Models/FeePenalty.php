<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePenalty extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'rule_id', 'invoice_id', 'student_id',
        'amount', 'applied_on', 'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'applied_on' => 'date',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(FeePenaltyRule::class, 'rule_id');
    }
}
