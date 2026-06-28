<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scholarship extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'name', 'type', 'amount', 'percentage',
        'criteria', 'application_deadline', 'slots', 'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'percentage' => 'decimal:2',
            'application_deadline' => 'date',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ScholarshipApplication::class);
    }
}
