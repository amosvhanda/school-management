<?php

namespace App\Models;

use App\Models\Concerns\IsolatesViaSchoolRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenanceLog extends Model
{
    use IsolatesViaSchoolRelation;

    protected $fillable = ['asset_id', 'maintenance_date', 'description', 'cost', 'performed_by'];

    public static function schoolIsolationRelation(): string
    {
        return 'asset';
    }

    protected function casts(): array
    {
        return ['maintenance_date' => 'date', 'cost' => 'decimal:2'];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
