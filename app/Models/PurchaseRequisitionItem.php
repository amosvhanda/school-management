<?php

namespace App\Models;

use App\Models\Concerns\IsolatesViaSchoolRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequisitionItem extends Model
{
    use IsolatesViaSchoolRelation;

    protected $fillable = ['requisition_id', 'description', 'quantity', 'unit_cost'];

    public static function schoolIsolationRelation(): string
    {
        return 'requisition';
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'requisition_id');
    }
}
