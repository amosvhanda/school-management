<?php

namespace App\Models;

use App\Models\Concerns\IsolatesViaSchoolRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDisposal extends Model
{
    use IsolatesViaSchoolRelation;

    protected $fillable = [
        'asset_id', 'disposed_at', 'reason', 'workflow_instance_id', 'approved_by',
    ];

    public static function schoolIsolationRelation(): string
    {
        return 'asset';
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
