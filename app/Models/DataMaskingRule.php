<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class DataMaskingRule extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'module', 'field', 'visible_roles', 'mask_pattern', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'visible_roles' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
