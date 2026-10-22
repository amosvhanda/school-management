<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'domain',
        'verification_token',
        'is_primary',
        'is_verified',
        'status',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
