<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentGatewayConfig extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'provider', 'credentials', 'is_active',
        'supports_cards', 'supports_mobile_money', 'supports_bank_transfer',
    ];

    protected $hidden = [
        'credentials',
    ];

    protected $appends = [
        'credentials_configured',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'is_active' => 'boolean',
            'supports_cards' => 'boolean',
            'supports_mobile_money' => 'boolean',
            'supports_bank_transfer' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentGatewayTransaction::class, 'config_id');
    }

    public function getCredentialsConfiguredAttribute(): bool
    {
        return ! empty($this->credentials);
    }
}
