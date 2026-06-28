<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'asset_tag', 'name', 'category', 'purchase_date',
        'purchase_cost', 'location', 'custodian_user_id', 'status',
    ];

    protected function casts(): array
    {
        return ['purchase_date' => 'date', 'purchase_cost' => 'decimal:2'];
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'custodian_user_id');
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(AssetMaintenanceLog::class);
    }
}
