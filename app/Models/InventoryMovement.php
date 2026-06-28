<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'inventory_item_id', 'type', 'quantity_change',
        'quantity_after', 'reference_type', 'reference_id', 'notes', 'created_by',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
