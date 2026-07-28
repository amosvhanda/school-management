<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'name',
        'fiscal_year',
        'department',
        'allocated_amount',
        'spent_amount',
        'currency',
        'status',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'allocated_amount' => 'decimal:2',
            'spent_amount' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function remainingAmount(): float
    {
        return (float) $this->allocated_amount - (float) $this->spent_amount;
    }
}
