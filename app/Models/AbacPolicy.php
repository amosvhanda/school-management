<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AbacPolicy extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'name', 'resource', 'conditions', 'effect', 'is_active'];

    protected function casts(): array
    {
        return ['conditions' => 'array', 'is_active' => 'boolean'];
    }
}
