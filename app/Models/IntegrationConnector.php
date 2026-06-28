<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class IntegrationConnector extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'provider', 'connector_type', 'config', 'is_active', 'last_sync_at',
    ];

    protected function casts(): array
    {
        return ['config' => 'array', 'is_active' => 'boolean', 'last_sync_at' => 'datetime'];
    }
}
