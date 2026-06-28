<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use BelongsToSchool;

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'school_id', 'code', 'name', 'account_type', 'parent_id', 'normal_balance', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'account_id');
    }
}
