<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookSubscription extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'event_type',
        'target_url',
        'secret',
        'secret_hash',
        'is_active',
    ];

    protected $hidden = [
        'secret',
        'secret_hash',
    ];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'subscription_id');
    }

    public function signingSecret(): ?string
    {
        return is_string($this->secret) && $this->secret !== '' ? $this->secret : null;
    }
}
