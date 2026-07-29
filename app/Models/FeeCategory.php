<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeCategory extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'is_active',
        'order',
    ];

    protected function casts(): array
    {
        return [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function feeStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class, 'fee_category_id');
    }

    /**
     * Legacy string-based structures that still match this category name.
     */
    public function feeStructuresByName(): HasMany
    {
        return $this->hasMany(FeeStructure::class, 'category', 'name');
    }
}
