<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceipt extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'requisition_id', 'vendor_id', 'received_by', 'received_date',
        'notes', 'status',
    ];

    protected function casts(): array
    {
        return ['received_date' => 'date'];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'requisition_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
