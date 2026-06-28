<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class StaffContract extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'user_id', 'contract_type', 'start_date', 'end_date', 'salary', 'status', 'renewal_due_at',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'salary' => 'decimal:2', 'renewal_due_at' => 'datetime'];
    }
}
