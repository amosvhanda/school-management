<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class BankStatementLine extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'transaction_date', 'reference', 'description',
        'amount', 'currency', 'matched_payment_id', 'match_status',
    ];

    protected function casts(): array
    {
        return ['transaction_date' => 'date', 'amount' => 'decimal:2'];
    }
}
