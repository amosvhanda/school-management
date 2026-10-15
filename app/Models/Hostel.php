<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hostel extends Model
{
    use Auditable, BelongsToSchool;

    protected $fillable = [
        'school_id', 'name', 'gender', 'capacity', 'status',
    ];

    public function rooms(): HasMany
    {
        return $this->hasMany(HostelRoom::class);
    }
}
