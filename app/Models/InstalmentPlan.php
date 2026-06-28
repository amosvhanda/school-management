<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstalmentPlan extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'invoice_id', 'total_amount', 'currency', 'status',
    ];

    protected function casts(): array
    {
        return ['total_amount' => 'decimal:2'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(InstalmentScheduleItem::class, 'plan_id');
    }
}
