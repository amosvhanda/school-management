<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstalmentScheduleItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'plan_id', 'installment_number', 'due_date', 'amount', 'amount_paid', 'status', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(InstalmentPlan::class, 'plan_id');
    }
}
