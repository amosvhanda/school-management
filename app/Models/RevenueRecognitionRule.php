<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class RevenueRecognitionRule extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'name', 'recognition_method', 'config', 'is_active'];

    protected function casts(): array
    {
        return ['config' => 'array', 'is_active' => 'boolean'];
    }
}
