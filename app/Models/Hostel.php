<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hostel extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    public function rooms(): HasMany
    {
        return $this->hasMany(HostelRoom::class);
    }
}
