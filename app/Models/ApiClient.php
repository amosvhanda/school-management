<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ApiClient extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'name', 'client_id', 'client_secret_hash',
        'scopes', 'is_active', 'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }
}
