<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStructure extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $fillable = [
        'class_id',
        'class_name',
        'category',
        'amount',
        'currency',
        'school_id',
    ];

    protected function casts(): array
    {
        return [
        'amount' => 'decimal:2',
    ];
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invoice::class, 'fee_structure_id');
    }
}
