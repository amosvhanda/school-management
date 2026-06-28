<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'permission_ids'];

    protected function casts(): array
    {
        return [
        'permission_ids' => 'array',
    ];
    }

    public function users()
    {
        return $this->hasMany(User::class, 'role', 'slug');
    }
}
