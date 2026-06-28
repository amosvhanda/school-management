<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class RetentionPolicy extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'module', 'retain_years', 'archive_action', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
