<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequisition extends Model
{
    use BelongsToSchool;

    public const SPEND_TYPES = [
        'procurement',
        'petty_cash',
        'utilities',
        'travel',
        'maintenance',
        'other',
    ];

    protected $fillable = [
        'school_id',
        'requested_by',
        'department_id',
        'vendor_id',
        'title',
        'description',
        'spend_type',
        'estimated_cost',
        'amount_paid',
        'payment_method',
        'payment_reference',
        'disbursed_at',
        'disbursed_by',
        'transaction_id',
        'status',
        'workflow_instance_id',
    ];

    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'disbursed_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function disbursedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItem::class, 'requisition_id');
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    public function ledgerCategory(): string
    {
        return match ($this->spend_type) {
            'petty_cash' => 'petty_cash',
            'utilities' => 'utilities',
            'travel' => 'travel',
            'maintenance' => 'maintenance',
            'other' => 'other',
            default => 'procurement',
        };
    }
}
