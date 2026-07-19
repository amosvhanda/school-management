<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolTrip extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'name',
        'destination',
        'trip_date',
        'return_date',
        'fee_amount',
        'currency',
        'capacity',
        'is_active',
        'open_for_registration',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'trip_date' => 'date',
            'return_date' => 'date',
            'fee_amount' => 'decimal:2',
            'capacity' => 'integer',
            'is_active' => 'boolean',
            'open_for_registration' => 'boolean',
        ];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(SchoolTripEnrollment::class);
    }

    public function enrolledCount(): int
    {
        return $this->enrollments()->where('status', 'enrolled')->count();
    }

    public function spotsRemaining(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        return max(0, (int) $this->capacity - $this->enrolledCount());
    }
}
