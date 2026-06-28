<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    use BelongsToSchool, HasFactory;

    protected $table = 'payroll';

    protected $fillable = [
        'school_id',
        'teacher_id',
        'month',
        'year',
        'base_salary',
        'allowances',
        'allowances_total',
        'gross_salary',
        'deductions',
        'deductions_total',
        'net_salary',
        'amount_paid',
        'currency',
        'status',
        'paid_at',
        'payment_method',
        'payment_reference',
        'processed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
        'base_salary' => 'decimal:2',
        'allowances_total' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'deductions_total' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'allowances' => 'array',
        'deductions' => 'array',
        'paid_at' => 'date',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
