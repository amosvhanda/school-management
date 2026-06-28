<?php

namespace App\Models;

use App\Enums\LicenseKeyStatus;
use App\Enums\LicensePlanType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseKey extends Model
{
    protected $fillable = [
        'key_prefix',
        'key_hash',
        'plan_type',
        'duration_months',
        'status',
        'school_id',
        'activated_at',
        'expires_at',
        'revoked_at',
        'customer_name',
        'customer_email',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'plan_type' => LicensePlanType::class,
            'status' => LicenseKeyStatus::class,
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isLifetime(): bool
    {
        return $this->plan_type === LicensePlanType::Lifetime;
    }
}
