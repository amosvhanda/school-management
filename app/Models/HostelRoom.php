<?php

namespace App\Models;

use App\Models\Concerns\IsolatesViaSchoolRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostelRoom extends Model
{
    use IsolatesViaSchoolRelation;

    protected $guarded = [];

    public static function schoolIsolationRelation(): string
    {
        return 'hostel';
    }

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    public function beds(): HasMany
    {
        return $this->hasMany(HostelBed::class, 'room_id');
    }
}
