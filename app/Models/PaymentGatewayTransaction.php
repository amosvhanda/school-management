<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayTransaction extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'config_id', 'invoice_id', 'student_id',
        'provider_reference', 'internal_reference', 'amount', 'currency',
        'payment_method', 'status', 'provider_response', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'provider_response' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function config(): BelongsTo
    {
        return $this->belongsTo(PaymentGatewayConfig::class, 'config_id');
    }
}
