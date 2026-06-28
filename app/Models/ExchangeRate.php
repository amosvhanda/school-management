<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'from_currency', 'to_currency', 'rate', 'effective_date'];

    protected function casts(): array
    {
        return ['rate' => 'decimal:8', 'effective_date' => 'date'];
    }
}
