<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stream extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassModel::class, 'stream_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'stream_id');
    }
}
