<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventorySale extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'sale_number', 'student_id', 'total_amount', 'currency',
        'payment_method', 'status', 'invoice_id', 'sold_by', 'notes',
    ];

    protected function casts(): array
    {
        return ['total_amount' => 'decimal:2'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventorySaleItem::class);
    }
}
